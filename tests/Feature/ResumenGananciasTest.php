<?php

namespace Tests\Feature;

use App\Models\MetodoPago;
use App\Models\ReportePago;
use App\Models\Rol;
use App\Models\Trabajador;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResumenGananciasTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_admin_ve_el_resumen_completo_como_un_cierre_semanal(): void
    {
        $admin = $this->trabajador('Root', 'Admin', 'admin');
        $modelo = $this->trabajador('Ana', 'Rios', 'modelo');
        $moderador = $this->trabajador('Luis', 'Mod', 'moderador');
        $metodo = MetodoPago::create(['propietario' => 'Equipo', 'metodo_pago' => 'PayPal']);

        $this->reporte($modelo, $moderador, $metodo, 1000);

        $this->actingAs($admin);

        $this->get('/')
            ->assertOk()
            ->assertSee('Ganancias hasta el momento (como cierre semanal)')
            ->assertSee('1 reportes sin cerrar')
            ->assertSee('TOTAL FACTURADO')
            ->assertSee('1,000.00')
            ->assertSee('TOTAL SIN IMPUESTOS (15%)')
            ->assertSee('850.00')
            // Modelo 50% de 850, moderador 20% de 850, admin 1.5% de 850.
            ->assertSee('Ana Rios')
            ->assertSee('Luis Mod')
            ->assertSee('425.00')
            ->assertSee('170.00')
            ->assertSee('12.75')
            ->assertSee('TOTAL A PAGAR')
            ->assertSee('607.75');
    }

    public function test_la_modelo_solo_ve_sus_propias_ganancias(): void
    {
        $modelo = $this->trabajador('Ana', 'Rios', 'modelo');
        $otra = $this->trabajador('Bety', 'Lara', 'modelo');
        $moderador = $this->trabajador('Luis', 'Mod', 'moderador');
        $metodo = MetodoPago::create(['propietario' => 'Equipo', 'metodo_pago' => 'PayPal']);

        $this->reporte($modelo, $moderador, $metodo, 100);
        $this->reporte($otra, $moderador, $metodo, 500);

        $this->actingAs($modelo);

        $this->get('/')
            ->assertOk()
            ->assertSee('Ganancias hasta el momento')
            ->assertSee('MIS GANANCIAS (con mi porcentaje)')
            ->assertSee('100.00')
            ->assertSee('85.00')
            ->assertSee('42.50')
            ->assertDontSee('500.00')
            ->assertDontSee('425.00');
    }

    public function test_el_propietario_solo_ve_sus_propias_ganancias(): void
    {
        $dueno = $this->trabajador('Dueno', 'Cuenta', 'propietario');
        $modelo = $this->trabajador('Ana', 'Rios', 'modelo');
        $moderador = $this->trabajador('Luis', 'Mod', 'moderador');
        $metodo = MetodoPago::create([
            'propietario' => 'Equipo',
            'metodo_pago' => 'PayPal',
            'porcentaje_cuenta' => 10,
            'id_propietario' => $dueno->id_trab,
        ]);

        $this->reporte($modelo, $moderador, $metodo, 1000);

        $this->actingAs($dueno);

        $this->get('/')
            ->assertOk()
            ->assertSee('Mis ganancias hasta el momento')
            ->assertSee('INGRESOS DE MIS MÉTODOS')
            ->assertSee('1,000.00')
            ->assertSee('100.00')
            ->assertDontSee('Ganancias hasta el momento (como cierre semanal)');
    }

    private function reporte(Trabajador $modelo, Trabajador $moderador, MetodoPago $metodo, float $precio): ReportePago
    {
        return ReportePago::create([
            'id_modelo' => $modelo->id_trab,
            'id_moderador' => $moderador->id_trab,
            'plataforma' => 'OnlyFans',
            'user_cliente' => 'cliente-'.$precio,
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
