<?php

namespace App\Http\Controllers;

use App\Models\MetodoPago;
use App\Models\Trabajador;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class MetodoPagoController extends Controller
{
    public function index(): View
    {
        $metodos = MetodoPago::withCount('reportes')->with('dueno')->orderBy('id_mp')->get();

        return view('metodos.index', compact('metodos'));
    }

    public function create(): View
    {
        return view('metodos.create', ['propietarios' => $this->propietarios()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateData($request);

        MetodoPago::create($data);

        return redirect()->route('metodos.index')->with('success', 'Método de pago creado correctamente.');
    }

    public function edit(MetodoPago $metodo): View
    {
        return view('metodos.edit', [
            'metodo' => $metodo,
            'propietarios' => $this->propietarios(),
        ]);
    }

    public function update(Request $request, MetodoPago $metodo): RedirectResponse
    {
        $data = $this->validateData($request);

        $metodo->update($data);

        return redirect()->route('metodos.index')->with('success', 'Método de pago actualizado correctamente.');
    }

    public function destroy(MetodoPago $metodo): RedirectResponse
    {
        $metodo->delete();

        return redirect()->route('metodos.index')->with('success', 'Método de pago eliminado correctamente.');
    }

    /** Trabajadores con rol propietario: los únicos que pueden ser dueños de un método. */
    private function propietarios(): Collection
    {
        return Trabajador::query()
            ->whereHas('rol', fn (Builder $query) => $query->whereRaw('lower(rol) = ?', ['propietario']))
            ->orderBy('nombre')
            ->orderBy('apellido')
            ->get();
    }

    private function validateData(Request $request): array
    {
        $data = $request->validate([
            'propietario' => ['nullable', 'string', 'max:255'],
            'id_propietario' => ['nullable', 'integer', 'exists:trabajador,id_trab'],
            'metodo_pago' => ['required', 'string', 'max:255'],
            'porcentaje_cuenta' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);

        if (blank($data['propietario']) && ! empty($data['id_propietario'])) {
            $dueno = Trabajador::find($data['id_propietario']);

            if ($dueno) {
                $data['propietario'] = $dueno->nombre_completo;
            }
        }

        if (blank($data['propietario'])) {
            $data['propietario'] = $request->input('metodo_pago') ?: 'Sin titular';
        }

        return $data;
    }
}
