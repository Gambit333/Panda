<?php

namespace App\Http\Controllers;

use App\Models\Adelanto;
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
        $saldos = Adelanto::saldosPorTrabajador();

        return view('pagos.create', compact('trabajadores', 'cierres', 'saldos'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateData($request);

        $pago = PagoEmpleado::create($data);

        $this->aplicarAdelantos($pago, $data);

        return redirect()->route('pagos.index')->with('success', 'Pago a empleado registrado correctamente.');
    }

    public function edit(PagoEmpleado $pago): View
    {
        $trabajadores = Trabajador::orderBy('nombre')->get();
        $cierres = CierreSemanal::orderByDesc('fecha_fin')->get();
        $saldos = Adelanto::saldosPorTrabajador();
        $pago->load(['abonosAdelanto.adelanto']);

        return view('pagos.edit', compact('pago', 'trabajadores', 'cierres', 'saldos'));
    }

    public function update(Request $request, PagoEmpleado $pago): RedirectResponse
    {
        $data = $this->validateData($request);

        $pago->update($data);

        $this->aplicarAdelantos($pago->fresh(), $data);

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
            'monto_bruto' => ['required', 'numeric', 'min:0'],
            'monto_neto' => ['required', 'numeric', 'min:0'],
            'deuda' => ['nullable', 'numeric', 'min:0'],
            'monto_final' => ['required', 'numeric', 'min:0'],
            'nota' => ['nullable', 'string', 'max:255'],
            'aplicar_adelantos' => ['nullable', 'boolean'],
        ]);
    }

    /**
     * El saldo de adelantos/préstamos del trabajador se descuenta del pago.
     *
     * Si el pago marca "aplicar_adelantos", la deuda se registra como abonos
     * (FIFO: primero los adelantos más antiguos) para que el saldo de la sección
     * de adelantos quede siempre consistente con lo ya descontado. Al editar el
     * pago se deshacen primero los abonos que él mismo generó, así la operación
     * es idempotente y nunca se descuenta dos veces.
     */
    private function aplicarAdelantos(PagoEmpleado $pago, array $data): void
    {
        $pago->abonosAdelanto()->delete();

        $deuda = round((float) ($data['deuda'] ?? 0), 2);

        if ($deuda <= 0 || ! (bool) ($data['aplicar_adelantos'] ?? false)) {
            return;
        }

        $adelantos = Adelanto::withSum('abonos', 'monto')
            ->where('id_trab', $pago->id_trab)
            ->orderBy('fecha')
            ->orderBy('id_adelanto')
            ->get()
            ->filter(fn (Adelanto $adelanto) => $adelanto->saldo > 0);

        $nota = 'Descuento de adelantos aplicado en este pago';

        foreach ($adelantos as $adelanto) {
            if ($deuda <= 0) {
                break;
            }

            $monto = min($deuda, $adelanto->saldo);
            $adelanto->abonos()->create([
                'id_pago' => $pago->id_pago,
                'monto' => $monto,
                'fecha' => now()->toDateString(),
                'nota' => $nota,
            ]);

            $deuda = round($deuda - $monto, 2);
        }
    }
}
