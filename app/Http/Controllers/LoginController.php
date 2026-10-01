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
        ]);
    }

    /**
     * "Olvidé mi contraseña": pasa el login al modo de crear contraseña
     * (misma pantalla del primer ingreso) sin necesidad de código por correo.
     */
    public function recuperarPassword(Request $request): RedirectResponse
    {
        if (! is_string(session()->get('auth_email'))) {
            return redirect()->route('login');
        }

        $request->session()->put('crear_password', true);

        return redirect()->route('login.password');
    }

    public function submitPassword(Request $request): RedirectResponse
    {
        $trabajador = $this->pendingTrabajador();

        if ($this->debeCrearPassword($trabajador)) {
            $data = $request->validate([
                'password' => ['required', 'string', 'min:6', 'confirmed'],
            ]);

            $trabajador->password = Hash::make($data['password']);
            $trabajador->save();
        } else {
            $data = $request->validate([
                'password' => ['required', 'string'],
            ]);

            if (! Hash::check($data['password'], $trabajador->password)) {
                return back()->withErrors([
                    'password' => 'La contraseña ingresada no es correcta.',
                ])->withInput();
            }
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
        return blank($trabajador->password) || session()->get('crear_password') === true;
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
