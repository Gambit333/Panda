<?php

namespace Tests\Feature;

use App\Models\Rol;
use App\Models\Trabajador;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrabajadorOcultoTest extends TestCase
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

    private function trabajador(string $nombre): Trabajador
    {
        return Trabajador::create([
            'nombre' => $nombre,
            'apellido' => 'Prueba',
            'email' => strtolower($nombre).'@test.com',
            'id_rol' => Rol::firstOrCreate(['rol' => 'modelo'])->id_rol,
        ]);
    }

    public function test_la_lista_principal_no_muestra_a_los_ocultos(): void
    {
        $admin = $this->admin();
        $activo = $this->trabajador('Aurora');
        $retirado = $this->trabajador('Bruno');
        $retirado->update(['oculto' => true]);

        $this->actingAs($admin);

        $this->get(route('trabajadores.index'))
            ->assertOk()
            ->assertSee('Aurora Prueba')
            ->assertDontSee('Bruno Prueba');
    }

    public function test_la_pestana_ocultos_muestra_a_los_retirados(): void
    {
        $admin = $this->admin();
        $activo = $this->trabajador('Aurora');
        $retirado = $this->trabajador('Bruno');
        $retirado->update(['oculto' => true]);

        $this->actingAs($admin);

        $this->get(route('trabajadores.index', ['estado' => 'ocultos']))
            ->assertOk()
            ->assertSee('Bruno Prueba')
            ->assertDontSee('Aurora Prueba')
            ->assertSee('Oculto');
    }

    public function test_ocultar_aparta_al_trabajador_sin_borrarlo(): void
    {
        $admin = $this->admin();
        $retirado = $this->trabajador('Bruno');

        $this->actingAs($admin);

        $this->post(route('trabajadores.ocultar', $retirado))
            ->assertRedirect(route('trabajadores.index'))
            ->assertSessionHas('success');

        $this->assertTrue($retirado->fresh()->oculto);
        $this->flushSession();
        $this->get(route('trabajadores.index'))->assertDontSee('Bruno Prueba');
    }

    public function test_mostrar_devuelve_al_trabajador_a_la_lista(): void
    {
        $admin = $this->admin();
        $retirado = $this->trabajador('Bruno');
        $retirado->update(['oculto' => true]);

        $this->actingAs($admin);

        $this->post(route('trabajadores.mostrar', $retirado))
            ->assertRedirect(route('trabajadores.index'))
            ->assertSessionHas('success');

        $this->assertFalse($retirado->fresh()->oculto);
        $this->get(route('trabajadores.index'))->assertSee('Bruno Prueba');
    }

    public function test_no_puede_ocultarse_a_si_mismo(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin);

        $this->post(route('trabajadores.ocultar', $admin))
            ->assertRedirect()
            ->assertSessionHasErrors('trabajador');

        $this->assertFalse($admin->fresh()->oculto);
    }

    public function test_nuevos_trabajadores_no_quedan_ocultos(): void
    {
        $admin = $this->admin();
        $nuevo = $this->trabajador('Aurora');

        $this->assertFalse((bool) $nuevo->fresh()->oculto);

        $this->actingAs($admin);
        $this->get(route('trabajadores.index'))->assertSee('Aurora Prueba');
    }
}
