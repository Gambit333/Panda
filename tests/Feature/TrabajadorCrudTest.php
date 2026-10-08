<?php

namespace Tests\Feature;

use App\Models\CierreSemanal;
use App\Models\MetodoPago;
use App\Models\PagoEmpleado;
use App\Models\ReportePago;
use App\Models\Rol;
use App\Models\Trabajador;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrabajadorCrudTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): Trabajador
    {
        $rol = Rol::create(['rol' => 'admin']);

        return Trabajador::create([
            'nombre' => 'Root',
            'apellido' => 'Admin',
            'email' => 'root@test.com',
            'id_rol' => $rol->id_rol,
        ]);
    }

    private function crearPago(Trabajador $trabajador): void
    {
        $cierre = CierreSemanal::create([
            'fecha_inicio' => '2026-09-01',
            'fecha_fin' => '2026-09-07',
        ]);

        PagoEmpleado::create([
            'id_trab' => $trabajador->id_trab,
            'id_cierre' => $cierre->id_cierre,
            'monto_bruto' => 100,
            'monto_neto' => 100,
            'deuda' => 0,
            'monto_final' => 100,
        ]);
    }

    public function test_el_model_binding_recibe_el_trabajador_de_la_url(): void
    {
        $admin = $this->admin();
        $otro = Trabajador::create([
            'nombre' => 'Juan',
            'apellido' => 'Pérez',
            'email' => 'juan@test.com',
            'id_rol' => $admin->id_rol,
        ]);

        $this->actingAs($admin);

        $this->get(route('trabajadores.edit', $otro))->assertOk();
        $this->assertSame('Juan', $otro->fresh()->nombre);
    }

    public function test_actualiza_el_trabajador_de_la_url(): void
    {
        $admin = $this->admin();
        $otro = Trabajador::create([
            'nombre' => 'Juan',
            'apellido' => 'Pérez',
            'email' => 'juan@test.com',
            'id_rol' => $admin->id_rol,
        ]);

        $this->actingAs($admin);

        $this->put(route('trabajadores.update', $otro), [
            'nombre' => 'Juan Carlos',
            'apellido' => 'Pérez',
            'email' => 'juan@test.com',
            'id_rol' => $admin->id_rol,
        ])->assertRedirect(route('trabajadores.index'));

        $this->assertSame('Juan Carlos', $otro->fresh()->nombre);
    }

    public function test_elimina_un_trabajador_sin_historia(): void
    {
        $admin = $this->admin();
        $otro = Trabajador::create([
            'nombre' => 'Juan',
            'apellido' => 'Pérez',
            'email' => 'juan@test.com',
            'id_rol' => $admin->id_rol,
        ]);

        $this->actingAs($admin);

        $this->delete(route('trabajadores.destroy', $otro))
            ->assertRedirect(route('trabajadores.index'))
            ->assertSessionHas('success');

        $this->assertNull($otro->fresh());
    }

    public function test_no_elimina_al_mismo_usuario_conectado(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin);

        $this->delete(route('trabajadores.destroy', $admin))
            ->assertRedirect()
            ->assertSessionHasErrors('trabajador');

        $this->assertNotNull($admin->fresh());
    }

    public function test_no_elimina_un_trabajador_con_pagos_registrados(): void
    {
        $admin = $this->admin();
        $otro = Trabajador::create([
            'nombre' => 'Juan',
            'apellido' => 'Pérez',
            'email' => 'juan@test.com',
            'id_rol' => $admin->id_rol,
        ]);

        $this->crearPago($otro);

        $this->actingAs($admin);

        $this->delete(route('trabajadores.destroy', $otro))
            ->assertRedirect()
            ->assertSessionHasErrors('trabajador');

        $this->assertNotNull($otro->fresh());
    }

    public function test_no_elimina_un_trabajador_con_reportes_asociados(): void
    {
        $admin = $this->admin();
        $modelo = Trabajador::create([
            'nombre' => 'Ana',
            'apellido' => 'Ruiz',
            'email' => 'ana@test.com',
            'id_rol' => $admin->id_rol,
        ]);

        $metodo = MetodoPago::create(['propietario' => 'Equipo', 'metodo_pago' => 'Transferencia']);

        ReportePago::create([
            'plataforma' => 'OnlyFans',
            'user_cliente' => 'cliente@test.com',
            'precio' => 50,
            'servicio' => 'Chat',
            'fecha_reporte' => '2026-09-01',
            'id_modelo' => $modelo->id_trab,
            'id_moderador' => $admin->id_trab,
            'id_mp' => $metodo->id_mp,
        ]);

        $this->actingAs($admin);

        $this->delete(route('trabajadores.destroy', $modelo))
            ->assertRedirect()
            ->assertSessionHasErrors('trabajador');

        $this->assertNotNull($modelo->fresh());
    }

    public function test_el_error_de_eliminacion_se_muestra_en_el_listado(): void
    {
        $admin = $this->admin();
        $otro = Trabajador::create([
            'nombre' => 'Juan',
            'apellido' => 'Pérez',
            'email' => 'juan@test.com',
            'id_rol' => $admin->id_rol,
        ]);

        $this->crearPago($otro);

        $this->actingAs($admin);

        $this->followingRedirects()
            ->delete(route('trabajadores.destroy', $otro))
            ->assertOk()
            ->assertSee('No se puede eliminar a Juan Pérez');
    }

    public function test_la_ceo_no_aparece_entre_las_modelos_para_moderadores(): void
    {
        $admin = $this->admin();

        Trabajador::create(['nombre' => 'Ceo', 'apellido' => 'Brea', 'id_rol' => Rol::create(['rol' => 'ceo'])->id_rol]);
        Trabajador::create(['nombre' => 'Modelo', 'apellido' => 'Brea', 'id_rol' => Rol::create(['rol' => 'modelo'])->id_rol]);

        $this->actingAs($admin);

        $this->get(route('trabajadores.create'))
            ->assertOk()
            ->assertSee('Modelo Brea')
            ->assertDontSee('Ceo Brea');
    }
}
