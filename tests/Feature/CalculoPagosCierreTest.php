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
        $rolProgramador = Rol::create(['rol' => 'programador']);

        $this->actingAs($root = Trabajador::create([
            'nombre' => 'Root', 'apellido' => 'Admin', 'email' => 'root@nomina.test',
            'id_rol' => $rolAdmin->id_rol, 'password' => Hash::make('secreto123'),
        ]));

        $brea = Trabajador::create(['nombre' => 'Maria', 'apellido' => 'Brea', 'id_rol' => $rolCeo->id_rol]);
        // María Pinto ya no tiene caso especial: cobra de la sección administrativa
        // como cualquier admin, con el porcentaje que se le defina en su ficha.
        $pinto = Trabajador::create(['nombre' => 'Maria', 'apellido' => 'Pinto', 'id_rol' => $rolAdmin->id_rol, 'porcentaje' => 7]);
        $juanH = Trabajador::create(['nombre' => 'Juan', 'apellido' => 'Hernandez', 'id_rol' => $rolAdmin->id_rol]);
        $juanB = Trabajador::create(['nombre' => 'Juan', 'apellido' => 'Brea', 'id_rol' => $rolAdmin->id_rol]);
        $dev = Trabajador::create(['nombre' => 'Devi', 'apellido' => 'Ramos', 'id_rol' => $rolProgramador->id_rol]);
        $rolSupport = Rol::create(['rol' => 'support']);
        $soporte = Trabajador::create(['nombre' => 'Sofi', 'apellido' => 'Marín', 'id_rol' => $rolSupport->id_rol]);
        $modelo = Trabajador::create(['nombre' => 'Lucia', 'apellido' => 'Rios', 'id_rol' => $rolModelo->id_rol]);
        $moderador = Trabajador::create(['nombre' => 'Ana', 'apellido' => 'Perez', 'id_rol' => $rolModerador->id_rol]);

        $metodo = MetodoPago::create(['propietario' => 'Equipo', 'metodo_pago' => 'PayPal', 'porcentaje_cuenta' => 10]);

        // Modelo normal: total de ventas 100, número final 100 - 15% = 85 (la comisión ya se calculó aparte)
        //   modelo 50% = 42.50 | moderador 20% = 17.00
        $this->post('/reportes', [
            'id_modelo' => $modelo->id_trab, 'plataforma' => 'OnlyFans', 'user_cliente' => 'cli1',
            'id_mp' => $metodo->id_mp, 'precio' => 100, 'servicio' => 'Video',
            'fecha_reporte' => '2026-09-10', 'id_moderador' => $moderador->id_trab,
        ])->assertSessionHasNoErrors();

        // María Brea como modelo NO es un caso aparte: cobra igual que cualquier modelo.
        // Total de ventas 200, número final 200 - 15% = 170
        //   modelo 50% = 85.00 | moderador 20% = 34.00
        $this->post('/reportes', [
            'id_modelo' => $brea->id_trab, 'plataforma' => 'OnlyFans', 'user_cliente' => 'cli2',
            'id_mp' => $metodo->id_mp, 'precio' => 200, 'servicio' => 'Chat',
            'fecha_reporte' => '2026-09-11', 'id_moderador' => $moderador->id_trab,
        ])->assertSessionHasNoErrors();

        $this->post('/cierres', ['fecha_inicio' => '2026-09-07', 'fecha_fin' => '2026-09-13'])
            ->assertSessionHasNoErrors();

        $cierre = CierreSemanal::firstOrFail();

        // Sección administrativa: NO es lo que sobra de las modelos, se calcula sobre
        // el total sin impuestos (85 + 170 = 255):
        //   ceo 20% = 51.00 | Pinto 7% (su porcentaje) = 17.85
        //   admin 1.5% = 3.83 | support 1.5% = 3.83 | programador 1.5% = 3.83
        $this->assertEquals(10, DetallePagoCierre::count());
        $this->assertEquals(0, DetallePagoCierre::where('concepto', 'pinto')->count());

        $monto = fn (int $idTrab, string $concepto) => DetallePagoCierre::where('id_cierre', $cierre->id_cierre)
            ->where('id_trab', $idTrab)->where('concepto', $concepto)->value('monto');

        $this->assertEquals(42.50, $monto($modelo->id_trab, 'modelo'));
        $this->assertEquals(85.00, $monto($brea->id_trab, 'modelo'));
        $this->assertEquals(51.00, $monto($brea->id_trab, 'admin'));
        $this->assertEquals(17.85, $monto($pinto->id_trab, 'admin'));
        $this->assertEquals(3.83, $monto($root->id_trab, 'admin'));
        $this->assertEquals(3.83, $monto($juanH->id_trab, 'admin'));
        $this->assertEquals(3.83, $monto($juanB->id_trab, 'admin'));
        $this->assertEquals(3.83, $monto($dev->id_trab, 'programador'));
        $this->assertEquals(3.83, $monto($soporte->id_trab, 'admin'));
        $this->assertEquals('1.5% del total sin impuestos (rol support)', DetallePagoCierre::where('id_cierre', $cierre->id_cierre)
            ->where('id_trab', $soporte->id_trab)->value('nota'));

        // Ana es moderadora en ambos reportes: 20% de Lucia (17.00) + 20% de Brea (34.00)
        $this->assertEquals(51.00, $monto($moderador->id_trab, 'moderador'));

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

        // Solo cobra de la sección administrativa: no tiene reportes propios.
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

    public function test_usa_el_porcentaje_propio_de_cada_trabajador(): void
    {
        $rolAdmin = Rol::create(['rol' => 'admin']);
        $rolCeo = Rol::create(['rol' => 'ceo']);
        $rolModelo = Rol::create(['rol' => 'modelo']);
        $rolModerador = Rol::create(['rol' => 'moderador']);
        $rolProgramador = Rol::create(['rol' => 'programador']);

        $this->actingAs($root = Trabajador::create([
            'nombre' => 'Root', 'apellido' => 'Admin', 'email' => 'root@nomina.test',
            'id_rol' => $rolAdmin->id_rol, 'password' => Hash::make('secreto123'),
            // Su parte de la sección administrativa: 10% (por defecto sería 1.5%)
            'porcentaje' => 10,
        ]));

        $brea = Trabajador::create(['nombre' => 'Maria', 'apellido' => 'Brea', 'id_rol' => $rolCeo->id_rol]);
        $pinto = Trabajador::create([
            'nombre' => 'Maria', 'apellido' => 'Pinto', 'id_rol' => $rolProgramador->id_rol,
            'porcentaje' => 12,
        ]);
        $modelo = Trabajador::create([
            'nombre' => 'Lucia', 'apellido' => 'Rios', 'id_rol' => $rolModelo->id_rol,
            'porcentaje' => 60,
        ]);
        $moderador = Trabajador::create([
            'nombre' => 'Ana', 'apellido' => 'Perez', 'id_rol' => $rolModerador->id_rol,
            'porcentaje' => 30,
        ]);

        $metodo = MetodoPago::create(['propietario' => 'Equipo', 'metodo_pago' => 'PayPal', 'porcentaje_cuenta' => 10]);

        // Ventas 200, número final 200 - 15% = 170
        //   modelo 60% = 102.00 | moderador 30% = 51.00
        //   sección administrativa sobre el total sin impuestos (170):
        //   ceo 20% = 34.00 | admin 10% = 17.00 | programador 12% = 20.40
        $this->post('/reportes', [
            'id_modelo' => $modelo->id_trab, 'plataforma' => 'OnlyFans', 'user_cliente' => 'cli1',
            'id_mp' => $metodo->id_mp, 'precio' => 200, 'servicio' => 'Video',
            'fecha_reporte' => '2026-09-10', 'id_moderador' => $moderador->id_trab,
        ])->assertSessionHasNoErrors();

        $this->post('/cierres', ['fecha_inicio' => '2026-09-07', 'fecha_fin' => '2026-09-13'])
            ->assertSessionHasNoErrors();

        $cierre = CierreSemanal::firstOrFail();

        $this->assertEquals(5, DetallePagoCierre::count());

        $fila = fn (int $idTrab, string $concepto) => DetallePagoCierre::where('id_cierre', $cierre->id_cierre)
            ->where('id_trab', $idTrab)->where('concepto', $concepto)->first();

        $this->assertEquals(102.00, $fila($modelo->id_trab, 'modelo')->monto);
        $this->assertEquals('60% de sus ganancias como modelo', $fila($modelo->id_trab, 'modelo')->nota);
        $this->assertEquals(51.00, $fila($moderador->id_trab, 'moderador')->monto);
        $this->assertEquals(34.00, $fila($brea->id_trab, 'admin')->monto);
        $this->assertEquals(20.40, $fila($pinto->id_trab, 'programador')->monto);
        $this->assertEquals('12% del total sin impuestos (rol programador)', $fila($pinto->id_trab, 'programador')->nota);
        $this->assertEquals(17.00, $fila($root->id_trab, 'admin')->monto);
        $this->assertEquals('10% del total sin impuestos (cada admin)', $fila($root->id_trab, 'admin')->nota);
    }

    public function test_moderador_cobra_porcentaje_distinto_por_cada_modelo(): void
    {
        $rolAdmin = Rol::create(['rol' => 'admin']);
        $rolModelo = Rol::create(['rol' => 'modelo']);
        $rolModerador = Rol::create(['rol' => 'moderador']);

        $this->actingAs($root = Trabajador::create([
            'nombre' => 'Root', 'apellido' => 'Admin', 'email' => 'root@nomina.test',
            'id_rol' => $rolAdmin->id_rol, 'password' => Hash::make('secreto123'),
        ]));

        $ana = Trabajador::create(['nombre' => 'Ana', 'apellido' => 'Perez', 'id_rol' => $rolModerador->id_rol]);
        $lucia = Trabajador::create(['nombre' => 'Lucia', 'apellido' => 'Rios', 'id_rol' => $rolModelo->id_rol]);
        $maria = Trabajador::create(['nombre' => 'Maria', 'apellido' => 'Brea', 'id_rol' => $rolModelo->id_rol]);

        // Ana cobra distinto según la modelo: 25% con Lucia y 15% con María.
        $ana->modelosAsignadas()->sync([
            $lucia->id_trab => ['porcentaje' => 25],
            $maria->id_trab => ['porcentaje' => 15],
        ]);

        $metodo = MetodoPago::create(['propietario' => 'Equipo', 'metodo_pago' => 'PayPal']);

        // Cada modelo vende 100 -> número final 85.
        $this->post('/reportes', [
            'id_modelo' => $lucia->id_trab, 'plataforma' => 'OnlyFans', 'user_cliente' => 'cli1',
            'id_mp' => $metodo->id_mp, 'precio' => 100, 'servicio' => 'Video',
            'fecha_reporte' => '2026-09-10', 'id_moderador' => $ana->id_trab,
        ])->assertSessionHasNoErrors();

        $this->post('/reportes', [
            'id_modelo' => $maria->id_trab, 'plataforma' => 'OnlyFans', 'user_cliente' => 'cli2',
            'id_mp' => $metodo->id_mp, 'precio' => 100, 'servicio' => 'Chat',
            'fecha_reporte' => '2026-09-11', 'id_moderador' => $ana->id_trab,
        ])->assertSessionHasNoErrors();

        $this->post('/cierres', ['fecha_inicio' => '2026-09-07', 'fecha_fin' => '2026-09-13'])
            ->assertSessionHasNoErrors();

        $cierre = CierreSemanal::firstOrFail();

        // 2 modelos + 1 moderador + el admin raíz (1.5% de 170) = 4 filas.
        $this->assertEquals(4, DetallePagoCierre::count());

        $fila = fn (int $idTrab, string $concepto) => DetallePagoCierre::where('id_cierre', $cierre->id_cierre)
            ->where('id_trab', $idTrab)->where('concepto', $concepto)->first();

        $this->assertEquals(42.50, $fila($lucia->id_trab, 'modelo')->monto);
        $this->assertEquals(42.50, $fila($maria->id_trab, 'modelo')->monto);

        // Moderadora: 85 x 25% (Lucia) + 85 x 15% (María) = 21.25 + 12.75 = 34.00
        $this->assertEquals(34.00, $fila($ana->id_trab, 'moderador')->monto);
        $this->assertStringContainsString('Lucia Rios', $fila($ana->id_trab, 'moderador')->nota);
        // Sección administrativa sobre el total sin impuestos (170): admin 1.5% = 2.55
        $this->assertEquals(2.55, $fila($root->id_trab, 'admin')->monto);
    }
}
