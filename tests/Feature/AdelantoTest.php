<?php

namespace Tests\Feature;

use App\Models\AbonoAdelanto;
use App\Models\Adelanto;
use App\Models\Rol;
use App\Models\Trabajador;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdelantoTest extends TestCase
{
    use RefreshDatabase;

    public function test_se_registra_un_adelanto_y_queda_con_saldo_pendiente(): void
    {
        $admin = $this->admin();
        $trabajador = $this->trabajador('Lucia', 'Rios');
        $this->actingAs($admin);

        $this->get('/adelantos/create')->assertOk();

        $this->post('/adelantos', [
            'id_trab' => $trabajador->id_trab,
            'tipo' => 'adelanto',
            'monto' => 300,
            'fecha' => '2026-10-01',
            'nota' => ' Adelanto de quincena',
        ])->assertRedirect('/adelantos');

        $this->assertDatabaseHas('adelantos', [
            'id_trab' => $trabajador->id_trab,
            'tipo' => 'adelanto',
            'monto' => 300,
        ]);

        $adelanto = Adelanto::firstOrFail();
        $this->assertSame(0.0, $adelanto->pagado);
        $this->assertSame(300.0, $adelanto->saldo);
        $this->assertFalse($adelanto->saldado);

        $this->get('/adelantos')
            ->assertOk()
            ->assertSee('Lucia Rios')
            ->assertSee('300.00');
    }

    public function test_los_abonos_descontan_el_saldo_hasta_quedarsaldado(): void
    {
        $this->actingAs($this->admin());
        $trabajador = $this->trabajador('Lucia', 'Rios');

        $adelanto = Adelanto::create([
            'id_trab' => $trabajador->id_trab,
            'tipo' => 'prestamo',
            'monto' => 500,
            'fecha' => '2026-10-01',
        ]);

        $this->post("/adelantos/{$adelanto->id_adelanto}/abonos", [
            'monto' => 200, 'fecha' => '2026-10-05', 'nota' => 'Abono 1',
        ])->assertRedirect();

        $this->assertDatabaseHas('abonos_adelanto', [
            'id_adelanto' => $adelanto->id_adelanto, 'monto' => 200,
        ]);
        $this->assertSame(200.0, $adelanto->fresh()->pagado);
        $this->assertSame(300.0, $adelanto->fresh()->saldo);

        // No se puede abonar más de lo que falta.
        $this->post("/adelantos/{$adelanto->id_adelanto}/abonos", [
            'monto' => 400, 'fecha' => '2026-10-06',
        ])->assertSessionHasErrors('monto');
        $this->assertSame(200.0, $adelanto->fresh()->pagado);

        $this->post("/adelantos/{$adelanto->id_adelanto}/abonos", [
            'monto' => 300, 'fecha' => '2026-10-07',
        ])->assertRedirect();

        $this->assertSame(0.0, $adelanto->fresh()->saldo);
        $this->assertTrue($adelanto->fresh()->saldado);

        // Un adelanto saldado no admite más abonos.
        $this->post("/adelantos/{$adelanto->id_adelanto}/abonos", [
            'monto' => 10, 'fecha' => '2026-10-08',
        ])->assertSessionHasErrors('monto');
    }

    public function test_se_puede_eliminar_un_abono_y_el_saldo_vuelve_a_subir(): void
    {
        $this->actingAs($this->admin());
        $trabajador = $this->trabajador('Lucia', 'Rios');

        $adelanto = Adelanto::create([
            'id_trab' => $trabajador->id_trab, 'tipo' => 'adelanto', 'monto' => 100, 'fecha' => '2026-10-01',
        ]);
        $abono = AbonoAdelanto::create([
            'id_adelanto' => $adelanto->id_adelanto, 'monto' => 40, 'fecha' => '2026-10-02',
        ]);

        $this->delete("/adelantos/abonos/{$abono->id_abono}")->assertRedirect();

        $this->assertDatabaseMissing('abonos_adelanto', ['id_abono' => $abono->id_abono]);
        $this->assertSame(100.0, $adelanto->fresh()->saldo);
    }

    public function test_al_editar_el_monto_nunca_queda_menor_a_lo_ya_abonado(): void
    {
        $this->actingAs($this->admin());
        $trabajador = $this->trabajador('Lucia', 'Rios');

        $adelanto = Adelanto::create([
            'id_trab' => $trabajador->id_trab, 'tipo' => 'adelanto', 'monto' => 500, 'fecha' => '2026-10-01',
        ]);
        AbonoAdelanto::create([
            'id_adelanto' => $adelanto->id_adelanto, 'monto' => 300, 'fecha' => '2026-10-02',
        ]);

        $this->put("/adelantos/{$adelanto->id_adelanto}", [
            'id_trab' => $trabajador->id_trab, 'tipo' => 'adelanto', 'monto' => 100, 'fecha' => '2026-10-03',
        ])->assertRedirect('/adelantos');

        $this->assertSame(300.0, (float) $adelanto->fresh()->monto);
        $this->assertSame(0.0, $adelanto->fresh()->saldo);
    }

    public function test_el_indice_muestra_cuanto_debe_cada_trabajador(): void
    {
        $this->actingAs($this->admin());
        $lucia = $this->trabajador('Lucia', 'Rios');
        $ana = $this->trabajador('Ana', 'Perez');

        Adelanto::create(['id_trab' => $lucia->id_trab, 'tipo' => 'adelanto', 'monto' => 300, 'fecha' => '2026-10-01']);
        Adelanto::create(['id_trab' => $lucia->id_trab, 'tipo' => 'prestamo', 'monto' => 100, 'fecha' => '2026-10-02']);
        Adelanto::create(['id_trab' => $ana->id_trab, 'tipo' => 'prestamo', 'monto' => 50, 'fecha' => '2026-10-03']);

        $this->get('/adelantos')
            ->assertOk()
            ->assertSee('Lucia Rios')
            ->assertSee('Ana Perez')
            ->assertSee('400.00')   // total de Lucia
            ->assertSee('300.00')   // adeudado de Ana
            ->assertSee('450.00');  // saldo pendiente total
    }

    public function test_el_formulario_exige_trabajador_tipo_monto_y_fecha(): void
    {
        $this->actingAs($this->admin());

        $this->post('/adelantos', [
            'id_trab' => '', 'tipo' => 'otro', 'monto' => 0, 'fecha' => '',
        ])->assertSessionHasErrors(['id_trab', 'tipo', 'monto', 'fecha']);

        $this->assertDatabaseCount('adelantos', 0);
    }

    private function admin(): Trabajador
    {
        return $this->trabajador('Admin', 'Uno', 'admin');
    }

    private function trabajador(string $nombre, string $apellido, string $rolNombre = 'modelo'): Trabajador
    {
        $rol = Rol::firstOrCreate(['rol' => $rolNombre]);

        return Trabajador::create([
            'nombre' => $nombre, 'apellido' => $apellido,
            'email' => strtolower($nombre.$apellido).'@nomina.test',
            'id_rol' => $rol->id_rol,
        ]);
    }
}
