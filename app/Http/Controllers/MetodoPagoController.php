<?php

namespace App\Http\Controllers;

use App\Models\MetodoPago;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MetodoPagoController extends Controller
{
    public function index(): View
    {
        $metodos = MetodoPago::withCount('reportes')->orderBy('id_mp')->get();

        return view('metodos.index', compact('metodos'));
    }

    public function create(): View
    {
        return view('metodos.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateData($request);

        MetodoPago::create($data);

        return redirect()->route('metodos.index')->with('success', 'Método de pago creado correctamente.');
    }

    public function edit(MetodoPago $metodo): View
    {
        return view('metodos.edit', compact('metodo'));
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

    private function validateData(Request $request): array
    {
        return $request->validate([
            'metodo_pago' => ['required', 'string', 'max:255'],
            'impuesto' => ['nullable', 'numeric', 'min:0'],
            'porcentaje_cuenta' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);
    }
}
