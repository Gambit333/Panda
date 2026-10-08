<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Solo los dueños de métodos de pago (rol `propietario`) pueden ver su sección
 * "Mis reportes". No depende de los permisos por módulo de Permisos::MODULOS:
 * el propietario por defecto no tiene módulos, pero esta sección siempre le
 * pertenece.
 */
class EnsurePropietario
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        abort_unless($user->esPropietario(), 403, 'Solo un propietario de métodos de pago puede hacer esto.');

        return $next($request);
    }
}
