<?php

namespace Tests\Feature;

use App\Models\Adelanto;
use App\Models\CierreSemanal;
use App\Models\MetodoPago;
use App\Models\PagoEmpleado;
use App\Models\ReportePago;
use App\Models\Rol;
use App\Models\Trabajador;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaginacionTest extends TestCase
{
    use RefreshDatabase;

    public function test_todas_las_tablas_paginadas_usan_la_paginacion_compacta(): void
    {
        $this->crearDatos();

        $this->actingAs($this->admin());

        $paginas = [
            '/trabajadores?page=2',
            '/reportes?page=2',
            '/pagos?page=2',
            '/adelantos?page=2',
            '/cierres?page=2',
        ];

        foreach ($paginas as $url) {
            $response = $this->get($url);

            $response->assertOk();

            // Marcado propio (nav + clases CSS del proyecto), no el de Tailwind.
            $response->assertSee('<nav class="page-links"', false);
            $response->assertSee('aria-label="Página anterior"', false);
            $response->assertSee('aria-label="Página siguiente"', false);
            $response->assertSee('page-btn', false);
            $response->assertSee('is-active', false);

            // Las flechas son SVG pequeños (14px vía CSS), no las de Tailwind.
            $response->assertDontSee('Previous');
            $response->assertDontSee('size-10', false);
            $response->assertDontSee('w-10 h-10', false);
        }
    }

    private function crearDatos(): void
    {
        $rolAdmin = Rol::create(['rol' => 'admin']);
        $rolModelo = Rol::create(['rol' => 'modelo']);
        $rolModerador = Rol::create(['rol' => 'moderador']);
        $metodo = MetodoPago::create(['propietario' => 'Equipo', 'metodo_pago' => 'PayPal']);

        $admin = Trabajador::create([
            'nombre' => 'Root', 'apellido' => 'Admin', 'email' => 'root@nomina.test', 'id_rol' => $rolAdmin->id_rol,
        ]);

        $modelos = [];
        for ($i = 1; $i <= 32; $i++) {
            $modelos[] = Trabajador::create([
                'nombre' => 'M'.$i, 'apellido' => 'Modelo', 'email' => "m{$i}@nomina.test",
                'id_rol' => $i % 2 === 0 ? $rolModelo->id_rol : $rolModerador->id_rol,
            ]);
        }

        for ($i = 1; $i <= 32; $i++) {
            $modelo = $modelos[$i % 32];

            ReportePago::create([
                'id_modelo' => $modelo->id_trab, 'id_moderador' => $modelo->id_trab,
                'plataforma' => 'OnlyFans', 'user_cliente' => 'cliente'.$i,
                'id_mp' => $metodo->id_mp, 'precio' => 100 + $i, 'servicio' => 'Video privado',
                'fecha_reporte' => '2026-09-'.$i,
            ]);

            $cierre = CierreSemanal::create([
                'fecha_inicio' => '2026-09-01', 'fecha_fin' => '2026-09-0'.(($i % 9) + 1),
            ]);

            PagoEmpleado::create([
                'id_trab' => $modelo->id_trab, 'id_cierre' => $cierre->id_cierre,
                'monto_bruto' => 100, 'monto_neto' => 100, 'deuda' => 0, 'monto_final' => 100,
            ]);

            Adelanto::create([
                'id_trab' => $modelo->id_trab, 'tipo' => 'adelanto', 'monto' => 50, 'fecha' => '2026-09-01',
            ]);

            Rol::create(['rol' => 'rol-'.$i]);
        }

        $this->admin = $admin;
    }

    private ?Trabajador $admin = null;

    private function admin(): Trabajador
    {
        return $this->admin;
    }
}
