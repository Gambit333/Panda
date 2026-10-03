<?php

namespace Tests\Feature;

use App\Models\Rol;
use App\Models\Trabajador;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * El "¿Olvidaste tu contraseña?" ya no recupera nada: avisa que hay que
 * contactar a un programador. Solo quien tiene el rol programador puede
 * cambiar contraseñas (ver BloqueoIntentosTest).
 */
class RecuperarPasswordTest extends TestCase
{
    use RefreshDatabase;

    public function test_olvide_mi_contrasena_muestra_el_mensaje_de_contactar_al_programador(): void
    {
        $trabajador = $this->trabajador();

        $this->post('/login', ['email' => $trabajador->email]);

        $this->get('/login/password')
            ->assertOk()
            ->assertSee('Hola, Lucia')
            ->assertSee('¿Olvidaste tu contraseña?');

        $this->post('/login/recuperar')
            ->assertRedirect()
            ->assertSessionHas('info');

        $this->get('/login/password')
            ->assertOk()
            ->assertSee('contacta a un programador');
    }

    public function test_olvide_mi_contrasena_no_permite_cambiar_la_clave(): void
    {
        $trabajador = $this->trabajador();

        $this->post('/login', ['email' => $trabajador->email]);
        $this->post('/login/recuperar');

        $this->post('/login/password', [
            'password' => 'nuevaclaw123',
            'password_confirmation' => 'nuevaclaw123',
        ])->assertSessionHasErrors('password');

        $this->assertGuest();
        $this->assertTrue(Hash::check('claveoriginal1', $trabajador->fresh()->password));
    }

    public function test_quien_no_tiene_contrasena_sigue_creando_una_en_su_primer_ingreso(): void
    {
        $trabajador = $this->trabajador(password: null);

        $this->post('/login', ['email' => $trabajador->email]);

        $this->get('/login/password')
            ->assertOk()
            ->assertSee('Crea tu contraseña')
            ->assertSee('Repite la contraseña');

        $this->post('/login/password', [
            'password' => 'primera1234',
            'password_confirmation' => 'primera1234',
        ])->assertRedirect('/');

        $this->assertAuthenticated();
        $this->assertTrue(Hash::check('primera1234', (string) $trabajador->fresh()->password));

        $this->post('/logout');
        $this->post('/login', ['email' => $trabajador->email]);
        $this->post('/login/password', ['password' => 'primera1234'])->assertRedirect('/');
    }

    public function test_la_creacion_exige_confirmar_la_contrasena(): void
    {
        $trabajador = $this->trabajador(password: null);

        $this->post('/login', ['email' => $trabajador->email]);

        $this->post('/login/password', [
            'password' => 'primera1234',
            'password_confirmation' => 'otra-distinta',
        ])->assertSessionHasErrors('password');

        $this->assertGuest();
        $this->assertEmpty($trabajador->fresh()->password);
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

    private function trabajador(?string $password = 'claveoriginal1'): Trabajador
    {
        $rol = Rol::create(['rol' => 'modelo']);

        return Trabajador::create([
            'nombre' => 'Lucia', 'apellido' => 'Rios', 'email' => 'lucia@nomina.test',
            'id_rol' => $rol->id_rol,
            'password' => $password ? Hash::make($password) : null,
        ]);
    }
}
