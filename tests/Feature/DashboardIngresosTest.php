<?php

namespace Tests\Feature;

use App\Models\CierreSemanal;
use App\Models\MetodoPago;
use App\Models\ReportePago;
use App\Models\Rol;
use App\Models\Trabajador;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardIngresosTest extends TestCase
{
    use RefreshDatabase;

    public function test_los_ingresos_del_inicio_solo_cuentan_reportes_sin_cierre(): void
    {
        $rol = Rol::create(['rol' => 'admin']);
        $admin = Trabajador::create([
            'nombre' => 'Root',
            'apellido' => 'Admin',
            'email' => 'root@test.com',
            'id_rol' => $rol->id_rol,
        ]);

        $modelo = Trabajador::create([
            'nombre' => 'Ana',
            'apellido' => 'Ruiz',
            'email' => 'ana@test.com',
            'id_rol' => $rol->id_rol,
        ]);

        $metodo = MetodoPago::create(['propietario' => 'Equipo', 'metodo_pago' => 'PayPal']);
        $cierre = CierreSemanal::create(['fecha_inicio' => '2026-09-01', 'fecha_fin' => '2026-09-07']);

        // 100 pendientes (sin cierre) + 200 ya cerrados = 300 en total histórico.
        foreach ([['2026-10-01', null, 50.0], ['2026-10-02', null, 50.0], ['2026-09-03', $cierre->id_cierre, 200.0]] as [$fecha, $idCierre, $precio]) {
            ReportePago::create([
                'plataforma' => 'OnlyFans',
                'user_cliente' => 'cliente@test.com',
                'precio' => $precio,
                'servicio' => 'Chat',
                'fecha_reporte' => $fecha,
                'id_modelo' => $modelo->id_trab,
                'id_moderador' => $admin->id_trab,
                'id_mp' => $metodo->id_mp,
                'id_cierre' => $idCierre,
            ]);
        }

        $this->actingAs($admin);

        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('Ingresos sin cerrar');
        $response->assertSee('$100.00');
        $response->assertSee('2 reportes pendientes de cerrar');
        $response->assertDontSee('Ingresos totales');
        $response->assertDontSee('$300.00');
    }

    public function test_el_cargo_destacado_muestra_la_cifra_ampliada(): void
    {
        $rol = Rol::create(['rol' => 'admin']);
        $admin = Trabajador::create([
            'nombre' => 'Root',
            'apellido' => 'Admin',
            'email' => 'root@test.com',
            'id_rol' => $rol->id_rol,
        ]);

        $this->actingAs($admin);

        $this->get('/')->assertOk()->assertSee('stat stat-destacado', false);
    }

    public function test_el_dashboard_de_empleado_no_cambia(): void
    {
        $rol = Rol::create(['rol' => 'moderador']);
        $moderador = Trabajador::create([
            'nombre' => 'Mod',
            'apellido' => 'Erador',
            'email' => 'mod@test.com',
            'id_rol' => $rol->id_rol,
        ]);

        $this->actingAs($moderador);

        $this->get('/')->assertOk()->assertSee('Mis ingresos reportados')->assertDontSee('Ingresos sin cerrar');
    }
}
