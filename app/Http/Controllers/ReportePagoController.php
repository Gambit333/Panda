<?php

namespace App\Http\Controllers;

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
        $reportes = ReportePago::with(['modelo', 'moderador', 'metodoPago', 'cierreSemanal'])
            ->orderByDesc('fecha_reporte')
            ->paginate(15);

        return view('reportes.index', compact('reportes'));
    }

    public function create()
    {
        $usuarioActual = auth()->user();

        // Asumiendo id_rol 1 = CEO, 3 = Admin
        $esAdmin = in_array($usuarioActual->id_rol, [1, 3]);

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

        if ($request->hasFile('comprobante')) {
            $data['comprobante'] = $this->guardarComprobante($request->file('comprobante'));
        }

        ReportePago::create($data);

        return redirect()->route('reportes.index')->with('success', 'Reporte de pago creado correctamente.');
    }

    public function edit(ReportePago $reporte): View
    {
        $modelos = $this->trabajadoresPorRol(['modelo', 'ceo']);

        if (! $modelos->contains('id_trab', $reporte->id_modelo) && $reporte->modelo) {
            $modelos = $modelos->concat([$reporte->modelo])->unique('id_trab');
        }

        $moderadores = $this->trabajadoresPorRol(['moderador', 'chatter']);

        $metodosPago = MetodoPago::all();

        return view('reportes.edit', compact('reporte', 'modelos', 'moderadores', 'metodosPago'));
    }

    public function update(Request $request, ReportePago $reporte): RedirectResponse
    {
        $data = $this->validateData($request);

        if ($request->hasFile('comprobante')) {
            $this->eliminarComprobante($reporte);
            $data['comprobante'] = $this->guardarComprobante($request->file('comprobante'));
        } elseif ($request->boolean('eliminar_comprobante')) {
            $this->eliminarComprobante($reporte);
            $data['comprobante'] = null;
        }

        $reporte->update($data);

        return redirect()->route('reportes.index')->with('success', 'Reporte de pago actualizado correctamente.');
    }

    public function destroy(ReportePago $reporte): RedirectResponse
    {
        $this->eliminarComprobante($reporte);
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
            'descripcion' => ['nullable', 'string'],
            'comprobante' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:4096'],
            'eliminar_comprobante' => ['sometimes', 'boolean'],
        ]);
    }

    /**
     * Guarda el comprobante en disco (storage/app/public/comprobantes).
     * La BD solo guarda la ruta; si GD está disponible se re-codifica a JPEG
     * (máx. 1600px, calidad 72) para minimizar el espacio ocupado.
     */
    private function guardarComprobante(UploadedFile $archivo): string
    {
        if (function_exists('gd_info')) {
            $jpeg = $this->comprimirAJPEG($archivo);
            if ($jpeg !== null) {
                return $jpeg;
            }
        }

        return $archivo->store('comprobantes', 'public');
    }

    private function comprimirAJPEG(UploadedFile $archivo): ?string
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

            $nombre = 'comprobantes/'.$archivo->hashName().'.jpg';

            ob_start();
            imagejpeg($imagen, null, 72);
            $binario = ob_get_clean();
            imagedestroy($imagen);

            if ($binario === false || ! Storage::disk('public')->put($nombre, $binario)) {
                return null;
            }

            return $nombre;
        } catch (\Throwable) {
            return null;
        }
    }

    private function eliminarComprobante(ReportePago $reporte): void
    {
        if ($reporte->comprobante && Storage::disk('public')->exists($reporte->comprobante)) {
            Storage::disk('public')->delete($reporte->comprobante);
        }
    }
}
