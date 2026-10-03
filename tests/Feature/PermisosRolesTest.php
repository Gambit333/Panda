<?php

namespace Tests\Feature;

use App\Models\Rol;
use App\Models\Trabajador;
use App\Support\Permisos;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PermisosRolesTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): Trabajador
    {
        $admin = Rol::create(['rol' => 'admin']);

        return Trabajador::create([
            'nombre' => 'Admin',
            'apellido' => 'Prueba',
            'email' => 'admin.permisos@test.com',
            'id_rol' => $admin->id_rol,
        ]);
    }

    private function trabajador(string $nombreRol, array $permisos, string $email): Trabajador
    {
        $rol = Rol::create(['rol' => $nombreRol, 'permisos' => $permisos]);

        return Trabajador::create([
            'nombre' => 'Persona',
            'apellido' => 'Prueba',
            'email' => $email,
            'id_rol' => $rol->id_rol,
        ]);
    }

    public function test_un_rol_solo_entra_a_las_secciones_marcadas(): void
    {
        $this->actingAs($this->trabajador('contabilidad', ['cierres', 'pagos'], 'contabilidad@test.com'));

        $this->get('/')->assertOk()->assertSee('Cierres semanales')->assertDontSee('Trabajadores');
        $this->get('/cierres')->assertOk();
        $this->get('/pagos')->assertOk();

        $this->get('/trabajadores')->assertRedirect(route('dashboard'))->assertSessionHasErrors('access');
        $this->get('/roles')->assertRedirect(route('dashboard'));
    }

    public function test_un_rol_sin_permisos_marcados_solo_ve_el_inicio(): void
    {
        $this->actingAs($this->trabajador('invitado', [], 'invitado@test.com'));

        $this->get('/')->assertOk()->assertDontSee('Reportes de pago');

        foreach (['/reportes', '/cierres', '/pagos', '/adelantos', '/trabajadores', '/metodos', '/roles'] as $ruta) {
            $this->get($ruta)->assertRedirect(route('dashboard'));
        }
    }

    public function test_los_permisos_guardados_mandan_sobre_el_nombre_del_rol(): void
    {
        // Un "modelo" al que le dan permisos de cierres puede entrar a cierres.
        $this->actingAs($this->trabajador('modelo', ['cierres'], 'modelo.cierres@test.com'));

        $this->get('/cierres')->assertOk();
        $this->get('/reportes')->assertRedirect(route('dashboard'));
    }

    public function test_el_formulario_guarda_los_permisos_al_crear_el_rol(): void
    {
        $this->actingAs($this->admin());

        $this->post('/roles', ['rol' => 'soporte-tecnico', 'permisos' => ['', 'reportes', 'pagos']])
            ->assertRedirect(route('roles.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('roles', ['rol' => 'soporte-tecnico']);
        $this->assertSame(['reportes', 'pagos'], Rol::where('rol', 'soporte-tecnico')->firstOrFail()->permisos);
    }

    public function test_al_editar_se_pueden_cambiar_los_permisos_y_cambia_el_acceso(): void
    {
        $rol = Rol::create(['rol' => 'soporte-tecnico', 'permisos' => ['pagos']]);
        $usuario = Trabajador::create([
            'nombre' => 'Soporte',
            'apellido' => 'Tecnico',
            'email' => 'soporte.tecnico@test.com',
            'id_rol' => $rol->id_rol,
        ]);

        $this->actingAs($this->admin());
        $this->put(route('roles.update', $rol), ['rol' => 'soporte-tecnico', 'permisos' => ['', 'adelantos']])
            ->assertRedirect(route('roles.index'));

        $this->assertSame(['adelantos'], $rol->fresh()->permisos);

        $this->actingAs($usuario);
        $this->get('/adelantos')->assertOk();
        $this->get('/pagos')->assertRedirect(route('dashboard'));
    }

    public function test_los_permisos_de_admin_y_programador_no_se_pueden_cambiar(): void
    {
        $this->actingAs($this->admin());

        // "admin" ya existe (es el rol del usuario de esta prueba).
        $this->post('/roles', ['rol' => 'programador', 'permisos' => ['', 'reportes']])
            ->assertRedirect(route('roles.index'));

        foreach (['admin', 'programador'] as $nombre) {
            $rol = Rol::where('rol', $nombre)->firstOrFail();

            // Aunque se envíen permisos, quedan bloqueados.
            $this->assertNull($rol->permisos);
            $this->assertCount(count(Permisos::MODULOS), $rol->modulos());
        }

        $this->put(route('roles.update', Rol::where('rol', 'programador')->firstOrFail()), [
            'rol' => 'programador',
            'permisos' => ['', 'reportes'],
        ])->assertRedirect(route('roles.index'));

        $this->assertNull(Rol::where('rol', 'programador')->firstOrFail()->permisos);
    }

    public function test_el_formulario_de_edicion_muestra_los_permisos_y_el_aviso_de_bloqueo(): void
    {
        $this->actingAs($this->admin());

        $editable = Rol::create(['rol' => 'soporte-tecnico', 'permisos' => ['pagos']]);
        $this->get(route('roles.edit', $editable))
            ->assertOk()
            ->assertSee('Secciones a las que puede entrar')
            ->assertSee('value="pagos"', false)
            ->assertSee('no se pueden cambiar', false);

        $bloqueado = Rol::where('rol', 'admin')->firstOrFail();
        $this->get(route('roles.edit', $bloqueado))
            ->assertOk()
            ->assertSee('no se pueden cambiar sus permisos', false);
    }

    public function test_el_listado_muestra_las_secciones_permitidas_de_cada_rol(): void
    {
        $this->actingAs($this->admin());

        Rol::create(['rol' => 'contabilidad', 'permisos' => ['cierres', 'metodos']]);
        Rol::create(['rol' => 'solo-modelo', 'permisos' => []]);

        $this->get('/roles')
            ->assertOk()
            ->assertSee('Cierres semanales')
            ->assertSee('Métodos de pago')
            ->assertSee('Solo el inicio');
    }
}
