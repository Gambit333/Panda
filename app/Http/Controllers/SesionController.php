<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

/**
 * Mantiene la sesión viva mientras haya al menos una pestaña abierta.
 *
 * Cada carga de página registra un "id de pestaña". Al cerrarse una pestaña
 * (pagehide -> sendBeacon) se da de baja ese id; cuando ya no queda ninguna
 * pestaña registrada, la sesión se invalida y el enlace copiado vuelve al login.
 * Dejar la página inactiva no envía nada, así que la sesión NO expira por inactividad.
 */
class SesionController extends Controller
{
    private const MAX_PESTANAS = 20;

    private const ANTIGUEDAD_MAXIMA = 43200; // 12 horas en segundos

    public function registrarTab(Request $request): Response
    {
        $tabId = $this->tabId($request);

        if ($tabId === null) {
            return response()->noContent();
        }

        $tabs = $this->tabs($request);
        $tabs[$tabId] = time();

        $corte = time() - self::ANTIGUEDAD_MAXIMA;
        $tabs = array_filter($tabs, fn ($marca) => $marca > $corte);

        if (count($tabs) > self::MAX_PESTANAS) {
            $tabs = array_slice($tabs, -self::MAX_PESTANAS, null, true);
        }

        $request->session()->put('tabs', $tabs);

        return response()->noContent();
    }

    public function cerrarTab(Request $request): Response
    {
        $tabId = $this->tabId($request);

        if ($tabId === null) {
            return response()->noContent();
        }

        $tabs = $this->tabs($request);
        unset($tabs[$tabId]);

        if ($tabs === []) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return response()->noContent();
        }

        $request->session()->put('tabs', $tabs);

        return response()->noContent();
    }

    /**
     * @return array<string, int>
     */
    private function tabs(Request $request): array
    {
        return (array) $request->session()->get('tabs', []);
    }

    private function tabId(Request $request): ?string
    {
        $tabId = $request->input('tab_id') ?: $request->header('X-Tab-Id');

        return is_string($tabId) && $tabId !== '' ? substr($tabId, 0, 64) : null;
    }
}
