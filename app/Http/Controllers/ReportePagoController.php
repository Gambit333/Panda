<?php

namespace App\Http\Controllers;

use App\Models\CierreSemanal;
use App\Models\MetodoPago;
use App\Models\ReportePago;
use App\Models\Trabajador;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ReportePagoController extends Controller
{
    public function index(): View
    {
        $reportes = ReportePago::with(['modelo', 'moderador', 'metodoPago', 'cierreSemanal'])
            ->orderByDesc('fecha_reporte')
            ->paginate(15);

        return view('reportes.index', compact('reportes'));
    }

    public function create(): View
    {
        $trabajadores = Trabajador::orderBy('nombre')->get();
        $modelos = $this->trabajadoresPorRol(['modelo', 'ceo']);
        $metodosPago = MetodoPago::all();
        $cierres = CierreSemanal::orderByDesc('fecha_fin')->get();

        return view('reportes.create', compact('trabajadores', 'modelos', 'metodosPago', 'cierres'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateData($request);

        ReportePago::create($data);

        return redirect()->route('reportes.index')->with('success', 'Reporte de pago creado correctamente.');
    }

    public function edit(ReportePago $reporte): View
    {
        $trabajadores = Trabajador::orderBy('nombre')->get();
        $modelos = $this->trabajadoresPorRol(['modelo', 'ceo']);

        if (! $modelos->contains('id_trab', $reporte->id_modelo)) {
            $modelos = $modelos->concat([$reporte->modelo])->unique('id_trab');
        }

        $metodosPago = MetodoPago::all();
        $cierres = CierreSemanal::orderByDesc('fecha_fin')->get();

        return view('reportes.edit', compact('reporte', 'trabajadores', 'modelos', 'metodosPago', 'cierres'));
    }

    public function update(Request $request, ReportePago $reporte): RedirectResponse
    {
        $data = $this->validateData($request);

        $reporte->update($data);

        return redirect()->route('reportes.index')->with('success', 'Reporte de pago actualizado correctamente.');
    }

    public function destroy(ReportePago $reporte): RedirectResponse
    {
        $reporte->delete();

        return redirect()->route('reportes.index')->with('success', 'Reporte de pago eliminado correctamente.');
    }

    private function trabajadoresPorRol(array $roles)
    {
        $roles = array_map('strtolower', $roles);

        return Trabajador::whereHas('rol', fn ($query) => $query->whereIn(DB::raw('lower(rol)'), $roles))
            ->orderBy('nombre')
            ->get();
    }

    private function validateData(Request $request): array
    {
        return $request->validate([
            'id_modelo' => ['required', 'integer', 'exists:trabajador,id_trab'],
            'plataforma' => ['required', 'string', 'max:255'],
            'user_cliente' => ['required', 'string', 'max:255'],
            'id_mp' => ['required', 'integer', 'exists:metodos_pago,id_mp'],
            'precio' => ['required', 'numeric', 'min:0'],
            'servicio' => ['required', 'string'],
            'duracion' => ['nullable', 'string', 'max:255'],
            'fecha_reporte' => ['nullable', 'date'],
            'id_moderador' => ['required', 'integer', 'exists:trabajador,id_trab'],
            'id_cierre' => ['nullable', 'integer', 'exists:cierre_semanal,id_cierre'],
            'descripcion' => ['nullable', 'string'],
        ]);
    }
}
