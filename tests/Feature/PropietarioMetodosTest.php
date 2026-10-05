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

class PropietarioMetodosTest extends TestCase
{
    use RefreshDatabase;

    private static int $secuencia = 0;

    private function trabajador(string $rol): Trabajador
    {
        $rolCreado = Rol::firstOrCreate(['rol' => $rol]);

        return Trabajador::create([
            'nombre' => 'Persona',
            'apellido' => $rol,
            'email' => $rol.'-'.(++self::$secuencia).'@test.com',
            'id_rol' => $rolCreado->id_rol,
        ]);
    }

    private function dueno(): Trabajador
    {
        return $this->trabajador('propietario');
    }

    private function metodo(string $nombre, ?int $duenoId = null): MetodoPago
    {
        return MetodoPago::create([
            'metodo_pago' => $nombre,
            'propietario' => $nombre.' titular',
            'porcentaje_cuenta' => 0,
            'id_propietario' => $duenoId,
        ]);
    }

    private array $involucrados = [];

    private function reporte(MetodoPago $metodo, float $precio, ?CierreSemanal $cierre = null, string $fecha = '2026-10-01'): ReportePago
    {
        if ($this->involucrados === []) {
            $this->involucrados = [$this->trabajador('modelo')->id_trab, $this->trabajador('moderador')->id_trab];
        }

        return ReportePago::create([
            'plataforma' => 'OnlyFans',
            'user_cliente' => 'Cliente '.$precio,
            'precio' => $precio,
            'servicio' => 'Chat',
            'fecha_reporte' => $fecha,
            'id_modelo' => $this->involucrados[0],
            'id_moderador' => $this->involucrados[1],
            'id_mp' => $metodo->id_mp,
            'id_cierre' => $cierre?->id_cierre,
        ]);
    }

    public function test_el_rol_propietario_solo_entra_al_inicio(): void
    {
        $dueno = $this->dueno();

        $this->actingAs($dueno);

        $this->get('/')->assertOk()->assertSee('Ingresos de mis');

        foreach (['/reportes', '/cierres', '/pagos', '/adelantos', '/trabajadores', '/metodos', '/roles'] as $ruta) {
            $this->get($ruta)->assertRedirect('/');
        }
    }

    public function test_ve_los_ingresos_de_sus_metodos_y_no_de_los_demas(): void
    {
        $dueno = $this->dueno();
        $otro = $this->dueno();

        $metodoMio = $this->metodo('Zelle', $dueno->id_trab);
        $metodoAjeno = $this->metodo('Binance', $otro->id_trab);
        $sinDueno = $this->metodo('PayPal');

        $this->reporte($metodoMio, 100);
        $this->reporte($metodoMio, 50);
        $this->reporte($metodoAjeno, 999);
        $this->reporte($sinDueno, 777);

        $this->actingAs($dueno);

        $this->get('/')
            ->assertOk()
            ->assertSee('Zelle')
            ->assertDontSee('Binance')
            ->assertDontSee('PayPal')
            ->assertDontSee('$777')
            ->assertSee('150.00');
    }

    public function test_pregunta_los_reportes_sin_cerrar_y_si_no_hay_usa_el_ultimo_cierre(): void
    {
        $dueno = $this->dueno();
        $metodo = $this->metodo('Zelle', $dueno->id_trab);

        $viejo = CierreSemanal::create(['fecha_inicio' => '2026-08-01', 'fecha_fin' => '2026-08-07', 'total' => 10]);
        $ultimo = CierreSemanal::create(['fecha_inicio' => '2026-09-01', 'fecha_fin' => '2026-09-07', 'total' => 10]);

        $this->reporte($metodo, 10, $viejo, '2026-08-02');
        $this->reporte($metodo, 20, $ultimo, '2026-09-02');
        $sinCerrar = $this->reporte($metodo, 30, null, '2026-10-01');

        // Con reportes sin cerrar gana ese periodo.
        $this->actingAs($dueno)->get('/')
            ->assertOk()
            ->assertSee('30.00')
            ->assertSee('Reportes sin cerrar')
            ->assertDontSee('20.00');

        $sinCerrar->delete();

        // Sin pendientes, muestra los del último cierre (no los del cierre viejo).
        $this->actingAs($dueno)->get('/')
            ->assertOk()
            ->assertSee('20.00')
            ->assertSee('Último cierre #'.$ultimo->id_cierre)
            ->assertDontSee('10.00');
    }

    public function test_sin_cierres_muestra_todos_sus_reportes(): void
    {
        $dueno = $this->dueno();
        $metodo = $this->metodo('Zelle', $dueno->id_trab);
        $this->reporte($metodo, 40, null);

        $this->actingAs($dueno)->get('/')
            ->assertOk()
            ->assertSee('40.00');
    }

    public function test_muestra_sus_ganancias_liquidadas_y_su_comision(): void
    {
        $dueno = $this->dueno();
        $metodo = $this->metodo('Zelle', $dueno->id_trab);
        $this->reporte($metodo, 100);

        PagoEmpleado::create([
            'id_trab' => $dueno->id_trab,
            'id_cierre' => CierreSemanal::create(['fecha_inicio' => '2026-10-01', 'fecha_fin' => '2026-10-07', 'total' => 0])->id_cierre,
            'monto_bruto' => 40,
            'monto_neto' => 40,
            'deuda' => 0,
            'monto_final' => 40,
        ]);
        PagoEmpleado::create([
            'id_trab' => $this->trabajador('modelo')->id_trab,
            'id_cierre' => CierreSemanal::create(['fecha_inicio' => '2026-10-08', 'fecha_fin' => '2026-10-14', 'total' => 0])->id_cierre,
            'monto_bruto' => 500,
            'monto_neto' => 500,
            'deuda' => 0,
            'monto_final' => 500,
        ]);

        $this->actingAs($dueno)->get('/')
            ->assertOk()
            ->assertSee('100.00')
            ->assertDontSee('500.00')
            ->assertDontSee('Monto liquidado');
    }

    public function test_sin_metodos_asignados_muestra_el_aviso(): void
    {
        $dueno = $this->dueno();

        $this->actingAs($dueno)->get('/')
            ->assertOk()
            ->assertSee('No tienes métodos de pago asignados');
    }

    public function test_se_puede_asignar_el_dueno_desde_el_formulario_de_metodos(): void
    {
        $dueno = $this->dueno();
        $admin = $this->trabajador('admin');

        $this->actingAs($admin);

        $this->get('/metodos/create')
            ->assertOk()
            ->assertSee('Dueño de la cuenta')
            ->assertSee($dueno->id_trab);

        $this->post('/metodos', [
            'metodo_pago' => 'Zelle',
            'propietario' => '',
            'id_propietario' => $dueno->id_trab,
            'porcentaje_cuenta' => 0,
        ])->assertRedirect('/metodos');

        $metodo = MetodoPago::where('metodo_pago', 'Zelle')->firstOrFail();

        $this->assertSame($dueno->id_trab, (int) $metodo->id_propietario);
        // El titular se rellena solo con el nombre del dueño.
        $this->assertSame('Persona propietario', $metodo->propietario);

        $this->assertTrue($metodo->esDe($dueno->id_trab));
        $this->assertTrue($metodo->dueno->is($dueno));

        $this->get('/metodos')->assertOk()->assertSee('Persona propietario');
    }
}
