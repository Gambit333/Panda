<?php

namespace Tests\Feature;

use App\Models\Rol;
use App\Models\Trabajador;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AccesoRolesTest extends TestCase
{
    use RefreshDatabase;

    private function usuarioConRol(string $nombreRol): Trabajador
    {
        $rol = Rol::create(['rol' => $nombreRol]);

        return Trabajador::create([
            'nombre' => 'Persona',
            'apellido' => $nombreRol,
            'email' => $nombreRol.'@test.com',
            'id_rol' => $rol->id_rol,
        ]);
    }

    public static function superRoles(): array
    {
        return [['admin'], ['ceo'], ['support'], ['programador']];
    }

    #[DataProvider('superRoles')]
    public function test_los_superroles_uentan_con_todas_las_secciones(string $nombreRol): void
    {
        $this->actingAs($this->usuarioConRol($nombreRol));

        foreach (['/reportes', '/cierres', '/pagos', '/adelantos', '/trabajadores', '/metodos', '/roles'] as $ruta) {
            $this->get($ruta)->assertOk();
        }

        $this->get('/')
            ->assertOk()
            ->assertSee('Trabajadores')
            ->assertSee('Cierres semanales')
            ->assertSee('Adelantos y préstamos')
            ->assertSee('Roles');
    }

    public function test_cualquier_rol_distinto_de_modelo_y_moderador_entra_a_todo(): void
    {
        // Juan Brea y Juan Hernandez están en la BD real con rol "programador".
        $this->actingAs($this->usuarioConRol('programador'));

        $this->get('/')->assertOk()->assertSee('Trabajadores');
        $this->get('/trabajadores')->assertOk();
        $this->get('/roles')->assertOk();

        // También un rol inventado: entra a todo para no dejar a nadie fuera.
        $otro = $this->usuarioConRol('contabilidad');
        $this->actingAs($otro);
        $this->get('/cierres')->assertOk();
        $this->get('/metodos')->assertOk();
    }

    public function test_moderador_y_modelo_solo_ven_lo_suyo(): void
    {
        $this->actingAs($this->usuarioConRol('moderador'));
        $this->get('/')->assertOk()->assertDontSee('Trabajadores')->assertDontSee('Cierres semanales');
        $this->get('/reportes')->assertOk();
        $this->get('/trabajadores')->assertRedirect(route('dashboard'));

        $this->actingAs($this->usuarioConRol('modelo'));
        $this->get('/')->assertOk()->assertDontSee('Reportes de pago');
        $this->get('/reportes')->assertRedirect(route('dashboard'));
    }

    public function test_editar_un_rol_carga_el_formulario_con_su_nombre(): void
    {
        $admin = $this->usuarioConRol('admin');
        $rol = Rol::create(['rol' => 'moderador']);

        $this->actingAs($admin);

        $this->get(route('roles.edit', $rol))
            ->assertOk()
            ->assertSee('Editar rol')
            ->assertSee('value="moderador"', false);
    }

    public function test_actualizar_un_rol_renombra_el_rol(): void
    {
        $admin = $this->usuarioConRol('admin');
        $rol = Rol::create(['rol' => 'moderador']);

        $this->actingAs($admin);

        $this->put(route('roles.update', $rol), ['rol' => 'moderador Senior'])
            ->assertRedirect(route('roles.index'))
            ->assertSessionHas('success');

        $this->assertSame('moderador Senior', $rol->fresh()->rol);
        $this->assertDatabaseHas('roles', ['id_rol' => $rol->id_rol, 'rol' => 'moderador Senior']);
    }

    public function test_no_se_puede_renombrar_un_rol_a_uno_existente(): void
    {
        $admin = $this->usuarioConRol('admin');
        $rol = Rol::create(['rol' => 'moderador']);

        $this->actingAs($admin);

        $this->put(route('roles.update', $rol), ['rol' => 'admin'])
            ->assertSessionHasErrors('rol');

        $this->assertSame('moderador', $rol->fresh()->rol);
    }

    public function test_el_rol_editado_actualiza_el_sistema_de_permisos(): void
    {
        $admin = $this->usuarioConRol('admin');
        $rol = Rol::create(['rol' => 'soporte-tecnico']);

        $this->actingAs($admin);
        $this->put(route('roles.update', $rol), ['rol' => 'support'])->assertRedirect(route('roles.index'));

        // Con el nombre correcto, ese rol ya tiene acceso a todo.
        $this->get('/roles')->assertOk();
    }

    public function test_se_puede_crear_y_borrar_un_rol(): void
    {
        $admin = $this->usuarioConRol('admin');

        $this->actingAs($admin);

        $this->post('/roles', ['rol' => 'pinto'])->assertRedirect(route('roles.index'));
        $this->assertDatabaseHas('roles', ['rol' => 'pinto']);

        $rol = Rol::where('rol', 'pinto')->firstOrFail();
        $this->delete(route('roles.destroy', $rol))->assertRedirect(route('roles.index'));
        $this->assertDatabaseMissing('roles', ['id_rol' => $rol->id_rol]);
    }
}
