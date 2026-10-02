<?php

namespace Tests\Feature;

use App\Models\MetodoPago;
use App\Models\ReportePago;
use App\Models\Rol;
use App\Models\Trabajador;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ReportesPorModeradorTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_moderador_solo_ve_sus_propios_reportes(): void
    {
        $moderador = $this->trabajador('Luis', 'Mod', 'moderador');
        $otro = $this->trabajador('Otro', 'Mod', 'moderador');
        $modelo = $this->trabajador('Ana', 'Modelo', 'modelo');
        $metodo = MetodoPago::create(['propietario' => 'Equipo', 'metodo_pago' => 'PayPal']);

        $this->reporte($moderador, $modelo, $metodo, 'cliente-mio', 100);
        $this->reporte($otro, $modelo, $metodo, 'cliente-ajeno', 999);

        $this->actingAs($moderador);

        $response = $this->get('/reportes');
        $response->assertOk();
        $response->assertSee('cliente-mio');
        $response->assertSee('100.00');
        $response->assertDontSee('cliente-ajeno');
        $response->assertDontSee('999.00');
        $response->assertSee('Aquí ves únicamente los reportes de pago que tú registraste.');

        // Ambas filas siguen en la BD: solo se filtró la vista.
        $this->assertSame(2, ReportePago::count());
    }

    public function test_el_admin_sigue_viendo_los_reportes_de_todos_los_moderadores(): void
    {
        $moderador = $this->trabajador('Luis', 'Mod', 'moderador');
        $otro = $this->trabajador('Otro', 'Mod', 'moderador');
        $modelo = $this->trabajador('Ana', 'Modelo', 'modelo');
        $metodo = MetodoPago::create(['propietario' => 'Equipo', 'metodo_pago' => 'PayPal']);

        $this->reporte($moderador, $modelo, $metodo, 'cliente-mio', 100);
        $this->reporte($otro, $modelo, $metodo, 'cliente-ajeno', 999);

        $this->actingAs($this->trabajador('Root', 'Admin', 'admin'));

        $this->get('/reportes')
            ->assertOk()
            ->assertSee('cliente-mio')
            ->assertSee('cliente-ajeno')
            ->assertSee('Luis Mod')
            ->assertSee('Otro Mod');
    }

    public function test_el_moderador_no_puede_editar_ni_borrar_el_reporte_de_otro(): void
    {
        $moderador = $this->trabajador('Luis', 'Mod', 'moderador');
        $otro = $this->trabajador('Otro', 'Mod', 'moderador');
        $modelo = $this->trabajador('Ana', 'Modelo', 'modelo');
        $metodo = MetodoPago::create(['propietario' => 'Equipo', 'metodo_pago' => 'PayPal']);

        $ajeno = $this->reporte($otro, $modelo, $metodo, 'cliente-ajeno', 999);
        $propio = $this->reporte($moderador, $modelo, $metodo, 'cliente-mio', 100);

        $this->actingAs($moderador);

        $this->get("/reportes/{$ajeno->id_reporte}/edit")
            ->assertRedirect('/reportes')
            ->assertSessionHasErrors('access');

        $this->put("/reportes/{$ajeno->id_reporte}", $this->datos($modelo, $metodo, $otro, 'hackeado', 1))
            ->assertRedirect('/reportes');
        $this->assertSame('cliente-ajeno', $ajeno->fresh()->user_cliente);

        $this->delete("/reportes/{$ajeno->id_reporte}")->assertRedirect('/reportes');
        $this->assertDatabaseHas('reporte_pagos', ['id_reporte' => $ajeno->id_reporte]);

        // Los suyos sí puede editarlos y borrarlos.
        $this->get("/reportes/{$propio->id_reporte}/edit")->assertOk();
        $this->delete("/reportes/{$propio->id_reporte}")->assertRedirect('/reportes');
        $this->assertDatabaseMissing('reporte_pagos', ['id_reporte' => $propio->id_reporte]);
    }

    public function test_al_crear_un_reporte_el_moderador_queda_siempre_como_moderador(): void
    {
        $moderador = $this->trabajador('Luis', 'Mod', 'moderador');
        $otro = $this->trabajador('Otro', 'Mod', 'moderador');
        $modelo = $this->trabajador('Ana', 'Modelo', 'modelo');
        $metodo = MetodoPago::create(['propietario' => 'Equipo', 'metodo_pago' => 'PayPal']);

        DB::table('modelo_moderador')->insert([
            'id_modelo' => $modelo->id_trab, 'id_moderador' => $moderador->id_trab,
        ]);

        $this->actingAs($moderador);

        // Intenta colarse con el id de otro moderador.
        $this->post('/reportes', $this->datos($modelo, $metodo, $otro, 'colado', 50))->assertRedirect('/reportes');

        $reporte = ReportePago::firstOrFail();
        $this->assertSame($moderador->id_trab, $reporte->id_moderador);
    }

    private function datos(Trabajador $modelo, MetodoPago $metodo, Trabajador $moderador, string $cliente, float $precio): array
    {
        return [
            'id_modelo' => $modelo->id_trab,
            'id_moderador' => $moderador->id_trab,
            'plataforma' => 'OnlyFans',
            'user_cliente' => $cliente,
            'id_mp' => $metodo->id_mp,
            'precio' => $precio,
            'servicio' => 'Video privado',
        ];
    }

    private function reporte(Trabajador $moderador, Trabajador $modelo, MetodoPago $metodo, string $cliente, float $precio): ReportePago
    {
        return ReportePago::create($this->datos($modelo, $metodo, $moderador, $cliente, $precio));
    }

    private function trabajador(string $nombre, string $apellido, string $rolNombre): Trabajador
    {
        $rol = Rol::firstOrCreate(['rol' => $rolNombre]);

        return Trabajador::create([
            'nombre' => $nombre, 'apellido' => $apellido,
            'email' => strtolower($nombre.$apellido).'.'.uniqid().'@nomina.test',
            'id_rol' => $rol->id_rol,
        ]);
    }
}
