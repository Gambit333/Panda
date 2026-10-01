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

        // Modelo normal: total de ventas 100, número final 100 - 15% = 85 (la comisión ya se calculó aparte)
        //   modelo 50% = 42.50 | moderador 20% = 17.00 | admin 30% = 25.50
        $this->post('/reportes', [
            'id_modelo' => $modelo->id_trab, 'plataforma' => 'OnlyFans', 'user_cliente' => 'cli1',
            'id_mp' => $metodo->id_mp, 'precio' => 100, 'servicio' => 'Video',
            'fecha_reporte' => '2026-09-10', 'id_moderador' => $moderador->id_trab,
        ])->assertSessionHasNoErrors();

        // Maria Brea como modelo: total de ventas 200, número final 200 - 15% = 170
        //   Brea 75% = 127.50 | moderador 18% = 30.60 | Pinto 7% = 11.90
        $this->post('/reportes', [
            'id_modelo' => $brea->id_trab, 'plataforma' => 'OnlyFans', 'user_cliente' => 'cli2',
            'id_mp' => $metodo->id_mp, 'precio' => 200, 'servicio' => 'Chat',
            'fecha_reporte' => '2026-09-11', 'id_moderador' => $moderador->id_trab,
        ])->assertSessionHasNoErrors();

        $this->post('/cierres', ['fecha_inicio' => '2026-09-07', 'fecha_fin' => '2026-09-13'])
            ->assertSessionHasNoErrors();

        $cierre = CierreSemanal::firstOrFail();

        // Fondo administrativo = 30% del número final de la modelo normal = 25.50
        //   ceo 20% = 5.10 | Pinto 7% = 1.79 | cada admin 1.5% = 0.38
        $this->assertEquals(9, DetallePagoCierre::count());

        $monto = fn (int $idTrab, string $concepto) => DetallePagoCierre::where('id_cierre', $cierre->id_cierre)
            ->where('id_trab', $idTrab)->where('concepto', $concepto)->value('monto');

        $this->assertEquals(42.50, $monto($modelo->id_trab, 'modelo'));
        $this->assertEquals(127.50, $monto($brea->id_trab, 'modelo'));
        $this->assertEquals(11.90, $monto($pinto->id_trab, 'pinto'));
        $this->assertEquals(5.10, $monto($brea->id_trab, 'admin'));
        $this->assertEquals(1.79, $monto($pinto->id_trab, 'admin'));
        $this->assertEquals(0.38, $monto($juanH->id_trab, 'admin'));
        $this->assertEquals(0.38, $monto($juanB->id_trab, 'admin'));
        $this->assertEquals(0.38, $monto($root->id_trab, 'admin'));

        // Ana es moderadora en ambos reportes: 20% de Lucia (17.00) + 18% de Brea (30.60)
        $this->assertEquals(47.60, $monto($moderador->id_trab, 'moderador'));

        // Totales por trabajador: total de ventas antes de impuestos (solo informativo)
        // y el número final con el 15% de impuestos (después de impuestos).
        $totales = fn (int $idTrab) => DetallePagoCierre::where('id_cierre', $cierre->id_cierre)
            ->where('id_trab', $idTrab)
            ->first(['total_antes_impuestos', 'total_despues_impuestos']);

        $this->assertEquals('100.00', $totales($modelo->id_trab)->total_antes_impuestos);
        $this->assertEquals('85.00', $totales($modelo->id_trab)->total_despues_impuestos);

        $this->assertEquals('200.00', $totales($brea->id_trab)->total_antes_impuestos);
        $this->assertEquals('170.00', $totales($brea->id_trab)->total_despues_impuestos);

        // Moderadora de los dos reportes: 100 + 200 antes, 85 + 170 después.
        $this->assertEquals('300.00', $totales($moderador->id_trab)->total_antes_impuestos);
        $this->assertEquals('255.00', $totales($moderador->id_trab)->total_despues_impuestos);

        // Solo cobra del fondo administrativo: no tiene reportes propios.
        $this->assertEquals('0.00', $totales($juanH->id_trab)->total_antes_impuestos);
        $this->assertEquals('0.00', $totales($juanH->id_trab)->total_despues_impuestos);

        $response = $this->get("/cierres/{$cierre->id_cierre}")->assertOk();
        $response
            ->assertSee('Pagos calculados')
            ->assertSee('Total antes de impuestos')
            ->assertSee('Total después de impuestos')
            ->assertSee('Maria Brea')
            ->assertSee('Maria Pinto')
            ->assertSee('TOTAL A PAGAR');
    }
}
