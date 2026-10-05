<?php

namespace App\Http\Controllers;

use App\Models\CierreSemanal;
use App\Models\MetodoPago;
use App\Models\PagoEmpleado;
use App\Models\ReportePago;
use App\Models\Trabajador;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $rol = strtolower((string) ($user->rol?->rol ?? ''));

        // Solo quien tiene acceso a todos los módulos ve el tablero completo;
        // los demás ven su resumen de ganancias.
        if (! $user->esSuperRol()) {
            if ($rol === 'propietario') {
                return $this->propietarioDashboard($user);
            }

            return $this->workerDashboard($user, in_array($rol, ['modelo'], true) ? 'modelo' : 'moderador');
        }

        $stats = [
            'trabajadores' => Trabajador::count(),
            'reportes' => ReportePago::count(),
            // Solo lo que todavía no tiene cierre asignado (id_cierre NULL).
            'ingresos' => ReportePago::whereNull('id_cierre')->sum('precio'),
            'reportes_sin_cierre' => ReportePago::whereNull('id_cierre')->count(),
            'cierres' => CierreSemanal::count(),
            'pagos_empleados' => PagoEmpleado::sum('monto_final'),
        ];

        // Solo ingresos pendientes de cerrar (id_cierre NULL), igual que la tarjeta "Ingresos sin cerrar".
        $ingresosPorMetodo = DB::table('reporte_pagos')
            ->join('metodos_pago', 'reporte_pagos.id_mp', '=', 'metodos_pago.id_mp')
            ->whereNull('reporte_pagos.id_cierre')
            ->select(
                'metodos_pago.propietario',
                'metodos_pago.metodo_pago',
                DB::raw('SUM(reporte_pagos.precio) as total')
            )
            ->groupBy('metodos_pago.propietario', 'metodos_pago.metodo_pago')
            ->orderByDesc('total')
            ->get();

        $ultimosReportes = ReportePago::with(['modelo', 'moderador', 'metodoPago'])
            ->latest('fecha_reporte')
            ->limit(10)
            ->get();

        $ultimosCierres = CierreSemanal::withCount('reportes')->latest('fecha_fin')->limit(5)->get();

        return view('dashboard', [
            'modoEmpleado' => null,
            'modoPropietario' => null,
            'stats' => $stats,
            'ingresosPorMetodo' => $ingresosPorMetodo,
            'topModelos' => $this->ranking('id_modelo'),
            'topModeradores' => $this->ranking('id_moderador'),
            'ultimosReportes' => $ultimosReportes,
            'ultimosCierres' => $ultimosCierres,
        ]);
    }

    /**
     * Top 5 de los reportes de pago SIN cerrar (id_cierre NULL) agrupados por la
     * columna indicada (id_modelo / id_moderador).
     *
     * @return Collection<int, array{nombre: string, total: float, reportes: int}>
     */
    private function ranking(string $columna): Collection
    {
        $filas = DB::table('reporte_pagos')
            ->whereNull('id_cierre')
            ->select([
                $columna.' as trabajador_id',
                DB::raw('SUM(precio) as total'),
                DB::raw('COUNT(*) as reportes'),
            ])
            ->groupBy($columna)
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        $nombres = Trabajador::whereIn('id_trab', $filas->pluck('trabajador_id'))
            ->get()
            ->keyBy('id_trab');

        return $filas->map(fn ($fila) => [
            'nombre' => $nombres->get($fila->trabajador_id)?->nombre_completo ?? 'Trabajador #'.$fila->trabajador_id,
            'total' => (float) $fila->total,
            'reportes' => (int) $fila->reportes,
        ]);
    }

    private function workerDashboard(Trabajador $user, string $rol)
    {
        $reportes = $rol === 'moderador'
            ? ReportePago::where('id_moderador', $user->id_trab)
            : ReportePago::where('id_modelo', $user->id_trab);

        $reportes = $reportes->latest('fecha_reporte')->get();

        $stats = [
            'reportado' => $reportes->sum('precio'),
            'reportes' => $reportes->count(),
            'liquidado' => PagoEmpleado::where('id_trab', $user->id_trab)->sum('monto_final'),
        ];

        return view('dashboard', [
            'modoEmpleado' => $rol,
            'stats' => $stats,
            'reportes' => $reportes,
        ]);
    }

    /**
     * Inicio de los dueños de métodos de pago (rol `propietario`).
     *
     * Solo ve los ingresos de los métodos que tiene asignados: primero los
     * reportes SIN cierre (id_cierre NULL) y, si ya no queda ninguno, los del
     * último cierre en el que aparecen sus métodos. Además, sus propias
     * ganancias (pagos liquidados + lo que haya reportado como modelo/moderador).
     */
    private function propietarioDashboard(Trabajador $user)
    {
        $metodos = MetodoPago::where('id_propietario', $user->id_trab)->orderBy('metodo_pago')->get();
        $ids = $metodos->pluck('id_mp');

        $base = ReportePago::query()->whereIn('id_mp', $ids)->with('metodoPago');

        $haySinCerrar = (clone $base)->whereNull('id_cierre')->exists();

        if ($haySinCerrar) {
            $reportes = (clone $base)->whereNull('id_cierre');
            $periodo = 'Reportes sin cerrar';
            $cierre = null;
        } else {
            $ultimoCierre = (clone $base)->whereNotNull('id_cierre')->max('id_cierre');

            if ($ultimoCierre) {
                $reportes = (clone $base)->where('id_cierre', $ultimoCierre);
                $cierre = CierreSemanal::find($ultimoCierre);
                $periodo = 'Último cierre #'.$ultimoCierre;
                if ($cierre) {
                    $periodo .= ' ('.$cierre->fecha_inicio->format('d/m/Y').' - '.$cierre->fecha_fin->format('d/m/Y').')';
                }
            } else {
                $reportes = (clone $base);
                $periodo = 'Todos sus reportes';
                $cierre = null;
            }
        }

        $reportesColeccion = $reportes->with('metodoPago')->latest('fecha_reporte')->get();

        $ingresosPorMetodo = $metodos->map(fn (MetodoPago $metodo) => [
            'metodo_pago' => $metodo->metodo_pago,
            'propietario' => $metodo->propietario,
            'total' => (float) $reportesColeccion->where('id_mp', $metodo->id_mp)->sum('precio'),
            'reportes' => $reportesColeccion->where('id_mp', $metodo->id_mp)->count(),
            'porcentaje_cuenta' => $metodo->porcentaje_cuenta !== null ? (float) $metodo->porcentaje_cuenta : 0.0,
        ])->sortByDesc('total')->values();

        $gananciaPropietario = $ingresosPorMetodo->sum(function ($item) {
            $total = (float) ($item['total'] ?? 0);
            $pct = (float) ($item['porcentaje_cuenta'] ?? 0);

            return $total * ($pct / 100);
        });

        $stats = [
            'ingresos' => (float) $reportesColeccion->sum('precio'),
            'reportes' => $reportesColeccion->count(),
            'ganancia_propietario' => $gananciaPropietario,
        ];

        return view('dashboard', [
            'modoEmpleado' => null,
            'modoPropietario' => [
                'periodo' => $periodo,
                'metodos' => $metodos,
            ],
            'stats' => $stats,
            'ingresosPorMetodo' => $ingresosPorMetodo,
            'reportes' => $reportesColeccion->take(10),
        ]);
    }
}
