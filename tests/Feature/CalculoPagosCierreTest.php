<?php

namespace Tests\Feature;

use App\Models\CierreSemanal;
use App\Models\DetallePagoCierre;
use App\Models\MetodoPago;
use App\Models\Rol;
use App\Models\Trabajador;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CalculoPagosCierreTest extends TestCase
{
    use RefreshDatabase;

    public function test_calcula_pagos_de_modelo_moderador_y_seccion_administrativa(): void
    {
        $rolAdmin = Rol::create(['rol' => 'admin']);
        $rolCeo = Rol::create(['rol' => 'ceo']);
        $rolModelo = Rol::create(['rol' => 'modelo']);
        $rolModerador = Rol::create(['rol' => 'moderador']);

        $this->actingAs($root = Trabajador::create([
            'nombre' => 'Root', 'apellido' => 'Admin', 'email' => 'root@nomina.test',
            'id_rol' => $rolAdmin->id_rol, 'password' => Hash::make('secreto123'),
        ]));

        $brea = Trabajador::create(['nombre' => 'Maria', 'apellido' => 'Brea', 'id_rol' => $rolCeo->id_rol]);
        $pinto = Trabajador::create(['nombre' => 'Maria', 'apellido' => 'Pinto', 'id_rol' => $rolModerador->id_rol]);
        $juanH = Trabajador::create(['nombre' => 'Juan', 'apellido' => 'Hernandez', 'id_rol' => $rolAdmin->id_rol]);
        $juanB = Trabajador::create(['nombre' => 'Juan', 'apellido' => 'Brea', 'id_rol' => $rolAdmin->id_rol]);
        $modelo = Trabajador::create(['nombre' => 'Lucia', 'apellido' => 'Rios', 'id_rol' => $rolModelo->id_rol]);
        $moderador = Trabajador::create(['nombre' => 'Ana', 'apellido' => 'Perez', 'id_rol' => $rolModerador->id_rol]);

        $metodo = MetodoPago::create(['propietario' => 'Equipo', 'metodo_pago' => 'PayPal', 'porcentaje_cuenta' => 10]);

        // Modelo normal: neto 90 (100 - 10%), base = 90 * 0.85 = 76.5
        //   modelo 50% = 38.25 | moderador 20% = 15.30 | admin 30% = 22.95
        $this->post('/reportes', [
            'id_modelo' => $modelo->id_trab, 'plataforma' => 'OnlyFans', 'user_cliente' => 'cli1',
            'id_mp' => $metodo->id_mp, 'precio' => 100, 'servicio' => 'Video',
            'fecha_reporte' => '2026-09-10', 'id_moderador' => $moderador->id_trab,
        ])->assertSessionHasNoErrors();

        // Maria Brea como modelo: neto 180 (200 - 10%), base = 180 * 0.85 = 153
        //   Brea 75% = 114.75 | moderador 18% = 27.54 | Pinto 7% = 10.71
        $this->post('/reportes', [
            'id_modelo' => $brea->id_trab, 'plataforma' => 'OnlyFans', 'user_cliente' => 'cli2',
            'id_mp' => $metodo->id_mp, 'precio' => 200, 'servicio' => 'Chat',
            'fecha_reporte' => '2026-09-11', 'id_moderador' => $moderador->id_trab,
        ])->assertSessionHasNoErrors();

        $this->post('/cierres', ['fecha_inicio' => '2026-09-07', 'fecha_fin' => '2026-09-13'])
            ->assertSessionHasNoErrors();

        $cierre = CierreSemanal::firstOrFail();

        // Fondo administrativo = 30% de la base de la modelo normal = 22.95
        //   ceo 20% = 4.59 | Pinto 7% = 1.61 | cada admin 1.5% = 0.34
        $this->assertEquals(9, DetallePagoCierre::count());

        $monto = fn (int $idTrab, string $concepto) => DetallePagoCierre::where('id_cierre', $cierre->id_cierre)
            ->where('id_trab', $idTrab)->where('concepto', $concepto)->value('monto');

        $this->assertEquals(38.25, $monto($modelo->id_trab, 'modelo'));
        $this->assertEquals(114.75, $monto($brea->id_trab, 'modelo'));
        $this->assertEquals(10.71, $monto($pinto->id_trab, 'pinto'));
        $this->assertEquals(4.59, $monto($brea->id_trab, 'admin'));
        $this->assertEquals(1.61, $monto($pinto->id_trab, 'admin'));
        $this->assertEquals(0.34, $monto($juanH->id_trab, 'admin'));
        $this->assertEquals(0.34, $monto($juanB->id_trab, 'admin'));
        $this->assertEquals(0.34, $monto($root->id_trab, 'admin'));

        // Ana es moderadora en ambos reportes: 20% de Lucia (15.30) + 18% de Brea (27.54)
        $this->assertEquals(42.84, $monto($moderador->id_trab, 'moderador'));

        $response = $this->get("/cierres/{$cierre->id_cierre}")->assertOk();
        $response
            ->assertSee('Pagos calculados')
            ->assertSee('Maria Brea')
            ->assertSee('Maria Pinto')
            ->assertSee('TOTAL A PAGAR');
    }
}
