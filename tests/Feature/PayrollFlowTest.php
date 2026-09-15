<?php

namespace Tests\Feature;

use App\Models\CierreSemanal;
use App\Models\MetodoPago;
use App\Models\PagoEmpleado;
use App\Models\ReportePago;
use App\Models\Rol;
use App\Models\Trabajador;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PayrollFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_complete_payroll_flow(): void
    {
        $rolModerador = Rol::create(['rol' => 'Moderador']);
        $rolModelo = Rol::create(['rol' => 'Modelo']);
        $rolAdmin = Rol::create(['rol' => 'admin']);

        $usuario = Trabajador::create([
            'nombre' => 'Admin', 'apellido' => 'Root', 'email' => 'admin@nomina.test',
            'id_rol' => $rolAdmin->id_rol, 'password' => Hash::make('secreto123'),
        ]);
        $this->actingAs($usuario);

        $moderador = Trabajador::create([
            'nombre' => 'Ana', 'apellido' => 'Perez', 'id_rol' => $rolModerador->id_rol,
        ]);
        $modelo = Trabajador::create([
            'nombre' => 'Luis', 'apellido' => 'Gomez', 'id_rol' => $rolModelo->id_rol,
        ]);

        $metodo = MetodoPago::create(['metodo_pago' => 'PayPal']);

        $this->post('/reportes', [
            'id_modelo' => $modelo->id_trab,
            'plataforma' => 'OnlyFans',
            'user_cliente' => 'cliente1',
            'id_mp' => $metodo->id_mp,
            'precio' => 100,
            'servicio' => 'Video privado',
            'duracion' => '30 min',
            'fecha_reporte' => '2026-09-10',
            'id_moderador' => $moderador->id_trab,
            'descripcion' => 'Test',
        ])->assertSessionHasNoErrors();

        $this->post('/reportes', [
            'id_modelo' => $modelo->id_trab,
            'plataforma' => 'OnlyFans',
            'user_cliente' => 'cliente2',
            'id_mp' => $metodo->id_mp,
            'precio' => 50,
            'servicio' => 'Chat',
            'fecha_reporte' => '2026-09-11',
            'id_moderador' => $moderador->id_trab,
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseCount('reporte_pagos', 2);

        $this->post('/cierres', [
            'fecha_inicio' => '2026-09-07',
            'fecha_fin' => '2026-09-13',
        ])->assertSessionHasNoErrors();

        $cierre = CierreSemanal::firstOrFail();

        $this->assertEquals(150, $cierre->total);
        $this->assertEquals(2, $cierre->reportes()->count());
        $this->assertEquals(0, ReportePago::whereNull('id_cierre')->count());

        $this->post('/pagos', [
            'id_trab' => $moderador->id_trab,
            'id_cierre' => $cierre->id_cierre,
            'monto' => 60,
        ])->assertSessionHasNoErrors();

        $this->post('/pagos', [
            'id_trab' => $modelo->id_trab,
            'id_cierre' => $cierre->id_cierre,
            'monto' => 90,
        ])->assertSessionHasNoErrors();

        $this->assertEquals(2, PagoEmpleado::count());

        $this->get("/cierres/{$cierre->id_cierre}")->assertOk();
    }
}
