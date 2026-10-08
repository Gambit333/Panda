<?php

namespace App\Http\Controllers;

use App\Models\MetodoPago;
use App\Models\ReportePago;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

/**
 * Sirve el comprobante de un reporte: primero la copia guardada en la BD
 * (table `comprobantes`) y, si no existe, la del disco. Solo pueden verlo
 * quien tiene acceso total, el modelo/moderador del reporte o el dueño del
 * método de pago usado.
 */
class ComprobanteController extends Controller
{
    public function show(ReportePago $reporte): Response
    {
        $this->autorizar($reporte);

        $blob = $reporte->comprobanteBinario;

        if ($blob !== null && filled($blob->imagen)) {
            $binario = $blob->binario;

            return response($binario, 200, [
                'Content-Type' => $blob->mime ?: 'image/jpeg',
                'Content-Length' => (string) (($blob->tamano > 0 ? (int) $blob->tamano : strlen($binario))),
                'Cache-Control' => 'public, max-age=31536000, immutable',
            ]);
        }

        if ($reporte->comprobante && Storage::disk('public')->exists($reporte->comprobante)) {
            return Storage::disk('public')->response($reporte->comprobante, null, [
                'Cache-Control' => 'public, max-age=31536000',
            ]);
        }

        abort(404);
    }

    private function autorizar(ReportePago $reporte): void
    {
        $user = auth()->user();

        if (! $user) {
            abort(401);
        }

        if ($user->esSuperRol()) {
            return;
        }

        $id = (int) $user->getAuthIdentifier();

        if ((int) $reporte->id_modelo === $id || (int) $reporte->id_moderador === $id) {
            return;
        }

        $esDueno = MetodoPago::where('id_mp', $reporte->id_mp)
            ->where('id_propietario', $id)
            ->exists();

        if ($esDueno) {
            return;
        }

        abort(403);
    }
}
