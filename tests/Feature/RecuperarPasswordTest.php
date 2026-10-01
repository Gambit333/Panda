<?php

namespace Tests\Feature;

use App\Models\Rol;
use App\Models\Trabajador;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RecuperarPasswordTest extends TestCase
{
    use RefreshDatabase;

    public function test_se_puede_crear_una_contrasena_nueva_desde_la_pantalla_de_password(): void
    {
        $trabajador = $this->trabajador();

        $this->post('/login', ['email' => $trabajador->email]);

        $this->get('/login/password')
            ->assertOk()
            ->assertSee('Hola, Lucia')
            ->assertSee('¿Olvidaste tu contraseña?');

        $this->post('/login/recuperar')->assertRedirect('/login/password');

        $this->get('/login/password')
            ->assertOk()
            ->assertSee('Crea tu contraseña')
            ->assertSee('Repite la contraseña')
            ->assertDontSee('¿Olvidaste tu contraseña?');

        $this->post('/login/password', [
            'password' => 'nuevaclaw123',
            'password_confirmation' => 'nuevaclaw123',
        ])->assertRedirect('/');

        $this->assertAuthenticated();
        $this->assertTrue(Hash::check('nuevaclaw123', $trabajador->fresh()->password));

        $this->post('/logout');
        $this->post('/login', ['email' => $trabajador->email]);
        $this->post('/login/password', ['password' => 'nuevaclaw123'])->assertRedirect('/');
    }

    public function test_la_creacion_exige_confirmar_la_contrasena(): void
    {
        $trabajador = $this->trabajador();

        $this->post('/login', ['email' => $trabajador->email]);
        $this->post('/login/recuperar');

        $this->post('/login/password', [
            'password' => 'nuevaclaw123',
            'password_confirmation' => 'otra-distinta',
        ])->assertSessionHasErrors('password');

        $this->assertGuest();
        $this->assertTrue(Hash::check('claveoriginal1', $trabajador->fresh()->password));
    }

    public function test_el_enlace_no_sirve_sin_un_email_en_sesion(): void
    {
        $this->post('/login/recuperar')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_el_password_normal_sigue_siendo_obligatorio_si_no_se_solicita_una_nueva(): void
    {
        $trabajador = $this->trabajador();

        $this->post('/login', ['email' => $trabajador->email]);

        $this->post('/login/password', ['password' => 'incorrecta'])->assertSessionHasErrors('password');
        $this->assertGuest();
        $this->assertTrue(Hash::check('claveoriginal1', $trabajador->fresh()->password));
    }

    private function trabajador(): Trabajador
    {
        $rol = Rol::create(['rol' => 'modelo']);

        return Trabajador::create([
            'nombre' => 'Lucia', 'apellido' => 'Rios', 'email' => 'lucia@nomina.test',
            'id_rol' => $rol->id_rol,
            'password' => Hash::make('claveoriginal1'),
        ]);
    }
}
