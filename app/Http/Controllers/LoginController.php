<?php

namespace App\Http\Controllers;

use App\Models\Trabajador;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function showLoginForm(): View
    {
        return view('auth.login');
    }

    public function submitEmail(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
        ]);

        $trabajador = $this->findByEmail($data['email']);

        if (! $trabajador) {
            return back()->withErrors([
                'email' => 'No existe ningún trabajador con ese email.',
            ])->withInput();
        }

        session()->put('auth_email', $trabajador->email);

        return redirect()->route('login.password');
    }

    public function showPasswordForm(): View|RedirectResponse
    {
        $trabajador = $this->pendingTrabajador();

        return view('auth.password', [
            'trabajador' => $trabajador,
            'createsPassword' => $this->debeCrearPassword($trabajador),
            'intentosRestantes' => $this->intentosRestantes($trabajador),
        ]);
    }

    /**
     * "Olvidé mi contraseña": ya no se puede recuperar por email, solo un
     * programador puede cambiarla desde la sección de trabajadores.
     */
    public function recuperarPassword(Request $request): RedirectResponse
    {
        if (! is_string(session()->get('auth_email'))) {
            return redirect()->route('login');
        }

        return back()->with('info', 'Para cambiar tu contraseña contacta a un programador, '
            .'que lo puede hacer por ti desde la sección de Trabajadores.');
    }

    public function submitPassword(Request $request): RedirectResponse
    {
        $trabajador = $this->pendingTrabajador();

        // Si el bloqueo ya venció, se limpia solo para dejar entrar.
        if ($trabajador->bloqueado && ! $trabajador->estaBloqueado()) {
            $trabajador->limpiarIntentos();
        }

        if ($trabajador->estaBloqueado()) {
            return redirect()->route('login.password')->withErrors([
                'password' => 'Cuenta bloqueada por intentos fallidos. Vuelve a intentar en '
                    .$trabajador->minutosRestantesBloqueo().' minuto(s) o contacta a un programador para que te la cambie.',
            ]);
        }

        if ($this->debeCrearPassword($trabajador)) {
            $data = $request->validate([
                'password' => ['required', 'string', 'min:6', 'confirmed'],
            ]);

            $trabajador->password = $data['password'];
            $trabajador->limpiarIntentos();
        } else {
            $data = $request->validate([
                'password' => ['required', 'string'],
            ]);

            if (! Hash::check($data['password'], $trabajador->password)) {
                $trabajador->registrarIntentoFallido();

                $restantes = $this->intentosRestantes($trabajador);

                return redirect()->route('login.password')->withErrors([
                    'password' => $trabajador->estaBloqueado()
                        ? 'Cuenta bloqueada por '.Trabajador::MAX_INTENTOS.' intentos fallidos. '
                            .'Contacta a un programador para que te la cambie o te desbloquee.'
                        : 'La contraseña ingresada no es correcta. Te '.($restantes === 1 ? 'queda' : 'quedan')
                            .' '.$restantes.' '.($restantes === 1 ? 'intento' : 'intentos').'.',
                ]);
            }

            $trabajador->limpiarIntentos();
        }

        session()->forget(['auth_email', 'crear_password']);

        Auth::login($trabajador);
        $request->session()->regenerate();

        return redirect()->route('dashboard');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    private function findByEmail(string $email): ?Trabajador
    {
        return Trabajador::whereRaw('lower(email) = ?', [strtolower(trim($email))])->first();
    }

    private function debeCrearPassword(Trabajador $trabajador): bool
    {
        return blank($trabajador->password);
    }

    /** Intentos que le quedan antes del bloqueo. */
    private function intentosRestantes(Trabajador $trabajador): int
    {
        return max(0, Trabajador::MAX_INTENTOS - (int) $trabajador->intentos_fallidos);
    }

    private function pendingTrabajador(): Trabajador
    {
        $email = session()->get('auth_email');
        abort_unless(is_string($email) && $email !== '', 419, 'Sesión de login expirada.');

        $trabajador = $this->findByEmail($email);
        abort_unless($trabajador, 419, 'Sesión de login expirada.');

        return $trabajador;
    }
}
