<?php

namespace Tests\Feature;

use App\Models\Rol;
use App\Models\Trabajador;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class BloqueoIntentosTest extends TestCase
{
    use RefreshDatabase;

    private function trabajador(string $nombreRol, string $email, ?string $password = 'secreto123'): Trabajador
    {
        $rol = Rol::create(['rol' => $nombreRol]);

        return Trabajador::create([
            'nombre' => 'Persona',
            'apellido' => 'Prueba',
            'email' => $email,
            'id_rol' => $rol->id_rol,
            'password' => $password ? Hash::make($password) : null,
        ]);
    }

    private function entrar(string $email, string $password)
    {
        $this->post('/login', ['email' => $email])->assertRedirect(route('login.password'));

        return $this->post('/login/password', ['password' => $password]);
    }

    public function test_seis_intentos_fallidos_bloquean_la_cuenta(): void
    {
        $trabajador = $this->trabajador('modelo', 'modelo@test.com');

        for ($i = 1; $i <= 5; $i++) {
            $this->entrar('modelo@test.com', 'incorrecta')
                ->assertSessionHasErrors('password');
            $this->assertFalse($trabajador->fresh()->estaBloqueado(), "No debe bloquear en el intento {$i}");
        }

        $this->entrar('modelo@test.com', 'incorrecta')->assertSessionHasErrors('password');

        $bloqueado = $trabajador->fresh();
        $this->assertTrue($bloqueado->estaBloqueado());
        $this->assertSame(Trabajador::MAX_INTENTOS, (int) $bloqueado->intentos_fallidos);
        $this->assertNotNull($bloqueado->bloqueado_hasta);
    }

    public function test_bloqueada_la_contrasena_correcta_no_entra(): void
    {
        $trabajador = $this->trabajador('modelo', 'modelo@test.com');

        for ($i = 0; $i < Trabajador::MAX_INTENTOS; $i++) {
            $this->entrar('modelo@test.com', 'incorrecta');
        }

        $this->entrar('modelo@test.com', 'secreto123')
            ->assertSessionHasErrors('password')
            ->assertRedirect(route('login.password'));

        $this->assertGuest();
        $this->assertStringContainsString(
            'bloqueada',
            session('errors')->first('password')
        );
    }

    public function test_con_cinco_fallos_la_contrasena_correcta_entra_y_limpia_el_contador(): void
    {
        $trabajador = $this->trabajador('modelo', 'modelo@test.com');

        for ($i = 0; $i < 5; $i++) {
            $this->entrar('modelo@test.com', 'incorrecta');
        }

        $this->assertSame(5, (int) $trabajador->fresh()->intentos_fallidos);

        $this->entrar('modelo@test.com', 'secreto123')->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($trabajador->fresh());
        $this->assertSame(0, (int) $trabajador->fresh()->intentos_fallidos);
        $this->assertFalse((bool) $trabajador->fresh()->bloqueado);
    }

    public function test_el_bloqueo_expira_solo(): void
    {
        $trabajador = $this->trabajador('modelo', 'modelo@test.com');

        for ($i = 0; $i < Trabajador::MAX_INTENTOS; $i++) {
            $this->entrar('modelo@test.com', 'incorrecta');
        }

        $this->assertTrue($trabajador->fresh()->estaBloqueado());

        $this->travel(Trabajador::MINUTOS_BLOQUEO + 1)->minutes();

        $this->entrar('modelo@test.com', 'secreto123')->assertRedirect(route('dashboard'));
        $this->assertFalse((bool) $trabajador->fresh()->bloqueado);
    }

    public function test_olvide_mi_contrasena_muestra_el_contacto_al_programador(): void
    {
        $this->trabajador('modelo', 'modelo@test.com');

        $this->post('/login', ['email' => 'modelo@test.com'])->assertRedirect(route('login.password'));

        $this->post('/login/recuperar')
            ->assertRedirect()
            ->assertSessionHas('info');

        $this->get(route('login.password'))
            ->assertOk()
            ->assertSee('contacta a un programador');

        // No se habilita el modo "crear contraseña" por email.
        $this->assertNull(session('crear_password'));
        $this->post('/login/password', ['password' => 'nuevaclave'])
            ->assertSessionHasErrors('password');
    }

    public function test_un_programador_elimina_la_contrasena_y_el_usuario_la_vuelve_a_crear(): void
    {
        $programador = $this->trabajador('programador', 'programador@test.com');
        $bloqueado = $this->trabajador('modelo', 'modelo@test.com');

        for ($i = 0; $i < Trabajador::MAX_INTENTOS; $i++) {
            $this->entrar('modelo@test.com', 'incorrecta');
        }

        $this->assertTrue($bloqueado->fresh()->estaBloqueado());

        $this->actingAs($programador);

        $this->post(route('trabajadores.password.eliminar', $bloqueado))
            ->assertRedirect(route('trabajadores.index'))
            ->assertSessionHas('success');

        // La clave desaparece y la cuenta queda desbloqueada con 0 intentos.
        $this->assertNull($bloqueado->fresh()->password);
        $this->assertFalse((bool) $bloqueado->fresh()->bloqueado);
        $this->assertSame(0, (int) $bloqueado->fresh()->intentos_fallidos);

        // El usuario entra y crea su propia contraseña.
        $this->post('/logout');

        $this->post('/login', ['email' => 'modelo@test.com'])->assertRedirect(route('login.password'));
        $this->get('/login/password')
            ->assertOk()
            ->assertSee('Crea tu contraseña')
            ->assertSee('Repite la contraseña');

        $this->post('/login/password', [
            'password' => 'claveNueva1',
            'password_confirmation' => 'claveNueva1',
        ])->assertRedirect(route('dashboard'));

        $this->assertTrue(Hash::check('claveNueva1', (string) $bloqueado->fresh()->password));

        $this->post('/logout');
        $this->entrar('modelo@test.com', 'claveNueva1')->assertRedirect(route('dashboard'));
    }

    public function test_quitar_la_contrasena_a_quien_no_tenia_una_solo_lo_desbloquea(): void
    {
        $programador = $this->trabajador('programador', 'programador@test.com');
        $bloqueado = $this->trabajador('modelo', 'modelo@test.com', null);

        for ($i = 0; $i < Trabajador::MAX_INTENTOS; $i++) {
            $bloqueado->registrarIntentoFallido();
        }

        $this->assertTrue($bloqueado->fresh()->estaBloqueado());

        $this->actingAs($programador);

        $this->post(route('trabajadores.password.eliminar', $bloqueado))
            ->assertRedirect(route('trabajadores.index'))
            ->assertSessionHas('success', fn (string $mensaje) => str_contains($mensaje, 'no tenía contraseña'));

        $this->assertFalse((bool) $bloqueado->fresh()->bloqueado);
        $this->assertSame(0, (int) $bloqueado->fresh()->intentos_fallidos);
    }

    public function test_solo_los_programadores_gestionan_contrasenas(): void
    {
        foreach (['admin', 'ceo', 'support'] as $indice => $nombreRol) {
            $usuario = $this->trabajador($nombreRol, $nombreRol.'.test@test.com');
            $objetivo = $this->trabajador('modelo', 'objetivo'.$indice.'@test.com');

            $this->actingAs($usuario);

            $this->post(route('trabajadores.password.eliminar', $objetivo))->assertForbidden();
            $this->post(route('trabajadores.desbloquear', $objetivo))->assertForbidden();

            // Tampoco aparece el botón en el listado.
            $this->get('/trabajadores')->assertOk()->assertDontSee('Quitar contraseña');

            $this->assertTrue(Hash::check('secreto123', (string) $objetivo->fresh()->password));
        }
    }

    public function test_los_programadores_ven_el_boton_para_gestionar_contrasenas(): void
    {
        $programador = $this->trabajador('programador', 'programador@test.com');
        $bloqueado = $this->trabajador('modelo', 'modelo@test.com');

        for ($i = 0; $i < Trabajador::MAX_INTENTOS; $i++) {
            $this->entrar('modelo@test.com', 'incorrecta');
        }

        $this->actingAs($programador);

        $this->get('/trabajadores')
            ->assertOk()
            ->assertSee('Desbloquear')
            ->assertSee('Quitar contraseña')
            ->assertSee(route('trabajadores.password.eliminar', $bloqueado), false)
            ->assertSee('Bloqueado');
    }

    public function test_la_ruta_para_editar_la_contrasena_ya_no_existe(): void
    {
        $programador = $this->trabajador('programador', 'programador@test.com');
        $objetivo = $this->trabajador('modelo', 'modelo@test.com');

        $this->actingAs($programador);

        $this->get('/trabajadores/'.$objetivo->id_trab.'/password')->assertNotFound();
    }

    public function test_un_programador_puede_quitarse_su_propia_contrasena(): void
    {
        $programador = $this->trabajador('programador', 'programador@test.com');

        $this->actingAs($programador);

        $this->post(route('trabajadores.password.eliminar', $programador))
            ->assertRedirect(route('trabajadores.index'));

        $this->assertNull($programador->fresh()->password);

        $this->post('/logout');

        $this->post('/login', ['email' => 'programador@test.com'])->assertRedirect(route('login.password'));
        $this->get('/login/password')->assertOk()->assertSee('Crea tu contraseña');
    }

    public function test_los_programadores_gestionan_contrasenas_de_otro_programador(): void
    {
        $programador = $this->trabajador('programador', 'programador@test.com');
        $otro = $this->trabajador('programador', 'otro@test.com');

        $otro->registrarIntentoFallido();
        $otro->registrarIntentoFallido();

        $this->actingAs($otro);

        $this->post(route('trabajadores.password.eliminar', $programador))
            ->assertRedirect(route('trabajadores.index'));

        $this->assertNull($programador->fresh()->password);
    }
}
