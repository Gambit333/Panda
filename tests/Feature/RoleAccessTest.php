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

class RoleAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_moderador_can_access_reportes_but_not_admin_modules(): void
    {
        $moderador = $this->trabajadorDeRol('moderador');
        $this->actingAs($moderador);

        $this->get('/reportes')->assertOk();
        $this->get('/reportes/create')->assertOk();

        $this->get('/cierres')->assertRedirect('/');
        $this->get('/pagos')->assertRedirect('/');
        $this->get('/trabajadores')->assertRedirect('/');
        $this->get('/metodos')->assertRedirect('/');
        $this->get('/roles')->assertRedirect('/');
    }

    public function test_modelo_can_only_access_dashboard(): void
    {
        $modelo = $this->trabajadorDeRol('modelo');
        $this->actingAs($modelo);

        $this->get('/')->assertOk();
        $this->get('/reportes')->assertRedirect('/');
        $this->get('/cierres')->assertRedirect('/');
        $this->get('/pagos')->assertRedirect('/');
    }

    public function test_admin_keeps_full_access(): void
    {
        $admin = $this->trabajadorDeRol('admin');
        $this->actingAs($admin);

        $this->get('/reportes')->assertOk();
        $this->get('/cierres')->assertOk();
        $this->get('/pagos')->assertOk();
        $this->get('/trabajadores')->assertOk();
        $this->get('/metodos')->assertOk();
        $this->get('/roles')->assertOk();
    }

    public function test_moderador_dashboard_shows_only_his_reported_earnings(): void
    {
        $rolModelo = Rol::create(['rol' => 'modelo']);
        $modelo = Trabajador::create(['nombre' => 'Luis', 'apellido' => 'Modelo', 'id_rol' => $rolModelo->id_rol]);
        $moderador = $this->trabajadorDeRol('moderador');
        $metodo = MetodoPago::create(['metodo_pago' => 'PayPal']);

        $this->crearReporte($moderador, $modelo, $metodo, 100);
        $this->crearReporte($moderador, $modelo, $metodo, 50);

        $otro = $this->trabajadorDeRol('moderador', 'Otra', 'Moderadora');
        $this->crearReporte($otro, $modelo, $metodo, 999);

        $this->actingAs($moderador);

        $response = $this->get('/');
        $response->assertOk();
        $response->assertSee('Mis ingresos reportados');
        $response->assertSee('150.00');
        $response->assertDontSee('999.00');
    }

    public function test_modelo_dashboard_shows_only_his_earnings(): void
    {
        $modelo = $this->trabajadorDeRol('modelo', 'Rania', 'Modelo');
        $moderador = $this->trabajadorDeRol('moderador', 'Ana', 'Moderadora');
        $metodo = MetodoPago::create(['metodo_pago' => 'PayPal']);

        $this->crearReporte($moderador, $modelo, $metodo, 200);
        $this->crearReporte($moderador, $modelo, $metodo, 75);

        $otroModelo = $this->trabajadorDeRol('modelo', 'Otra', 'Modelo');
        $this->crearReporte($moderador, $otroModelo, $metodo, 888);

        CierreSemanal::create([
            'fecha_inicio' => '2026-09-07', 'fecha_fin' => '2026-09-13',
        ]);
        PagoEmpleado::create([
            'id_trab' => $modelo->id_trab,
            'id_cierre' => CierreSemanal::firstOrFail()->id_cierre,
            'monto' => 40,
        ]);

        $this->actingAs($modelo);

        $response = $this->get('/');
        $response->assertOk();
        $response->assertSee('Mis ingresos reportados');
        $response->assertSee('275.00');
        $response->assertSee('40.00');
        $response->assertDontSee('888.00');
    }

    private function trabajadorDeRol(string $rol, string $nombre = 'N', string $apellido = 'A'): Trabajador
    {
        $rolModel = Rol::create(['rol' => $rol]);

        return Trabajador::create([
            'nombre' => $nombre, 'apellido' => $apellido,
            'email' => $nombre.$apellido.'@nomina.test'.uniqid(), 'id_rol' => $rolModel->id_rol,
        ]);
    }

    private function crearReporte(Trabajador $moderador, Trabajador $modelo, MetodoPago $metodo, float $precio): void
    {
        ReportePago::create([
            'id_modelo' => $modelo->id_trab,
            'plataforma' => 'OnlyFans',
            'user_cliente' => 'cliente'.uniqid(),
            'id_mp' => $metodo->id_mp,
            'precio' => $precio,
            'servicio' => 'Video privado',
            'id_moderador' => $moderador->id_trab,
        ]);
    }
}