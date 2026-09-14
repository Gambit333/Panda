<?php

namespace App\Http\Controllers;

use App\Models\Rol;
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
        $data = $this->validateData($request, $rol->id_rol);

        $rol->update($data);

        return redirect()->route('roles.index')->with('success', 'Rol actualizado correctamente.');
    }

    public function destroy(Rol $rol): RedirectResponse
    {
        $rol->delete();

        return redirect()->route('roles.index')->with('success', 'Rol eliminado correctamente.');
    }

    private function validateData(Request $request, ?int $id = null): array
    {
        return $request->validate([
            'rol' => ['required', 'string', 'max:255', Rule::unique('roles', 'rol')->ignore($id, 'id_rol')],
        ]);
    }
}
