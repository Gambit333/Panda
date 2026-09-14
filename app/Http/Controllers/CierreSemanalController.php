<?php

namespace App\Http\Controllers;

use App\Models\CierreSemanal;
use App\Models\ReportePago;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CierreSemanalController extends Controller
{
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

        $reportes = ReportePago::whereNull('id_cierre')
            ->whereBetween('fecha_reporte', [$data['fecha_inicio'], $data['fecha_fin']])
            ->get();

        $total = $reportes->sum('precio');

        $cierre->reportes()->saveMany($reportes);
        $cierre->update(['total' => $total]);

        return redirect()
            ->route('cierres.show', $cierre)
            ->with('success', "Cierre generado: {$reportes->count()} reportes asignados.");
    }

    public function show(CierreSemanal $cierre): View
    {
        $cierre->load(['reportes.modelo', 'reportes.moderador', 'reportes.metodoPago', 'pagosEmpleados.trabajador']);

        return view('cierres.show', compact('cierre'));
    }

    public function destroy(CierreSemanal $cierre): RedirectResponse
    {
        ReportePago::where('id_cierre', $cierre->id_cierre)->update(['id_cierre' => null]);
        $cierre->pagosEmpleados()->delete();
        $cierre->delete();

        return redirect()->route('cierres.index')->with('success', 'Cierre eliminado correctamente.');
    }
}
