<?php

namespace Tests\Feature;

use App\Models\Rol;
use App\Models\Trabajador;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PwaTest extends TestCase
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

    public function test_el_sitio_declara_el_manifiesto_y_el_service_worker(): void
    {
        $this->actingAs($this->admin());

        $this->get('/')
            ->assertOk()
            ->assertSee('rel="manifest" href="/manifest.webmanifest"', false)
            ->assertSee("navigator.serviceWorker.register('/sw.js')", false)
            ->assertSee('id="installApp"', false);
    }

    public function test_el_nombre_dashboard_fue_cambiado_a_inicio(): void
    {
        $this->actingAs($this->admin());

        $this->get('/')
            ->assertOk()
            ->assertSee('>Inicio</a>', false)
            ->assertDontSee('Dashboard');
    }

    public function test_el_manifiesto_es_valido_y_tiene_los_iconos(): void
    {
        $manifiesto = json_decode((string) file_get_contents(public_path('manifest.webmanifest')), true);

        $this->assertIsArray($manifiesto);
        $this->assertSame('standalone', $manifiesto['display']);
        $this->assertSame('/', $manifiesto['start_url']);
        $this->assertSame('Panda', $manifiesto['name']);
        $this->assertSame('Panda', $manifiesto['short_name']);

        $tamanos = array_column($manifiesto['icons'], 'sizes');
        $this->assertContains('192x192', $tamanos);
        $this->assertContains('512x512', $tamanos);

        foreach ($manifiesto['icons'] as $icono) {
            $this->assertFileExists(public_path(ltrim($icono['src'], '/')));
        }

        $this->assertFileExists(public_path('sw.js'));
        $this->assertFileExists(public_path('offline.html'));
        $this->assertFileExists(public_path('icons/icon.svg'));
    }
}
