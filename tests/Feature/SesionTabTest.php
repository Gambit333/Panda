<?php

namespace Tests\Feature;

use App\Models\Rol;
use App\Models\Trabajador;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SesionTabTest extends TestCase
{
    use RefreshDatabase;

    public function test_la_sesion_no_expira_por_inactividad_y_se_registra_la_pestana(): void
    {
        $this->loginComoAdmin();

        $this->get('/')->assertOk();

        $this->post('/session/tab', ['tab_id' => 'tab-1'])->assertNoContent();

        $this->assertCount(1, session('tabs'));
        $this->assertAuthenticated();

        // Sin peticiones durante un rato la sesión sigue viva (no hay expiración por inactividad).
        $this->assertTrue(config('session.expire_on_close'));
        $this->assertGreaterThanOrEqual(60, config('session.lifetime'));
    }

    public function test_cerrar_una_pestana_no_cierra_la_sesion_si_queda_otra(): void
    {
        $this->loginComoAdmin();

        $this->post('/session/tab', ['tab_id' => 'tab-1']);
        $this->post('/session/tab', ['tab_id' => 'tab-2']);
        $this->assertCount(2, session('tabs'));

        $this->post('/session/tab/cerrar', ['tab_id' => 'tab-1'])->assertNoContent();

        $this->assertCount(1, session('tabs'));
        $this->get('/')->assertOk();
        $this->assertAuthenticated();
    }

    public function test_cerrar_la_ultima_pestana_invalida_la_sesion(): void
    {
        $this->loginComoAdmin();

        $this->post('/session/tab', ['tab_id' => 'tab-1']);
        $this->post('/session/tab', ['tab_id' => 'tab-2']);

        $this->post('/session/tab/cerrar', ['tab_id' => 'tab-1']);
        $this->post('/session/tab/cerrar', ['tab_id' => 'tab-2'])->assertNoContent();

        $this->assertGuest();
        $this->assertEmpty(session('tabs'));

        // El enlace copiado vuelve al login.
        $this->get('/')->assertRedirect('/login');
        $this->get('/reportes')->assertRedirect('/login');
    }

    private function loginComoAdmin(): void
    {
        $rol = Rol::create(['rol' => 'admin']);

        $trabajador = Trabajador::create([
            'nombre' => 'Admin', 'apellido' => 'Root',
            'email' => 'admin@nomina.test',
            'id_rol' => $rol->id_rol,
            'password' => Hash::make('correcta1'),
        ]);

        $this->post('/login', ['email' => $trabajador->email]);
        $this->post('/login/password', ['password' => 'correcta1'])->assertRedirect('/');
    }
}
