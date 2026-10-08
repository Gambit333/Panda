<?php

namespace App\Http\Controllers;

use App\Models\Comprobante;
use App\Models\MetodoPago;
use App\Models\ReportePago;
use App\Models\Trabajador;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ReportePagoController extends Controller
{
    public function index(): View
    {
        $usuario = auth()->user();
        $soloMios = ! $usuario->esSuperRol();

        $query = ReportePago::with(['modelo', 'moderador', 'metodoPago', 'cierreSemanal', 'comprobanteBinario']);

        // Un moderador solo ve los reportes que él registró.
        if ($soloMios) {
            $query->where('id_moderador', $usuario->getAuthIdentifier());
        }

        $reportes = $query->orderByDesc('fecha_reporte')->paginate(15);

        return view('reportes.index', compact('reportes', 'soloMios'));
    }

    public function create()
    {
        $usuarioActual = auth()->user();
        $esAdmin = $usuarioActual->esSuperRol();

        $metodosPago = MetodoPago::all();

        if ($esAdmin) {
            $modelos = $this->trabajadoresPorRol(['modelo']);
            $moderadores = $this->trabajadoresPorRol(['moderador', 'chatter']);
        } else {
            // Moderador: Carga únicamente las modelos asignadas a él en la tabla pivote
            $modelos = $usuarioActual->modelosAsignadas;
            $moderadores = collect([$usuarioActual]);
        }

        return view('reportes.create', compact('modelos', 'moderadores', 'metodosPago', 'esAdmin', 'usuarioActual'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateData($request);

        if (! auth()->user()->esSuperRol()) {
            // El moderador siempre queda como moderador del reporte.
            $data['id_moderador'] = auth()->user()->getAuthIdentifier();
        }

        if ($request->hasFile('comprobante')) {
            $data['comprobante'] = $this->guardarComprobante($request->file('comprobante'));
        }

        $reporte = ReportePago::create($data);

        if ($request->hasFile('comprobante')) {
            $this->guardarComprobanteBD($reporte, $request->file('comprobante'));
        }

        return redirect()->route('reportes.index')->with('success', 'Reporte de pago creado correctamente.');
    }

    public function edit(ReportePago $reporte): View|RedirectResponse
    {
        if ($redirigir = $this->sinAcceso($reporte)) {
            return $redirigir;
        }

        $modelos = $this->trabajadoresPorRol(['modelo']);

        if (! $modelos->contains('id_trab', $reporte->id_modelo) && $reporte->modelo) {
            $modelos = $modelos->concat([$reporte->modelo])->unique('id_trab');
        }

        $moderadores = $this->trabajadoresPorRol(['moderador', 'chatter']);

        if ($reporte->comprobante) {
            $reporte->load('comprobanteBinario');
        }

        $metodosPago = MetodoPago::all();

        return view('reportes.edit', compact('reporte', 'modelos', 'moderadores', 'metodosPago'));
    }

    public function update(Request $request, ReportePago $reporte): RedirectResponse
    {
        if ($redirigir = $this->sinAcceso($reporte)) {
            return $redirigir;
        }

        $data = $this->validateData($request);

        if (! auth()->user()->esSuperRol()) {
            $data['id_moderador'] = $reporte->id_moderador;
        }

        if ($request->hasFile('comprobante')) {
            $this->eliminarComprobante($reporte);
            $data['comprobante'] = $this->guardarComprobante($request->file('comprobante'));
        } elseif ($request->boolean('eliminar_comprobante')) {
            $this->eliminarComprobante($reporte);
            $data['comprobante'] = null;
        }

        $reporte->update($data);

        if ($request->hasFile('comprobante')) {
            $this->guardarComprobanteBD($reporte, $request->file('comprobante'));
        }

        return redirect()->route('reportes.index')->with('success', 'Reporte de pago actualizado correctamente.');
    }

    public function destroy(ReportePago $reporte): RedirectResponse
    {
        if ($redirigir = $this->sinAcceso($reporte)) {
            return $redirigir;
        }

        $this->eliminarComprobante($reporte);
        $reporte->delete();

        return redirect()->route('reportes.index')->with('success', 'Reporte de pago eliminado correctamente.');
    }

    /**
     * Un moderador solo puede ver y modificar los reportes que él registró.
     * Los superroles (admin, ceo, support) acceden a todos.
     */
    private function sinAcceso(ReportePago $reporte): ?RedirectResponse
    {
        if (auth()->user()->esSuperRol() || (int) $reporte->id_moderador === (int) auth()->user()->getAuthIdentifier()) {
            return null;
        }

        return redirect()->route('reportes.index')
            ->withErrors(['access' => 'Ese reporte pertenece a otro moderador.']);
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
            'descripcion' => ['nullable', 'string'],
            'comprobante' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:4096'],
            'eliminar_comprobante' => ['sometimes', 'boolean'],
        ]);
    }

    /**
     * Guarda el comprobante en disco (storage/app/public/comprobantes) y devuelve
     * la ruta relativa que se guarda en `reporte_pagos.comprobante`. El binario
     * además se persiste en la BD (ver guardarComprobanteBD).
     */
    private function guardarComprobante(UploadedFile $archivo): string
    {
        $comp = $this->binarioComprobante($archivo);

        if ($comp['comprimido']) {
            $nombre = 'comprobantes/'.$archivo->hashName().'.jpg';
            Storage::disk('public')->put($nombre, $comp['binario']);

            return $nombre;
        }

        return $archivo->store('comprobantes', 'public');
    }

    /**
     * Persiste una copia del comprobante en la BD (tabla `comprobantes`) para
     * que sobreviva a los deploys con storage efímero. Si ya existía una copia
     * para el reporte se reemplaza.
     */
    private function guardarComprobanteBD(ReportePago $reporte, UploadedFile $archivo): void
    {
        $comp = $this->binarioComprobante($archivo);

        Comprobante::updateOrCreate(
            ['id_reporte' => $reporte->id_reporte],
            [
                'mime' => $comp['mime'],
                'tamano' => strlen($comp['binario']),
                'imagen' => base64_encode($comp['binario']),
            ]
        );
    }

    /**
     * Binario listo para guardar: JPEG comprimido (si GD está disponible) o el
     * archivo original tal cual.
     *
     * @return array{binario: string, mime: string, comprimido: bool}
     */
    private function binarioComprobante(UploadedFile $archivo): array
    {
        if (function_exists('gd_info')) {
            $jpeg = $this->comprimirAJPEG($archivo);
            if ($jpeg !== null) {
                return $jpeg;
            }
        }

        return [
            'binario' => (string) $archivo->get(),
            'mime' => $archivo->getClientMimeType() ?: 'image/jpeg',
            'comprimido' => false,
        ];
    }

    /**
     * @return array{binario: string, mime: string, comprimido: bool}|null
     */
    private function comprimirAJPEG(UploadedFile $archivo): ?array
    {
        try {
            $imagen = match ($archivo->guessExtension()) {
                'png' => @imagecreatefrompng($archivo->getRealPath()),
                'webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($archivo->getRealPath()) : null,
                'gif' => function_exists('imagecreatefromgif') ? @imagecreatefromgif($archivo->getRealPath()) : null,
                default => @imagecreatefromjpeg($archivo->getRealPath()),
            };

            if (! $imagen) {
                return null;
            }

            $ancho = imagesx($imagen);
            $alto = imagesy($imagen);
            $maxLado = 1600;

            if (max($ancho, $alto) > $maxLado) {
                $escala = $maxLado / max($ancho, $alto);
                $nuevoAncho = (int) round($ancho * $escala);
                $nuevoAlto = (int) round($alto * $escala);

                $nueva = imagecreatetruecolor($nuevoAncho, $nuevoAlto);
                $blanco = imagecolorallocate($nueva, 255, 255, 255);
                imagefilledrectangle($nueva, 0, 0, $nuevoAncho, $nuevoAlto, $blanco);
                imagecopyresampled($nueva, $imagen, 0, 0, 0, 0, $nuevoAncho, $nuevoAlto, $ancho, $alto);
                imagedestroy($imagen);
                $imagen = $nueva;
            }

            ob_start();
            imagejpeg($imagen, null, 72);
            $binario = ob_get_clean();
            imagedestroy($imagen);

            if ($binario === false) {
                return null;
            }

            return [
                'binario' => $binario,
                'mime' => 'image/jpeg',
                'comprimido' => true,
            ];
        } catch (\Throwable) {
            return null;
        }
    }

    private function eliminarComprobante(ReportePago $reporte): void
    {
        if ($reporte->comprobante && Storage::disk('public')->exists($reporte->comprobante)) {
            Storage::disk('public')->delete($reporte->comprobante);
        }

        // También se quita la copia guardada en BD (si existía).
        Comprobante::where('id_reporte', $reporte->id_reporte)->delete();
    }
}
