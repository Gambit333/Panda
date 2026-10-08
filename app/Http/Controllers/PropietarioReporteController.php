<?php

namespace App\Http\Controllers;

use App\Models\MetodoPago;
use App\Models\ReportePago;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Sección de los dueños de métodos de pago (rol `propietario`): ven los
 * reportes de las cuentas que tienen asignadas, divididos por método de pago
 * y con solo tres columnas: fecha, precio y comprobante.
 */
class PropietarioReporteController extends Controller
{
    public function index(): View
    {
        $metodos = MetodoPago::where('id_propietario', Auth::id())->orderBy('metodo_pago')->get();

        $reportes = ReportePago::with('metodoPago', 'comprobanteBinario')
            ->whereIn('id_mp', $metodos->pluck('id_mp'))
            ->latest('fecha_reporte')
            ->get()
            ->groupBy('id_mp');

        $bloques = $metodos->map(function (MetodoPago $metodo) use ($reportes) {
            $delMetodo = $reportes->get($metodo->id_mp, collect());

            return [
                'metodo' => $metodo,
                'reportes' => $delMetodo,
                'total' => (float) $delMetodo->sum('precio'),
            ];
        });

        return view('propietario.reportes', ['bloques' => $bloques]);
    }
}
