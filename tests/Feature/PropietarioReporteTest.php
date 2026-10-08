<?php

namespace Tests\Feature;

use App\Models\MetodoPago;
use App\Models\ReportePago;
use App\Models\Rol;
use App\Models\Trabajador;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PropietarioReporteTest extends TestCase
{
    use RefreshDatabase;

    private function rol(string $nombre)
    {
        return Rol::create(['rol' => $nombre]);
    }

    public function test_propietario_ve_solo_los_reportes_de_sus_metodos(): void
    {
        $rolAdmin = $this->rol('admin');
        $rolModelo = $this->rol('modelo');
        $rolModerador = $this->rol('moderador');
        $rolPropietario = $this->rol('propietario');

        $admin = Trabajador::create(['nombre' => 'Root', 'apellido' => 'Admin', 'id_rol' => $rolAdmin->id_rol]);
        $modelo = Trabajador::create(['nombre' => 'Lucia', 'apellido' => 'Rios', 'id_rol' => $rolModelo->id_rol]);
        $moderador = Trabajador::create(['nombre' => 'Ana', 'apellido' => 'Perez', 'id_rol' => $rolModerador->id_rol]);

        $dueno = Trabajador::create(['nombre' => 'Daniel', 'apellido' => 'Gomez', 'id_rol' => $rolPropietario->id_rol]);
        $otroDueno = Trabajador::create(['nombre' => 'Otro', 'apellido' => 'Dueno', 'id_rol' => $rolPropietario->id_rol]);

        $paypal = MetodoPago::create(['propietario' => 'Dueño 1', 'id_propietario' => $dueno->id_trab, 'metodo_pago' => 'PayPal']);
        $binance = MetodoPago::create(['propietario' => 'Dueño 1', 'id_propietario' => $dueno->id_trab, 'metodo_pago' => 'Binance']);
        $otroMetodo = MetodoPago::create(['propietario' => 'Otro', 'id_propietario' => $otroDueno->id_trab, 'metodo_pago' => 'Zelle']);

        $crear = fn (MetodoPago $metodo, float $precio, string $fecha) => ReportePago::create([
            'plataforma' => 'OnlyFans',
            'user_cliente' => 'cliente-x',
            'id_mp' => $metodo->id_mp,
            'precio' => $precio,
            'servicio' => 'Chat',
            'fecha_reporte' => $fecha,
            'id_modelo' => $modelo->id_trab,
            'id_moderador' => $moderador->id_trab,
            'comprobante' => $metodo->metodo_pago === 'PayPal' ? 'comprobantes/paypal.jpg' : null,
        ]);

        $crear($paypal, 100, '2026-09-10');
        $crear($paypal, 50, '2026-09-12');
        $crear($binance, 250, '2026-09-11');
        $crear($otroMetodo, 999, '2026-09-13');

        $this->actingAs($dueno);

        $this->get(route('propietario.reportes'))
            ->assertOk()
            ->assertSee('PayPal')
            ->assertSee('Binance')
            ->assertSee('$100.00')
            ->assertSee('$50.00')
            ->assertSee('$250.00')
            ->assertSee('$150.00')
            ->assertSee('10/09/2026')
            ->assertSee('/storage/comprobantes/paypal.jpg')
            // No ve los reportes del método de otro propietario
            ->assertDontSee('Zelle')
            ->assertDontSee('$999.00')
            // Solo las columnas mínimas: ni cliente, ni modelo, ni moderador
            ->assertDontSee('cliente-x')
            ->assertDontSee('Root Admin')
            ->assertDontSee('Ana Perez');
    }

    public function test_la_seccion_es_solo_para_propietarios(): void
    {
        $roles = [
            'admin' => $this->rol('admin'),
            'programador' => $this->rol('programador'),
            'moderador' => $this->rol('moderador'),
            'propietario' => $this->rol('propietario'),
        ];

        foreach (['admin', 'programador', 'moderador'] as $rol) {
            $usuario = Trabajador::create(['nombre' => ucfirst($rol), 'apellido' => 'Test', 'id_rol' => $roles[$rol]->id_rol]);

            $this->actingAs($usuario)->get(route('propietario.reportes'))->assertForbidden();
        }

        $dueno = Trabajador::create(['nombre' => 'Daniel', 'apellido' => 'Gomez', 'id_rol' => $roles['propietario']->id_rol]);

        $this->actingAs($dueno)->get(route('propietario.reportes'))->assertOk();
    }
}
