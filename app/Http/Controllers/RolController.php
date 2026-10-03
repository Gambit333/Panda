<?php

namespace App\Http\Controllers;

use App\Models\Rol;
use App\Support\Permisos;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class RolController extends Controller
{
    public function index(): View
    {
        $roles = Rol::withCount('trabajadores')->orderBy('id_rol')->get();

        return view('roles.index', compact('roles'));
    }

    public function create(): View
    {
        return view('roles.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateData($request);

        Rol::create($data);

        return redirect()->route('roles.index')->with('success', 'Rol creado correctamente.');
    }

    public function edit(Rol $rol): View
    {
        return view('roles.edit', compact('rol'));
    }

    public function update(Request $request, Rol $rol): RedirectResponse
    {
        $data = $this->validateData($request, $rol);

        $rol->update($data);

        return redirect()->route('roles.index')->with('success', 'Rol actualizado correctamente.');
    }

    public function destroy(Rol $rol): RedirectResponse
    {
        $rol->delete();

        return redirect()->route('roles.index')->with('success', 'Rol eliminado correctamente.');
    }

    private function validateData(Request $request, ?Rol $rol = null): array
    {
        $data = $request->validate([
            'rol' => ['required', 'string', 'max:255', Rule::unique('roles', 'rol')->ignore($rol?->id_rol, 'id_rol')],
            'permisos' => ['nullable', 'array'],
            // El primer elemento es el campo oculto que permite dejar la lista vacía
            // (Laravel lo convierte a null y nullable lo deja pasar).
            'permisos.*' => ['nullable', 'string', Rule::in(Permisos::todos())],
        ]);

        // admin y programador siempre entran a todo: sus permisos no se pueden editar.
        if (Permisos::esBloqueado($data['rol'])) {
            $data['permisos'] = null;

            return $data;
        }

        // Sin el campo (formulario antiguo) se deja el valor actual: el rol usará
        // su acceso por defecto si nunca se han guardado permisos.
        if (! array_key_exists('permisos', $data)) {
            $data['permisos'] = $rol?->permisos;

            return $data;
        }

        $data['permisos'] = array_values(array_intersect(Permisos::todos(), (array) $data['permisos']));

        return $data;
    }
}
