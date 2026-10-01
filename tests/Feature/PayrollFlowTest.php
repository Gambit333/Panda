<?php

namespace Tests\Feature;

use App\Models\CierreSemanal;
use App\Models\MetodoPago;
use App\Models\PagoEmpleado;
use App\Models\ReportePago;
use App\Models\Rol;
use App\Models\Trabajador;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
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

        $metodo = MetodoPago::create(['propietario' => 'Equipo', 'metodo_pago' => 'PayPal', 'porcentaje_cuenta' => 10]);

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

        $this->assertEquals(150, $cierre->total_bruto);
        $this->assertEquals(2, $cierre->reportes()->count());
        $this->assertEquals(0, ReportePago::whereNull('id_cierre')->count());

        $this->post('/pagos', [
            'id_trab' => $moderador->id_trab,
            'id_cierre' => $cierre->id_cierre,
            'monto_bruto' => 60,
            'monto_neto' => 60,
            'monto_final' => 60,
        ])->assertSessionHasNoErrors();

        $this->post('/pagos', [
            'id_trab' => $modelo->id_trab,
            'id_cierre' => $cierre->id_cierre,
            'monto_bruto' => 90,
            'monto_neto' => 90,
            'monto_final' => 90,
        ])->assertSessionHasNoErrors();

        $this->assertEquals(2, PagoEmpleado::count());

        $response = $this->get("/cierres/{$cierre->id_cierre}")->assertOk();

        // Cálculo de comisiones por cuenta:
        // facturado 150, impuestos 15% = 22.5, comisión 10% de 150 = 15, resto para Brea = 7.5
        $response
            ->assertSee('TOTAL FACTURADO')
            ->assertSee('150.00')
            ->assertSee('22.50')
            ->assertSee('COMISIÓN TOTAL')
            ->assertSee('15.00')
            ->assertSee('135.00')
            ->assertSee('RESTO PARA BREA')
            ->assertSee('7.50');
    }

    public function test_reporte_adjunta_comprobante_sin_guardarlo_en_la_bd(): void
    {
        Storage::fake('public');

        $rolAdmin = Rol::create(['rol' => 'admin']);
        $usuario = Trabajador::create([
            'nombre' => 'Admin', 'apellido' => 'Root', 'email' => 'admin2@nomina.test',
            'id_rol' => $rolAdmin->id_rol, 'password' => Hash::make('secreto123'),
        ]);
        $this->actingAs($usuario);

        $modelo = Trabajador::create(['nombre' => 'Luis', 'apellido' => 'Gomez', 'id_rol' => Rol::create(['rol' => 'Modelo'])->id_rol]);
        $moderador = Trabajador::create(['nombre' => 'Ana', 'apellido' => 'Perez', 'id_rol' => Rol::create(['rol' => 'Moderador'])->id_rol]);
        $metodo = MetodoPago::create(['propietario' => 'Equipo', 'metodo_pago' => 'PayPal']);

        $campos = [
            'id_modelo' => $modelo->id_trab,
            'plataforma' => 'OnlyFans',
            'user_cliente' => 'cliente1',
            'id_mp' => $metodo->id_mp,
            'precio' => 100,
            'servicio' => 'Video privado',
            'fecha_reporte' => '2026-09-10',
            'id_moderador' => $moderador->id_trab,
        ];

        $this->post('/reportes', $campos + [
            'comprobante' => $this->pngDePrueba(),
        ])->assertSessionHasNoErrors();

        /** @var ReportePago $reporte */
        $reporte = ReportePago::firstOrFail();
        $ruta = $reporte->comprobante;

        $this->assertNotNull($reporte->comprobante);
        $this->assertStringContainsString('comprobantes/', $reporte->comprobante);
        Storage::disk('public')->assertExists($reporte->comprobante);

        $this->put("/reportes/{$reporte->id_reporte}", $campos + [
            'eliminar_comprobante' => '1',
        ])->assertSessionHasNoErrors();

        $reporte->refresh();
        $this->assertNull($reporte->comprobante);
        Storage::disk('public')->assertMissing($ruta);
    }

    private function pngDePrueba(): UploadedFile
    {
        $tmp = tempnam(sys_get_temp_dir(), 'comprobante').'.png';
        file_put_contents($tmp, base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII='
        ));

        return new UploadedFile($tmp, 'comprobante.png', 'image/png', null, true);
    }
}
