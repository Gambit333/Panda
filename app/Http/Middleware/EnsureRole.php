<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    private const SUPER_ROLES = ['admin', 'ceo', 'support'];

    public function handle(Request $request, Closure $next, string ...$extraRoles): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        $rol = strtolower((string) ($user->rol?->rol ?? ''));

        if (! in_array($rol, array_merge(self::SUPER_ROLES, $extraRoles), true)) {
            return redirect()->route('dashboard')
                ->withErrors(['access' => 'No tienes permiso para acceder a este módulo.']);
        }

        return $next($request);
    }
}