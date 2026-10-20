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

        // Sección administrativa: NO es lo que sobra de las modelos. Al total sin
        // impuestos (85 + 170 = 255) se le restan las ventas netas de los
        // administrativos que también participaron como modelo/moderador. Solo Brea
        // (ceo + modelo) vendió: 170, así que la base compartida es 85 y la usan
        // TODOS los administrativos. Excepción: María Pinto calcula del total (255).
        //   ceo 20% de 85 = 17.00 | Pinto 7% de 255 = 17.85
        //   admin 1.5% de 85 = 1.28 | support 1.5% = 1.28 | programador 1.5% = 1.28
        $this->assertEquals(10, DetallePagoCierre::count());
        $this->assertEquals(0, DetallePagoCierre::where('concepto', 'pinto')->count());

        $monto = fn (int $idTrab, string $concepto) => DetallePagoCierre::where('id_cierre', $cierre->id_cierre)
            ->where('id_trab', $idTrab)->where('concepto', $concepto)->value('monto');

        $this->assertEquals(42.50, $monto($modelo->id_trab, 'modelo'));
        $this->assertEquals(85.00, $monto($brea->id_trab, 'modelo'));
        $this->assertEquals(17.00, $monto($brea->id_trab, 'admin'));
        $this->assertEquals('20% del total sin impuestos completo (rol ceo)', DetallePagoCierre::where('id_cierre', $cierre->id_cierre)
            ->where('id_trab', $brea->id_trab)->where('concepto', 'admin')->value('nota'));
        $this->assertEquals(17.85, $monto($pinto->id_trab, 'admin'));
        $this->assertEquals('7% del total sin impuestos completo (excepción) (cada admin)', DetallePagoCierre::where('id_cierre', $cierre->id_cierre)
            ->where('id_trab', $pinto->id_trab)->where('concepto', 'admin')->value('nota'));
        $this->assertEquals(1.28, $monto($root->id_trab, 'admin'));
        $this->assertEquals(1.28, $monto($juanH->id_trab, 'admin'));
        $this->assertEquals(1.28, $monto($juanB->id_trab, 'admin'));
        $this->assertEquals(1.28, $monto($dev->id_trab, 'programador'));
        $this->assertEquals(1.28, $monto($soporte->id_trab, 'admin'));
        $this->assertEquals('1.5% del total sin impuestos completo (rol support)', DetallePagoCierre::where('id_cierre', $cierre->id_cierre)
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
        //   sección administrativa: nadie de la sección administrativa vendió, así que
        //   la base compartida es el total completo (170):
        //   ceo 20% = 34.00 | admin 10% = 17.00 | Pinto (programador) 12% = 20.40
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
        $this->assertEquals('12% del total sin impuestos completo (rol programador)', $fila($pinto->id_trab, 'programador')->nota);
        $this->assertEquals(17.00, $fila($root->id_trab, 'admin')->monto);
        $this->assertEquals('10% del total sin impuestos completo (cada admin)', $fila($root->id_trab, 'admin')->nota);
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

    public function test_resta_las_ventas_netas_a_la_base_del_administrativo_que_tambien_modera(): void
    {
        $rolAdmin = Rol::create(['rol' => 'admin']);
        $rolModelo = Rol::create(['rol' => 'modelo']);
        $rolModerador = Rol::create(['rol' => 'moderador']);

        $this->actingAs($root = Trabajador::create([
            'nombre' => 'Root', 'apellido' => 'Admin', 'email' => 'root@nomina.test',
            'id_rol' => $rolAdmin->id_rol, 'password' => Hash::make('secreto123'),
        ]));

        $lucia = Trabajador::create(['nombre' => 'Lucia', 'apellido' => 'Rios', 'id_rol' => $rolModelo->id_rol]);
        // Ana tiene rol administrativo (admin) y además modera uno de los reportes.
        $ana = Trabajador::create(['nombre' => 'Ana', 'apellido' => 'Perez', 'id_rol' => $rolAdmin->id_rol]);
        $otroModerador = Trabajador::create(['nombre' => 'Otro', 'apellido' => 'Moderador', 'id_rol' => $rolModerador->id_rol]);

        $metodo = MetodoPago::create(['propietario' => 'Equipo', 'metodo_pago' => 'PayPal']);

        // Reporte 1: Ana (admin) también modera. Ventas netas 200 - 15% = 170.
        $this->post('/reportes', [
            'id_modelo' => $lucia->id_trab, 'plataforma' => 'OnlyFans', 'user_cliente' => 'cli1',
            'id_mp' => $metodo->id_mp, 'precio' => 200, 'servicio' => 'Video',
            'fecha_reporte' => '2026-09-10', 'id_moderador' => $ana->id_trab,
        ])->assertSessionHasNoErrors();

        // Reporte 2: moderado por otra persona; suma al total pero no a las ventas de Ana.
        $this->post('/reportes', [
            'id_modelo' => $lucia->id_trab, 'plataforma' => 'OnlyFans', 'user_cliente' => 'cli2',
            'id_mp' => $metodo->id_mp, 'precio' => 200, 'servicio' => 'Chat',
            'fecha_reporte' => '2026-09-11', 'id_moderador' => $otroModerador->id_trab,
        ])->assertSessionHasNoErrors();

        $this->post('/cierres', ['fecha_inicio' => '2026-09-07', 'fecha_fin' => '2026-09-13'])
            ->assertSessionHasNoErrors();

        $cierre = CierreSemanal::firstOrFail();

        $fila = fn (int $idTrab, string $concepto) => DetallePagoCierre::where('id_cierre', $cierre->id_cierre)
            ->where('id_trab', $idTrab)->where('concepto', $concepto)->first();

        // Total sin impuestos: 170 + 170 = 340.
        // Ana (admin) vendió neto 170 como moderadora, así que la base compartida es
        // 340 - 170 = 170 y la usan TODOS los administrativos: 1.5% = 2.55.
        $this->assertEquals(2.55, $fila($ana->id_trab, 'admin')->monto);
        $this->assertEquals('1.5% del total sin impuestos completo (cada admin)', $fila($ana->id_trab, 'admin')->nota);

        // El otro admin (root) usa la misma base compartida (170) -> 2.55.
        $this->assertEquals(2.55, $fila($root->id_trab, 'admin')->monto);
        $this->assertEquals('1.5% del total sin impuestos completo (cada admin)', $fila($root->id_trab, 'admin')->nota);

        // Ana además cobra como moderadora de Lucia: 20% de 170 = 34.00.
        $this->assertEquals(34.00, $fila($ana->id_trab, 'moderador')->monto);
    }

    public function test_maria_pinto_calcula_su_parte_administrativa_del_total_completo(): void
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
        $pinto = Trabajador::create(['nombre' => 'Maria', 'apellido' => 'Pinto', 'id_rol' => $rolAdmin->id_rol]);
        $ana = Trabajador::create(['nombre' => 'Ana', 'apellido' => 'Perez', 'id_rol' => $rolModerador->id_rol]);

        $metodo = MetodoPago::create(['propietario' => 'Equipo', 'metodo_pago' => 'PayPal']);

        // Brea (ceo) vende 200 -> neto 170; Ana moderadora del reporte.
        $this->post('/reportes', [
            'id_modelo' => $brea->id_trab, 'plataforma' => 'OnlyFans', 'user_cliente' => 'cli1',
            'id_mp' => $metodo->id_mp, 'precio' => 200, 'servicio' => 'Video',
            'fecha_reporte' => '2026-09-10', 'id_moderador' => $ana->id_trab,
        ])->assertSessionHasNoErrors();

        $this->post('/cierres', ['fecha_inicio' => '2026-09-07', 'fecha_fin' => '2026-09-13'])
            ->assertSessionHasNoErrors();

        $cierre = CierreSemanal::firstOrFail();

        $fila = fn (int $idTrab, string $concepto) => DetallePagoCierre::where('id_cierre', $cierre->id_cierre)
            ->where('id_trab', $idTrab)->where('concepto', $concepto)->first();

        // Total sin impuestos 170; Brea vendió neto 170 -> base compartida 0.
        $this->assertEquals(0.00, $fila($brea->id_trab, 'admin')->monto);
        $this->assertEquals(0.00, $fila($root->id_trab, 'admin')->monto);

        // Excepción: Pinto calcula sobre el total completo (170): 1.5% = 2.55.
        $this->assertEquals(2.55, $fila($pinto->id_trab, 'admin')->monto);
        $this->assertEquals('1.5% del total sin impuestos completo (excepción) (cada admin)', $fila($pinto->id_trab, 'admin')->nota);
    }

    public function test_resta_las_ventas_de_la_ficha_modelo_al_administrativo_con_el_mismo_nombre(): void
    {
        $rolAdmin = Rol::create(['rol' => 'admin']);
        $rolCeo = Rol::create(['rol' => 'ceo']);
        $rolModelo = Rol::create(['rol' => 'modelo']);
        $rolModerador = Rol::create(['rol' => 'moderador']);

        $this->actingAs($root = Trabajador::create([
            'nombre' => 'Root', 'apellido' => 'Admin', 'email' => 'root@nomina.test',
            'id_rol' => $rolAdmin->id_rol, 'password' => Hash::make('secreto123'),
        ]));

        // La misma persona con DOS fichas: una ceo (20%) y otra modelo (75%),
        // que es como se manejan dos porcentajes distintos.
        $breaCeo = Trabajador::create(['nombre' => 'Maria', 'apellido' => 'Brea', 'id_rol' => $rolCeo->id_rol, 'porcentaje' => 20]);
        $breaModelo = Trabajador::create(['nombre' => 'Maria', 'apellido' => 'Brea', 'id_rol' => $rolModelo->id_rol, 'porcentaje' => 75]);
        $ana = Trabajador::create(['nombre' => 'Ana', 'apellido' => 'Perez', 'id_rol' => $rolModerador->id_rol]);

        $metodo = MetodoPago::create(['propietario' => 'Equipo', 'metodo_pago' => 'PayPal']);

        // Brea vende como MODELO (ficha 147) 200 -> neto 170; Ana modera.
        $this->post('/reportes', [
            'id_modelo' => $breaModelo->id_trab, 'plataforma' => 'OnlyFans', 'user_cliente' => 'cli1',
            'id_mp' => $metodo->id_mp, 'precio' => 200, 'servicio' => 'Video',
            'fecha_reporte' => '2026-09-10', 'id_moderador' => $ana->id_trab,
        ])->assertSessionHasNoErrors();

        // Otra modelo vende 100 -> neto 85.
        $lucia = Trabajador::create(['nombre' => 'Lucia', 'apellido' => 'Rios', 'id_rol' => $rolModelo->id_rol]);
        $this->post('/reportes', [
            'id_modelo' => $lucia->id_trab, 'plataforma' => 'OnlyFans', 'user_cliente' => 'cli2',
            'id_mp' => $metodo->id_mp, 'precio' => 100, 'servicio' => 'Chat',
            'fecha_reporte' => '2026-09-11', 'id_moderador' => $ana->id_trab,
        ])->assertSessionHasNoErrors();

        $this->post('/cierres', ['fecha_inicio' => '2026-09-07', 'fecha_fin' => '2026-09-13'])
            ->assertSessionHasNoErrors();

        $cierre = CierreSemanal::firstOrFail();

        $fila = fn (int $idTrab, string $concepto) => DetallePagoCierre::where('id_cierre', $cierre->id_cierre)
            ->where('id_trab', $idTrab)->where('concepto', $concepto)->first();

        // Total sin impuestos 170 + 85 = 255. Las ventas netas de la ficha MODELO de
        // Brea (170) se restan aunque su ficha administrativa sea otra (la ceo):
        // base compartida = 255 - 170 = 85.
        $this->assertEquals(17.00, $fila($breaCeo->id_trab, 'admin')->monto);
        $this->assertEquals('20% del total sin impuestos completo (rol ceo)', $fila($breaCeo->id_trab, 'admin')->nota);
        $this->assertEquals(1.28, $fila($root->id_trab, 'admin')->monto);

        // La ficha modelo cobra como modelo, sin mezclarse con su parte administrativa.
        $this->assertEquals(127.50, $fila($breaModelo->id_trab, 'modelo')->monto);
        // Lucia cobra el 50% por defecto del rol modelo sobre su neto (85).
        $this->assertEquals(42.50, $fila($lucia->id_trab, 'modelo')->monto);
    }
}
