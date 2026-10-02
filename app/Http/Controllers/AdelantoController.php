<?php

namespace App\Http\Controllers;

use App\Models\AbonoAdelanto;
use App\Models\Adelanto;
use App\Models\Trabajador;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdelantoController extends Controller
{
    public function index(): View
    {
        $adelantos = Adelanto::with('trabajador')
            ->withSum('abonos', 'monto')
            ->orderByDesc('fecha')
            ->orderByDesc('id_adelanto')
            ->paginate(15);

        $total = round((float) Adelanto::sum('monto'), 2);
        $pagado = round((float) AbonoAdelanto::sum('monto'), 2);
        $deudas = $this->deudasPorTrabajador();

        return view('adelantos.index', compact('adelantos', 'total', 'pagado', 'deudas'));
    }

    public function create(): View
    {
        $trabajadores = $this->trabajadores();

        return view('adelantos.create', compact('trabajadores'));
    }

    public function store(Request $request): RedirectResponse
    {
        Adelanto::create($this->validateData($request));

        return redirect()->route('adelantos.index')
            ->with('success', 'Adelanto / préstamo registrado correctamente.');
    }

    public function edit(Adelanto $adelanto): View
    {
        $trabajadores = $this->trabajadores();
        $adelanto->load(['trabajador', 'abonos' => fn ($q) => $q->orderByDesc('fecha')]);

        return view('adelantos.edit', compact('adelanto', 'trabajadores'));
    }

    public function update(Request $request, Adelanto $adelanto): RedirectResponse
    {
        $adelanto->update($this->validateData($request, $adelanto));

        return redirect()->route('adelantos.index')
            ->with('success', 'Adelanto / préstamo actualizado correctamente.');
    }

    public function destroy(Adelanto $adelanto): RedirectResponse
    {
        $adelanto->delete();

        return redirect()->route('adelantos.index')
            ->with('success', 'Adelanto / préstamo eliminado correctamente.');
    }

    private function validateData(Request $request, ?Adelanto $adelanto = null): array
    {
        $data = $request->validate([
            'id_trab' => ['required', 'integer', 'exists:trabajador,id_trab'],
            'tipo' => ['required', Rule::in(Adelanto::TIPOS)],
            'monto' => ['required', 'numeric', 'min:0.01'],
            'fecha' => ['required', 'date'],
            'nota' => ['nullable', 'string', 'max:255'],
        ]);

        // El saldo pendiente no puede quedar negativo por editar el monto del adelanto.
        if ($adelanto && isset($data['monto'])) {
            $abonado = $adelanto->abonos()->sum('monto');
            $data['monto'] = max((float) $data['monto'], round((float) $abonado, 2));
        }

        return $data;
    }

    private function trabajadores()
    {
        return Trabajador::orderBy('nombre')->orderBy('apellido')->get();
    }

    /**
     * Saldo pendiente agrupado por trabajador.
     *
     * @return array<int, array{trabajador: ?Trabajador, cantidad: int, total: float, pagado: float, saldo: float}>
     */
    private function deudasPorTrabajador(): array
    {
        $cantidad = Adelanto::query()
            ->selectRaw('id_trab, COUNT(*) as cantidad')
            ->groupBy('id_trab')
            ->pluck('cantidad', 'id_trab');

        $saldos = Adelanto::saldosPorTrabajador();

        if ($saldos === []) {
            return [];
        }

        $trabajadores = Trabajador::whereIn('id_trab', array_keys($saldos))->get()->keyBy('id_trab');

        $filas = [];
        foreach ($saldos as $idTrab => $saldo) {
            $total = round((float) Adelanto::where('id_trab', $idTrab)->sum('monto'), 2);

            $filas[] = [
                'trabajador' => $trabajadores[$idTrab] ?? null,
                'cantidad' => (int) ($cantidad[$idTrab] ?? 0),
                'total' => $total,
                'pagado' => round($total - $saldo, 2),
                'saldo' => $saldo,
            ];
        }

        usort($filas, fn ($a, $b) => $b['saldo'] <=> $a['saldo']);

        return $filas;
    }
}
