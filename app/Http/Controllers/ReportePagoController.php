<?php

namespace App\Http\Controllers;

use App\Models\CierreSemanal;
use App\Models\MetodoPago;
use App\Models\ReportePago;
use App\Models\Trabajador;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReportePagoController extends Controller
{
    public function index(): View
    {
        $reportes = ReportePago::with(['modelo', 'moderador', 'metodoPago', 'cierreSemanal'])
            ->orderByDesc('fecha_reporte')
            ->paginate(15);

        return view('reportes.index', compact('reportes'));
    }

    public function create(): View
    {
        $trabajadores = Trabajador::orderBy('nombre')->get();
        $metodosPago = MetodoPago::all();
        $cierres = CierreSemanal::orderByDesc('fecha_fin')->get();

        return view('reportes.create', compact('trabajadores', 'metodosPago', 'cierres'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateData($request);

        ReportePago::create($data);

        return redirect()->route('reportes.index')->with('success', 'Reporte de pago creado correctamente.');
    }

    public function edit(ReportePago $reporte): View
    {
        $trabajadores = Trabajador::orderBy('nombre')->get();
        $metodosPago = MetodoPago::all();
        $cierres = CierreSemanal::orderByDesc('fecha_fin')->get();

        return view('reportes.edit', compact('reporte', 'trabajadores', 'metodosPago', 'cierres'));
    }

    public function update(Request $request, ReportePago $reporte): RedirectResponse
    {
        $data = $this->validateData($request);

        $reporte->update($data);

        return redirect()->route('reportes.index')->with('success', 'Reporte de pago actualizado correctamente.');
    }

    public function destroy(ReportePago $reporte): RedirectResponse
    {
        $reporte->delete();

        return redirect()->route('reportes.index')->with('success', 'Reporte de pago eliminado correctamente.');
    }

    private function validateData(Request $request): array
    {
        return $request->validate([
            'id_modelo' => ['required', 'integer', 'exists:trabajador,id_trab'],
            'plataforma' => ['required', 'string', 'max:255'],
            'user_cliente' => ['required', 'string', 'max:255'],
            'id_mp' => ['required', 'integer', 'exists:metodos_pago,id_mp'],
            'precio' => ['required', 'numeric', 'min:0'],
            'servicio' => ['required', 'string'],
            'duracion' => ['nullable', 'string', 'max:255'],
            'fecha_reporte' => ['nullable', 'date'],
            'id_moderador' => ['required', 'integer', 'exists:trabajador,id_trab'],
            'id_cierre' => ['nullable', 'integer', 'exists:cierre_semanal,id_cierre'],
            'descripcion' => ['nullable', 'string'],
        ]);
    }
}
