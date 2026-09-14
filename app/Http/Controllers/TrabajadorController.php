<?php

namespace App\Http\Controllers;

use App\Models\Rol;
use App\Models\Trabajador;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TrabajadorController extends Controller
{
    public function index(): View
    {
        $trabajadores = Trabajador::with('rol')->orderBy('id_trab')->paginate(15);

        return view('trabajadores.index', compact('trabajadores'));
    }

    public function create(): View
    {
        $roles = Rol::all();

        return view('trabajadores.create', compact('roles'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateData($request);

        Trabajador::create($data);

        return redirect()->route('trabajadores.index')->with('success', 'Trabajador creado correctamente.');
    }

    public function edit(Trabajador $trabajador): View
    {
        $roles = Rol::all();

        return view('trabajadores.edit', compact('trabajador', 'roles'));
    }

    public function update(Request $request, Trabajador $trabajador): RedirectResponse
    {
        $data = $this->validateData($request, $trabajador->id_trab);

        $trabajador->update($data);

        return redirect()->route('trabajadores.index')->with('success', 'Trabajador actualizado correctamente.');
    }

    public function destroy(Trabajador $trabajador): RedirectResponse
    {
        $trabajador->delete();

        return redirect()->route('trabajadores.index')->with('success', 'Trabajador eliminado correctamente.');
    }

    private function validateData(Request $request, ?int $id = null): array
    {
        return $request->validate([
            'nombre' => ['required', 'string', 'max:255'],
            'apellido' => ['required', 'string', 'max:255'],
            'telefono' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('trabajador', 'email')->ignore($id, 'id_trab')],
            'direccion' => ['nullable', 'string'],
            'id_rol' => ['required', 'integer', 'exists:roles,id_rol'],
        ]);
    }
}
