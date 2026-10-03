<?php

namespace Tests\Feature;

use App\Models\CierreSemanal;
use App\Models\MetodoPago;
use App\Models\ReportePago;
use App\Models\Rol;
use App\Models\Trabajador;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardRankingTest extends TestCase
{
    use RefreshDatabase;

    private function crearTrabajador(string $nombre, string $rol = 'modelo'): Trabajador
    {
        $idRol = Rol::firstOrCreate(['rol' => $rol])->id_rol;

        return Trabajador::create([
            'nombre' => $nombre,
            'apellido' => 'Prueba',
            'email' => strtolower($nombre).'@test.com',
            'id_rol' => $idRol,
        ]);
    }

    private function crearReporte(Trabajador $modelo, Trabajador $moderador, MetodoPago $metodo, string $fecha, float $precio, ?int $cierre = null): void
    {
        ReportePago::create([
            'plataforma' => 'OnlyFans',
            'user_cliente' => 'cliente@test.com',
            'precio' => $precio,
            'servicio' => 'Chat',
            'fecha_reporte' => $fecha,
            'id_modelo' => $modelo->id_trab,
            'id_moderador' => $moderador->id_trab,
            'id_mp' => $metodo->id_mp,
            'id_cierre' => $cierre,
        ]);
    }

    public function test_las_tarjetas_muestran_el_ranking_solo_de_reportes_sin_cerrar(): void
    {
        $admin = $this->crearTrabajador('Root', 'admin');
        $metodo = MetodoPago::create(['propietario' => 'Equipo', 'metodo_pago' => 'PayPal']);

        $ana = $this->crearTrabajador('Ana');
        $beto = $this->crearTrabajador('Beto');

        $modUno = $this->crearTrabajador('ModUno', 'moderador');
        $modDos = $this->crearTrabajador('ModDos', 'moderador');

        // Ana 150 pendientes (2 reportes) | Beto 200 pendiente (1 reporte, pero ya cerrado -> no cuenta)
        $this->crearReporte($ana, $modUno, $metodo, '2026-10-01', 100.0);
        $this->crearReporte($ana, $modUno, $metodo, '2026-10-02', 50.0);

        $cierre = CierreSemanal::create(['fecha_inicio' => '2026-09-01', 'fecha_fin' => '2026-09-07']);
        $this->crearReporte($beto, $modDos, $metodo, '2026-09-03', 200.0, $cierre->id_cierre);
        $this->crearReporte($beto, $modUno, $metodo, '2026-10-03', 80.0);

        $this->actingAs($admin);

        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('Modelos que más venden');
        $response->assertSee('Moderadores que más venden');

        // Ana primero con $150.00; Beto solo cuenta su reporte pendiente de $80.
        $this->assertStringContainsString('Ana Prueba', $response->getContent());
        $this->assertStringContainsString('$150.00', $response->getContent());
        $this->assertStringContainsString('$80.00', $response->getContent());

        $posAna = strpos($response->getContent(), 'Ana Prueba');
        $posBeto = strpos($response->getContent(), 'Beto Prueba');
        $this->assertNotFalse($posAna);
        $this->assertNotFalse($posBeto);
        $this->assertLessThan($posBeto, $posAna, 'Ana (más ventas sin cerrar) debe ir antes que Beto.');
    }

    public function test_las_tarjetas_muestran_el_vacio_sin_reportes_pendientes(): void
    {
        $admin = $this->crearTrabajador('Root', 'admin');

        $this->actingAs($admin);

        $response = $this->get('/');

        $response->assertOk();
        $this->assertSame(
            2,
            substr_count($response->getContent(), 'No hay reportes pendientes de cerrar.')
        );
    }

    public function test_el_ranking_muestra_maximo_cinco(): void
    {
        $admin = $this->crearTrabajador('Root', 'admin');
        $metodo = MetodoPago::create(['propietario' => 'Equipo', 'metodo_pago' => 'PayPal']);
        $moderador = $this->crearTrabajador('Mod', 'moderador');

        foreach (range(1, 6) as $i) {
            $modelo = $this->crearTrabajador('Modelo'.$i);
            $this->crearReporte($modelo, $moderador, $metodo, '2026-10-0'.$i, (float) ($i * 10));
        }

        $this->actingAs($admin);

        $contenido = $this->get('/')->assertOk()->getContent();

        // Solo se miran las dos tarjetas de ranking, no la tabla de últimos reportes.
        $inicio = strpos($contenido, 'Modelos que más venden');
        $fin = strpos($contenido, 'Moderadores que más venden');
        $tarjetaModelos = substr($contenido, $inicio, $fin - $inicio);

        $this->assertStringContainsString('Modelo6 Prueba', $tarjetaModelos);
        $this->assertStringNotContainsString('Modelo1 Prueba', $tarjetaModelos);
        $this->assertSame(5, substr_count($tarjetaModelos, '<li>'), 'El ranking debe traer los 5 primeros.');
    }
}
