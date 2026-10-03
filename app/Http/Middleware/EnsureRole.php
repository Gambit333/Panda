<?php

namespace App\Http\Middleware;

use App\Support\Permisos;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    /**
     * Comprueba el acceso a un módulo: `role:reportes`, `role:cierres`, ...
     * Sin argumentos exige el acceso a todos los módulos.
     */
    public function handle(Request $request, Closure $next, string ...$modulos): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        $pedidos = $modulos === [] ? Permisos::todos() : $modulos;

        foreach ($pedidos as $modulo) {
            if ($user->puede($modulo)) {
                return $next($request);
            }
        }

        return redirect()->route('dashboard')
            ->withErrors(['access' => 'No tienes permiso para acceder a este módulo.']);
    }
}
