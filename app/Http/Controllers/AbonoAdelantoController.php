<?php

namespace App\Http\Controllers;

use App\Models\AbonoAdelanto;
use App\Models\Adelanto;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AbonoAdelantoController extends Controller
{
    public function store(Request $request, Adelanto $adelanto): RedirectResponse
    {
        $saldo = $adelanto->saldo;

        if ($saldo <= 0) {
            return back()->withErrors(['monto' => 'Este adelanto ya está saldado.']);
        }

        $data = $request->validate([
            'monto' => ['required', 'numeric', 'min:0.01', 'max:'.$saldo],
            'fecha' => ['required', 'date'],
            'nota' => ['nullable', 'string', 'max:255'],
        ], [
            'monto.max' => 'El abono no puede ser mayor al saldo pendiente ($'.$saldo.').',
        ]);

        $adelanto->abonos()->create($data);

        return back()->with('success', 'Abono registrado correctamente.');
    }

    public function destroy(AbonoAdelanto $abono): RedirectResponse
    {
        $abono->delete();

        return back()->with('success', 'Abono eliminado correctamente.');
    }
}
