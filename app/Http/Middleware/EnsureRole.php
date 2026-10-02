<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    public function handle(Request $request, Closure $next, string ...$extraRoles): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        $rol = strtolower((string) ($user->rol?->rol ?? ''));
        $permitidos = array_map('strtolower', $extraRoles);

        if ($user->esSuperRol() || in_array($rol, $permitidos, true)) {
            return $next($request);
        }

        return redirect()->route('dashboard')
            ->withErrors(['access' => 'No tienes permiso para acceder a este módulo.']);
    }
}
