<?php

namespace Tests\Feature;

use App\Models\MetodoPago;
use App\Models\Rol;
use App\Models\Trabajador;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MetodoPagoCrudTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): Trabajador
    {
        $rol = Rol::create(['rol' => 'admin']);

        return Trabajador::create([
            'nombre' => 'Root',
            'apellido' => 'Admin',
            'email' => 'root@test.com',
            'id_rol' => $rol->id_rol,
        ]);
    }

    public function test_la_columna_impuesto_ya_no_existe(): void
    {
        $this->assertFalse(Schema::hasColumn('metodos_pago', 'impuesto'));
    }

    public function test_crea_un_metodo_de_pago_sin_impuesto(): void
    {
        $this->actingAs($this->admin());

        $this->post('/metodos', [
            'metodo_pago' => 'Zelle',
            'propietario' => 'Cuenta principal',
            'porcentaje_cuenta' => '5',
        ])->assertRedirect(route('metodos.index'))->assertSessionHas('success');

        $this->assertDatabaseHas('metodos_pago', [
            'metodo_pago' => 'Zelle',
            'propietario' => 'Cuenta principal',
        ]);
    }

    public function test_ignora_el_campo_impuesto_si_alguien_lo_envia(): void
    {
        $this->actingAs($this->admin());

        $this->post('/metodos', [
            'metodo_pago' => 'Binance',
            'propietario' => 'Binance Pay',
            'impuesto' => '5',
        ])->assertRedirect(route('metodos.index'))->assertSessionHas('success');

        $this->assertDatabaseHas('metodos_pago', ['metodo_pago' => 'Binance']);
    }

    public function test_actualiza_un_metodo_de_pago(): void
    {
        $this->actingAs($this->admin());

        $metodo = MetodoPago::create([
            'metodo_pago' => 'PayPal',
            'propietario' => 'Equipo',
            'porcentaje_cuenta' => 10,
        ]);

        $this->put(route('metodos.update', $metodo), [
            'metodo_pago' => 'PayPal ME',
            'propietario' => 'María',
            'porcentaje_cuenta' => '20',
        ])->assertRedirect(route('metodos.index'));

        $this->assertSame('PayPal ME', $metodo->fresh()->metodo_pago);
        $this->assertSame('20.00', $metodo->fresh()->porcentaje_cuenta);
    }

    public function test_el_formulario_y_el_listado_no_muestran_el_campo_impuesto(): void
    {
        $this->actingAs($this->admin());

        $metodo = MetodoPago::create([
            'metodo_pago' => 'PayPal',
            'propietario' => 'Equipo',
            'porcentaje_cuenta' => 10,
        ]);

        $this->get('/metodos/create')->assertOk()->assertDontSee('name="impuesto"', false);
        $this->get(route('metodos.edit', $metodo))->assertOk()->assertDontSee('name="impuesto"', false);
        $this->get('/metodos')->assertOk()->assertDontSee('Impuesto');
    }
}
