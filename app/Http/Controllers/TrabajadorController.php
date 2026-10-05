<?php

namespace App\Http\Controllers;

use App\Models\MetodoPago;
use App\Models\Rol;
use App\Models\Trabajador;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
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
        $metodosSinDueno = $this->metodosSinDueno();

        return view('trabajadores.create', compact('roles', 'metodosSinDueno'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateData($request);

        $trabajador = Trabajador::create($data);

        $this->sincronizarMetodos($trabajador, $request->input('metodos_pago', []));

        return redirect()->route('trabajadores.index')->with('success', 'Trabajador creado correctamente.');
    }

    public function edit(Trabajador $trabajador): View
    {
        $roles = Rol::all();
        $metodosSinDueno = $this->metodosSinDueno();
        $metodosAsignados = $this->metodosAsignados($trabajador);

        return view('trabajadores.edit', compact('trabajador', 'roles', 'metodosSinDueno', 'metodosAsignados'));
    }

    public function update(Request $request, Trabajador $trabajador): RedirectResponse
    {
        $data = $this->validateData($request, $trabajador->id_trab);

        $trabajador->update($data);

        $this->sincronizarMetodos($trabajador, $request->input('metodos_pago', []));

        return redirect()->route('trabajadores.index')->with('success', 'Trabajador actualizado correctamente.');
    }

    public function destroy(Trabajador $trabajador): RedirectResponse
    {
        if ($trabajador->id_trab === auth()->id()) {
            return back()->withErrors(['trabajador' => 'No puedes eliminar tu propio usuario.']);
        }

        // pago_empleados y reporte_pagos son ON DELETE RESTRICT: si hay historial
        // de nómina la BD rechaza el borrado, así que se avisa en vez de fallar.
        $detalles = [];

        $pagos = $trabajador->pagosEmpleado()->count();
        $reportes = $trabajador->reportesComoModelo()->count() + $trabajador->reportesComoModerador()->count();

        if ($pagos > 0) {
            $detalles[] = $pagos.' '.($pagos === 1 ? 'pago a empleado' : 'pagos a empleados');
        }

        if ($reportes > 0) {
            $detalles[] = $reportes.' '.($reportes === 1 ? 'reporte de pago' : 'reportes de pago');
        }

        if ($detalles !== []) {
            return back()->withErrors([
                'trabajador' => 'No se puede eliminar a '.$trabajador->nombre.' '.$trabajador->apellido
                    .' porque tiene '.implode(' y ', $detalles).' registrados. '
                    .'Si solo dejó de trabajar, edita sus datos en lugar de eliminarlo.',
            ]);
        }

        $trabajador->delete();

        return redirect()->route('trabajadores.index')->with('success', 'Trabajador eliminado correctamente.');
    }

    /**
     * Elimina la contraseña de una cuenta (solo programadores, middleware
     * 'programador'). El usuario podrá crear una nueva en su próximo ingreso,
     * porque la pantalla de primer ingreso aparece cuando `password` está vacío.
     */
    public function eliminarPassword(Trabajador $trabajador): RedirectResponse
    {
        $tenia = $trabajador->tienePassword();

        $trabajador->password = null;
        // desbloquear() guarda el cambio y limpia el bloqueo por intentos.
        $trabajador->desbloquear();

        $nombre = $trabajador->nombre.' '.$trabajador->apellido;

        return redirect()->route('trabajadores.index')->with('success', $tenia
            ? 'Contraseña de '.$nombre.' eliminada: podrá crear una nueva en su próximo ingreso.'
            : $nombre.' no tenía contraseña. Su cuenta quedó desbloqueada (0 intentos fallidos).');
    }

    /** Quita el bloqueo por intentos fallidos (solo programadores). */
    public function desbloquear(Trabajador $trabajador): RedirectResponse
    {
        $trabajador->desbloquear();

        return redirect()->route('trabajadores.index')
            ->with('success', $trabajador->nombre.' '.$trabajador->apellido.' quedó desbloqueado (0 intentos fallidos).');
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

    private function metodosSinDueno(): Collection
    {
        return MetodoPago::query()
            ->whereNull('id_propietario')
            ->orderBy('metodo_pago')
            ->get();
    }

    private function metodosAsignados(Trabajador $trabajador): Collection
    {
        return MetodoPago::query()
            ->where('id_propietario', $trabajador->id_trab)
            ->orderBy('metodo_pago')
            ->get();
    }

    private function sincronizarMetodos(Trabajador $trabajador, array $seleccionados): void
    {
        $rol = strtolower((string) ($trabajador->rol?->rol ?? ''));
        $seleccionados = collect($seleccionados)->filter()->map(fn ($id) => (int) $id)->all();

        if ($rol !== 'propietario') {
            MetodoPago::where('id_propietario', $trabajador->id_trab)
                ->update(['id_propietario' => null]);

            return;
        }

        MetodoPago::where('id_propietario', $trabajador->id_trab)
            ->update(['id_propietario' => null]);

        if ($seleccionados === []) {
            return;
        }

        MetodoPago::whereIn('id_mp', $seleccionados)
            ->update(['id_propietario' => $trabajador->id_trab]);
    }
}
