<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Solo los programadores pueden cambiar contraseñas y desbloquear cuentas.
 * Los programadores siempre tienen acceso a la sección de trabajadores
 * (rol bloqueado en Permisos::ROLES_BLOQUEADOS), pero se deja el chequeo explícito.
 */
class EnsureProgramador
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        abort_unless($user->esProgramador(), 403, 'Solo un programador puede hacer esto.');

        return $next($request);
    }
}
