<?php

namespace Tests\Feature;

use App\Models\AbonoAdelanto;
use App\Models\Adelanto;
use App\Models\CierreSemanal;
use App\Models\PagoEmpleado;
use App\Models\Rol;
use App\Models\Trabajador;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PagoAdelantoTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_saldo_de_adelantos_se_descuenta_del_pago_y_genera_el_abono(): void
    {
        $this->actingAs($this->admin());
        $trabajador = $this->trabajador();
        $cierre = $this->cierre();

        Adelanto::create([
            'id_trab' => $trabajador->id_trab, 'tipo' => 'adelanto',
            'monto' => 300, 'fecha' => '2026-09-20', 'nota' => 'Adelanto quincena',
        ]);

        $this->get('/pagos/create')
            ->assertOk()
            ->assertSee('Saldo pendiente de adelantos');

        $this->post('/pagos', [
            'id_trab' => $trabajador->id_trab,
            'id_cierre' => $cierre->id_cierre,
            'monto_bruto' => 400,
            'monto_neto' => 400,
            'deuda' => 300,
            'monto_final' => 100,
            'aplicar_adelantos' => 1,
        ])->assertRedirect('/pagos');

        $pago = PagoEmpleado::firstOrFail();
        $this->assertSame(300.0, (float) $pago->deuda);
        $this->assertSame(100.0, (float) $pago->monto_final);

        // El abono queda registrado y el adelanto queda saldado.
        $this->assertDatabaseHas('abonos_adelanto', [
            'id_adelanto' => Adelanto::firstOrFail()->id_adelanto,
            'id_pago' => $pago->id_pago,
            'monto' => 300,
        ]);
        $this->assertSame(0.0, Adelanto::firstOrFail()->fresh()->saldo);
    }

    public function test_el_abono_se_parte_entre_varios_adelentos_del_mismo_trabajador(): void
    {
        $this->actingAs($this->admin());
        $trabajador = $this->trabajador();
        $cierre = $this->cierre();

        Adelanto::create([
            'id_trab' => $trabajador->id_trab, 'tipo' => 'adelanto', 'monto' => 100, 'fecha' => '2026-09-01',
        ]);
        Adelanto::create([
            'id_trab' => $trabajador->id_trab, 'tipo' => 'prestamo', 'monto' => 250, 'fecha' => '2026-09-15',
        ]);

        $this->post('/pagos', [
            'id_trab' => $trabajador->id_trab,
            'id_cierre' => $cierre->id_cierre,
            'monto_bruto' => 300,
            'monto_neto' => 300,
            'deuda' => 180,
            'monto_final' => 120,
            'aplicar_adelantos' => 1,
        ])->assertRedirect('/pagos');

        $abonos = AbonoAdelanto::orderBy('id_abono')->get();
        $this->assertCount(2, $abonos);
        $this->assertSame(100.0, (float) $abonos[0]->monto);  // primero el más antiguo
        $this->assertSame(80.0, (float) $abonos[1]->monto);
        $this->assertSame(170.0, Adelanto::pendientePorTrabajador($trabajador->id_trab));
    }

    public function test_si_se_desmarca_no_se_generan_abonos(): void
    {
        $this->actingAs($this->admin());
        $trabajador = $this->trabajador();
        $cierre = $this->cierre();

        Adelanto::create([
            'id_trab' => $trabajador->id_trab, 'tipo' => 'adelanto', 'monto' => 300, 'fecha' => '2026-09-20',
        ]);

        $this->post('/pagos', [
            'id_trab' => $trabajador->id_trab,
            'id_cierre' => $cierre->id_cierre,
            'monto_bruto' => 400,
            'monto_neto' => 400,
            'deuda' => 50,
            'monto_final' => 350,
        ])->assertRedirect('/pagos');

        $this->assertDatabaseCount('abonos_adelanto', 0);
        $this->assertSame(300.0, Adelanto::pendientePorTrabajador($trabajador->id_trab));
        $this->assertSame(50.0, (float) PagoEmpleado::firstOrFail()->deuda);
    }

    public function test_editar_el_pago_rehace_los_abonos_sin_duplicar_la_deuda(): void
    {
        $this->actingAs($this->admin());
        $trabajador = $this->trabajador();
        $cierre = $this->cierre();

        Adelanto::create([
            'id_trab' => $trabajador->id_trab, 'tipo' => 'adelanto', 'monto' => 300, 'fecha' => '2026-09-20',
        ]);

        $this->post('/pagos', [
            'id_trab' => $trabajador->id_trab,
            'id_cierre' => $cierre->id_cierre,
            'monto_bruto' => 400,
            'monto_neto' => 400,
            'deuda' => 300,
            'monto_final' => 100,
            'aplicar_adelantos' => 1,
        ]);

        $pago = PagoEmpleado::firstOrFail();
        $this->assertSame(0.0, Adelanto::pendientePorTrabajador($trabajador->id_trab));

        // Se edita con la misma deuda: no debe duplicar el abono.
        $this->put("/pagos/{$pago->id_pago}", [
            'id_trab' => $trabajador->id_trab,
            'id_cierre' => $cierre->id_cierre,
            'monto_bruto' => 500,
            'monto_neto' => 500,
            'deuda' => 300,
            'monto_final' => 200,
            'aplicar_adelantos' => 1,
        ])->assertRedirect('/pagos');

        $this->assertDatabaseCount('abonos_adelanto', 1);
        $this->assertSame(0.0, Adelanto::pendientePorTrabajador($trabajador->id_trab));
        $this->assertSame(200.0, (float) $pago->fresh()->monto_final);
    }

    public function test_editar_el_pago_desmarcando_deja_el_saldo_de_adelantos_intacto(): void
    {
        $this->actingAs($this->admin());
        $trabajador = $this->trabajador();
        $cierre = $this->cierre();

        Adelanto::create([
            'id_trab' => $trabajador->id_trab, 'tipo' => 'adelanto', 'monto' => 300, 'fecha' => '2026-09-20',
        ]);

        $this->post('/pagos', [
            'id_trab' => $trabajador->id_trab,
            'id_cierre' => $cierre->id_cierre,
            'monto_bruto' => 400, 'monto_neto' => 400, 'deuda' => 300, 'monto_final' => 100,
            'aplicar_adelantos' => 1,
        ]);

        $pago = PagoEmpleado::firstOrFail();

        $this->put("/pagos/{$pago->id_pago}", [
            'id_trab' => $trabajador->id_trab,
            'id_cierre' => $cierre->id_cierre,
            'monto_bruto' => 400, 'monto_neto' => 400, 'deuda' => 0, 'monto_final' => 400,
        ])->assertRedirect('/pagos');

        $this->assertDatabaseCount('abonos_adelanto', 0);
        $this->assertSame(300.0, Adelanto::pendientePorTrabajador($trabajador->id_trab));
    }

    public function test_la_deuda_se_guarda_en_la_columna_correcta(): void
    {
        $this->actingAs($this->admin());
        $trabajador = $this->trabajador();
        $cierre = $this->cierre();

        $this->post('/pagos', [
            'id_trab' => $trabajador->id_trab,
            'id_cierre' => $cierre->id_cierre,
            'monto_bruto' => 100, 'monto_neto' => 100, 'deuda' => 25, 'monto_final' => 75,
        ])->assertRedirect('/pagos');

        $this->assertDatabaseHas('pago_empleados', ['id_pago' => 1, 'deuda' => 25]);
    }

    private function cierre(): CierreSemanal
    {
        return CierreSemanal::create([
            'fecha_inicio' => '2026-09-07', 'fecha_fin' => '2026-09-13',
        ]);
    }

    private function admin(): Trabajador
    {
        return $this->trabajador('Admin', 'Uno', 'admin');
    }

    private function trabajador(string $nombre = 'Lucia', string $apellido = 'Rios', string $rolNombre = 'modelo'): Trabajador
    {
        $rol = Rol::firstOrCreate(['rol' => $rolNombre]);

        return Trabajador::create([
            'nombre' => $nombre, 'apellido' => $apellido,
            'email' => strtolower($nombre.$apellido).'.'.uniqid().'@nomina.test',
            'id_rol' => $rol->id_rol,
        ]);
    }
}
