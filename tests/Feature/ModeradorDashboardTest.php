<?php

namespace Tests\Feature;

use App\Models\MetodoPago;
use App\Models\ReportePago;
use App\Models\Rol;
use App\Models\Trabajador;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ModeradorDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_moderador_ve_cuanto_ha_ganado_cada_modelo_asignada(): void
    {
        $moderador = $this->trabajador('Luis', 'Mod', 'moderador');
        $modeloA = $this->trabajador('Ana', 'Rios', 'modelo');
        $modeloB = $this->trabajador('Bety', 'Lara', 'modelo');
        $modeloC = $this->trabajador('Ceci', 'Mora', 'modelo');
        $metodo = MetodoPago::create(['propietario' => 'Equipo', 'metodo_pago' => 'PayPal']);

        $this->asignar($moderador->id_trab, $modeloA->id_trab);
        $this->asignar($moderador->id_trab, $modeloB->id_trab);
        $this->asignar($moderador->id_trab, $modeloC->id_trab);

        $this->reporte($moderador, $modeloA, $metodo, 'cliente-1', 100);
        $this->reporte($moderador, $modeloA, $metodo, 'cliente-2', 150);
        $this->reporte($moderador, $modeloB, $metodo, 'cliente-3', 50);

        $this->actingAs($moderador);

        $respuesta = $this->get('/');

        $respuesta->assertOk();
        $respuesta->assertSee('Ganancias de mis modelos');
        $respuesta->assertSee('Ana Rios');
        $respuesta->assertSee('Bety Lara');
        $respuesta->assertSee('Ceci Mora');
        $respuesta->assertSee('250.00');
        $respuesta->assertSee('50.00');
    }

    public function test_la_ganancia_solo_cuenta_los_reportes_del_moderador(): void
    {
        $moderador = $this->trabajador('Luis', 'Mod', 'moderador');
        $otro = $this->trabajador('Otro', 'Mod', 'moderador');
        $modelo = $this->trabajador('Ana', 'Rios', 'modelo');
        $metodo = MetodoPago::create(['propietario' => 'Equipo', 'metodo_pago' => 'PayPal']);

        $this->asignar($moderador->id_trab, $modelo->id_trab);

        $this->reporte($moderador, $modelo, $metodo, 'cliente-1', 100);
        $this->reporte($otro, $modelo, $metodo, 'cliente-2', 999);

        $this->actingAs($moderador);

        $this->get('/')
            ->assertOk()
            ->assertSee('Ganancias de mis modelos')
            ->assertSee('100.00')
            ->assertDontSee('999.00');
    }

    public function test_el_moderador_ve_la_modelo_en_mis_ganancias_recientes(): void
    {
        $moderador = $this->trabajador('Luis', 'Mod', 'moderador');
        $modelo = $this->trabajador('Ana', 'Rios', 'modelo');
        $metodo = MetodoPago::create(['propietario' => 'Equipo', 'metodo_pago' => 'PayPal']);

        $this->reporte($moderador, $modelo, $metodo, 'cliente-1', 100);

        $this->actingAs($moderador);

        $this->get('/')
            ->assertOk()
            ->assertSee('<th>Modelo</th>', false)
            ->assertSee('Ana Rios');
    }

    public function test_la_modelo_no_ve_la_tarjeta_de_ganancias(): void
    {
        $modelo = $this->trabajador('Ana', 'Rios', 'modelo');

        $this->actingAs($modelo);

        $this->get('/')
            ->assertOk()
            ->assertDontSee('Ganancias de mis modelos');
    }

    private function asignar(int $idModerador, int $idModelo): void
    {
        DB::table('modelo_moderador')->insert([
            'id_moderador' => $idModerador,
            'id_modelo' => $idModelo,
        ]);
    }

    private function reporte(Trabajador $moderador, Trabajador $modelo, MetodoPago $metodo, string $cliente, float $precio): ReportePago
    {
        return ReportePago::create([
            'id_modelo' => $modelo->id_trab,
            'id_moderador' => $moderador->id_trab,
            'plataforma' => 'OnlyFans',
            'user_cliente' => $cliente,
            'id_mp' => $metodo->id_mp,
            'precio' => $precio,
            'servicio' => 'Video privado',
        ]);
    }

    private function trabajador(string $nombre, string $apellido, string $rolNombre): Trabajador
    {
        $rol = Rol::firstOrCreate(['rol' => $rolNombre]);

        return Trabajador::create([
            'nombre' => $nombre,
            'apellido' => $apellido,
            'email' => strtolower($nombre.$apellido).'.'.uniqid().'@nomina.test',
            'id_rol' => $rol->id_rol,
        ]);
    }
}
