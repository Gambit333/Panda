<?php

namespace App\Http\Controllers;

use App\Models\CierreSemanal;
use App\Models\PagoEmpleado;
use App\Models\ReportePago;
use App\Models\Trabajador;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'trabajadores' => Trabajador::count(),
            'reportes' => ReportePago::count(),
            'ingresos' => ReportePago::sum('precio'),
            'cierres' => CierreSemanal::count(),
            'pagos_empleados' => PagoEmpleado::sum('monto'),
        ];

        $ingresosPorMetodo = DB::table('reporte_pagos')
            ->join('metodos_pago', 'reporte_pagos.id_mp', '=', 'metodos_pago.id_mp')
            ->select('metodos_pago.metodo_pago', DB::raw('SUM(reporte_pagos.precio) as total'))
            ->groupBy('metodos_pago.metodo_pago')
            ->orderByDesc('total')
            ->get();

        $ultimosReportes = ReportePago::with(['modelo', 'moderador', 'metodoPago'])
            ->latest('fecha_reporte')
            ->limit(10)
            ->get();

        $ultimosCierres = CierreSemanal::withCount('reportes')->latest('fecha_fin')->limit(5)->get();

        return view('dashboard', compact('stats', 'ingresosPorMetodo', 'ultimosReportes', 'ultimosCierres'));
    }
}
