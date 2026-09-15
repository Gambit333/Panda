<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class SessionTimeout
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            $lastSeen = session('session_last_seen');

            if ($lastSeen instanceof \DateTimeInterface && $lastSeen->lt(now()->subMinute())) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('login')
                    ->withErrors(['email' => 'Tu sesión expiró. Inicia sesión nuevamente.']);
            }

            session(['session_last_seen' => now()]);
        }

        return $next($request);
    }
}