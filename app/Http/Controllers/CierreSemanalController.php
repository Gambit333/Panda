<?php

namespace App\Http\Controllers;

use App\Models\CierreSemanal;
use App\Models\ReportePago;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CierreSemanalController extends Controller
{
    private const IMPUESTO_PORCENTAJE = 15;

    public function index(): View
    {
        $cierres = CierreSemanal::withCount(['reportes', 'pagosEmpleados'])
            ->orderByDesc('fecha_fin')
            ->paginate(15);

        return view('cierres.index', compact('cierres'));
    }

    public function create(): View
    {
        return view('cierres.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'fecha_inicio' => ['required', 'date'],
            'fecha_fin' => ['required', 'date', 'after_or_equal:fecha_inicio'],
        ]);

        $cierre = CierreSemanal::create($data);

        $reportes = ReportePago::with('metodoPago')
            ->whereNull('id_cierre')
            ->whereBetween('fecha_reporte', [$data['fecha_inicio'], $data['fecha_fin']])
            ->get();

        $totalBruto = $reportes->sum('precio');

        // Cálculo del neto restando la tasa/porcentaje asociado al método de pago
        $totalNeto = $reportes->sum(function ($r) {
            $porcentaje = $r->metodoPago?->porcentaje_cuenta ?? 0;

            return $r->precio * (1 - ($porcentaje / 100));
        });

        $cierre->reportes()->saveMany($reportes);
        $cierre->update([
            'total_bruto' => $totalBruto,
            'total_neto' => $totalNeto,
        ]);

        return redirect()
            ->route('cierres.show', $cierre)
            ->with('success', "Cierre generado: {$reportes->count()} reportes asignados.");
    }

    public function show(CierreSemanal $cierre): View
    {
        $cierre->load(['reportes.modelo', 'reportes.moderador', 'reportes.metodoPago', 'pagosEmpleados.trabajador']);

        $calculos = $this->calcularComisiones($cierre);

        return view('cierres.show', compact('cierre', 'calculos'));
    }

    /**
     * Desglose de comisiones por cuenta de pago:
     * por método se agrupan los reportes, se aplica el porcentaje_cuenta (comisión),
     * y del impuesto global (15% del total facturado) se paga la comisión total;
     * el resto es lo que queda para Brea.
     */
    private function calcularComisiones(CierreSemanal $cierre): array
    {
        $grupos = $cierre->reportes
            ->groupBy('id_mp')
            ->map(function ($reportes) {
                $metodo = $reportes->first()->metodoPago;
                $bruto = (float) $reportes->sum('precio');
                $porcentaje = (float) ($metodo?->porcentaje_cuenta ?? 0);
                $comision = $bruto * ($porcentaje / 100);

                return [
                    'id_mp' => (int) $reportes->first()->id_mp,
                    'metodo' => $metodo?->metodo_pago ?? 'Sin método',
                    'propietario' => $metodo?->propietario,
                    'porcentaje' => $porcentaje,
                    'bruto' => round($bruto, 2),
                    'comision' => round($comision, 2),
                    'neto' => round($bruto - $comision, 2),
                ];
            })
            ->values()
            ->all();

        $totalFacturado = (float) array_sum(array_column($grupos, 'bruto'));
        $totalImpuestos = $totalFacturado * (self::IMPUESTO_PORCENTAJE / 100);
        $comisionTotal = (float) array_sum(array_column($grupos, 'comision'));

        return [
            'impuesto_porcentaje' => self::IMPUESTO_PORCENTAJE,
            'grupos' => $grupos,
            'total_facturado' => round($totalFacturado, 2),
            'total_con_impuestos' => round($totalFacturado - $totalImpuestos, 2),
            'total_impuestos' => round($totalImpuestos, 2),
            'comision_total' => round($comisionTotal, 2),
            'resto_brea' => round($totalImpuestos - $comisionTotal, 2),
        ];
    }

    public function destroy(CierreSemanal $cierre): RedirectResponse
    {
        ReportePago::where('id_cierre', $cierre->id_cierre)->update(['id_cierre' => null]);
        $cierre->pagosEmpleados()->delete();
        $cierre->delete();

        return redirect()->route('cierres.index')->with('success', 'Cierre eliminado correctamente.');
    }
}
