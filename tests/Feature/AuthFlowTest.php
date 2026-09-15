<?php

namespace Tests\Feature;

use App\Models\Rol;
use App\Models\Trabajador;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/')->assertRedirect('/login');
        $this->get('/reportes')->assertRedirect('/login');
    }

    public function test_first_login_creates_and_hashes_password(): void
    {
        $rol = Rol::create(['rol' => 'modelo']);
        $trabajador = Trabajador::create([
            'nombre' => 'Nuevo', 'apellido' => 'Usuario',
            'email' => 'nuevo@nomina.test', 'id_rol' => $rol->id_rol,
        ]);

        $this->post('/login', ['email' => $trabajador->email])
            ->assertRedirect('/login/password');

        $this->get('/login/password')
            ->assertOk()
            ->assertSee('Crear contraseña e ingresar');

        $this->post('/login/password', [
            'password' => 'secreto123',
            'password_confirmation' => 'secreto123',
        ])->assertRedirect('/');

        $this->assertAuthenticatedAs($trabajador);

        $trabajador->refresh();
        $this->assertNotSame('secreto123', $trabajador->password);
        $this->assertTrue(Hash::check('secreto123', $trabajador->password));
    }

    public function test_login_with_existing_password_and_wrong_password(): void
    {
        $rol = Rol::create(['rol' => 'admin']);
        $trabajador = Trabajador::create([
            'nombre' => 'Admin', 'apellido' => 'Root',
            'email' => 'admin@nomina.test', 'id_rol' => $rol->id_rol,
            'password' => Hash::make('correcta1'),
        ]);

        $this->post('/login', ['email' => $trabajador->email]);

        $this->post('/login/password', ['password' => 'incorrecta1'])
            ->assertSessionHasErrors('password');
        $this->assertGuest();

        $this->get('/login/password')
            ->assertOk()
            ->assertSee('Ingresa la contraseña');

        $this->post('/login/password', ['password' => 'correcta1'])
            ->assertRedirect('/');
        $this->assertAuthenticatedAs($trabajador);
    }

    public function test_unknown_email_is_rejected(): void
    {
        $this->post('/login', ['email' => 'noexiste@nomina.test'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_session_expires_after_inactivity(): void
    {
        $trabajador = $this->trabajadorConPassword();
        $this->actingAs($trabajador);

        session(['session_last_seen' => now()->subMinutes(2)]);

        $this->get('/')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_keepalive_keeps_session_alive(): void
    {
        $trabajador = $this->trabajadorConPassword();
        $this->actingAs($trabajador);

        $this->get('/')->assertOk();
        $this->post('/session/keepalive')->assertNoContent();
        $this->assertAuthenticated();
    }

    private function trabajadorConPassword(): Trabajador
    {
        $rol = Rol::create(['rol' => 'admin']);

        return Trabajador::create([
            'nombre' => 'Admin', 'apellido' => 'Root',
            'email' => 'admin@nomina.test', 'id_rol' => $rol->id_rol,
            'password' => Hash::make('correcta1'),
        ]);
    }
}