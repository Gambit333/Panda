<?php

namespace App\Http\Controllers;

use App\Models\CierreSemanal;
use App\Models\PagoEmpleado;
use App\Models\ReportePago;
use App\Models\Trabajador;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $rol = strtolower((string) ($user->rol?->rol ?? ''));

        if (in_array($rol, ['moderador', 'modelo'], true)) {
            return $this->workerDashboard($user, $rol);
        }

        $stats = [
            'trabajadores' => Trabajador::count(),
            'reportes' => ReportePago::count(),
            'ingresos' => ReportePago::sum('precio'),
            'cierres' => CierreSemanal::count(),
            'pagos_empleados' => PagoEmpleado::sum('monto'),
        ];

        $ingresosPorMetodo = DB::table('reporte_pagos')
            ->join('metodos_pago', 'reporte_pagos.id_mp', '=', 'metodos_pago.id_mp')
            ->select('metodos_pago.metodo_pago', DB::raw('SUM(reporte_pagos.precio) as total'))
            ->groupBy('metodos_pago.metodo_pago')
            ->orderByDesc('total')
            ->get();

        $ultimosReportes = ReportePago::with(['modelo', 'moderador', 'metodoPago'])
            ->latest('fecha_reporte')
            ->limit(10)
            ->get();

        $ultimosCierres = CierreSemanal::withCount('reportes')->latest('fecha_fin')->limit(5)->get();

        return view('dashboard', [
            'modoEmpleado' => null,
            'stats' => $stats,
            'ingresosPorMetodo' => $ingresosPorMetodo,
            'ultimosReportes' => $ultimosReportes,
            'ultimosCierres' => $ultimosCierres,
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
            'liquidado' => PagoEmpleado::where('id_trab', $user->id_trab)->sum('monto'),
        ];

        return view('dashboard', [
            'modoEmpleado' => $rol,
            'stats' => $stats,
            'reportes' => $reportes,
        ]);
    }
}