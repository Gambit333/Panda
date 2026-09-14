<?php

namespace App\Http\Controllers;

use App\Models\CierreSemanal;
use App\Models\PagoEmpleado;
use App\Models\Trabajador;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PagoEmpleadoController extends Controller
{
    public function index(): View
    {
        $pagos = PagoEmpleado::with(['trabajador', 'cierreSemanal'])
            ->orderByDesc('id_pago')
            ->paginate(15);

        return view('pagos.index', compact('pagos'));
    }

    public function create(): View
    {
        $trabajadores = Trabajador::orderBy('nombre')->get();
        $cierres = CierreSemanal::orderByDesc('fecha_fin')->get();

        return view('pagos.create', compact('trabajadores', 'cierres'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateData($request);

        PagoEmpleado::create($data);

        return redirect()->route('pagos.index')->with('success', 'Pago a empleado registrado correctamente.');
    }

    public function edit(PagoEmpleado $pago): View
    {
        $trabajadores = Trabajador::orderBy('nombre')->get();
        $cierres = CierreSemanal::orderByDesc('fecha_fin')->get();

        return view('pagos.edit', compact('pago', 'trabajadores', 'cierres'));
    }

    public function update(Request $request, PagoEmpleado $pago): RedirectResponse
    {
        $data = $this->validateData($request);

        $pago->update($data);

        return redirect()->route('pagos.index')->with('success', 'Pago a empleado actualizado correctamente.');
    }

    public function destroy(PagoEmpleado $pago): RedirectResponse
    {
        $pago->delete();

        return redirect()->route('pagos.index')->with('success', 'Pago a empleado eliminado correctamente.');
    }

    private function validateData(Request $request): array
    {
        return $request->validate([
            'id_trab' => ['required', 'integer', 'exists:trabajador,id_trab'],
            'id_cierre' => ['required', 'integer', 'exists:cierre_semanal,id_cierre'],
            'monto' => ['required', 'numeric', 'min:0'],
        ]);
    }
}
