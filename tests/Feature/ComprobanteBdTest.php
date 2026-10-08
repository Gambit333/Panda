<?php

namespace Tests\Feature;

use App\Models\Comprobante;
use App\Models\MetodoPago;
use App\Models\ReportePago;
use App\Models\Rol;
use App\Models\Trabajador;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ComprobanteBdTest extends TestCase
{
    use RefreshDatabase;

    private function armarReporte(): array
    {
        $rolAdmin = Rol::create(['rol' => 'admin']);
        $rolModelo = Rol::create(['rol' => 'modelo']);
        $rolModerador = Rol::create(['rol' => 'moderador']);
        $rolPropietario = Rol::create(['rol' => 'propietario']);

        $admin = Trabajador::create(['nombre' => 'Root', 'apellido' => 'Admin', 'id_rol' => $rolAdmin->id_rol]);
        $modelo = Trabajador::create(['nombre' => 'Lucia', 'apellido' => 'Rios', 'id_rol' => $rolModelo->id_rol]);
        $moderador = Trabajador::create(['nombre' => 'Ana', 'apellido' => 'Perez', 'id_rol' => $rolModerador->id_rol]);
        $dueno = Trabajador::create(['nombre' => 'Daniel', 'apellido' => 'Gomez', 'id_rol' => $rolPropietario->id_rol]);

        $metodo = MetodoPago::create(['propietario' => 'Dueño 1', 'id_propietario' => $dueno->id_trab, 'metodo_pago' => 'PayPal']);

        $reporte = ReportePago::create([
            'plataforma' => 'OnlyFans',
            'user_cliente' => 'cliente-x',
            'id_mp' => $metodo->id_mp,
            'precio' => 100,
            'servicio' => 'Chat',
            'fecha_reporte' => '2026-09-10',
            'id_modelo' => $modelo->id_trab,
            'id_moderador' => $moderador->id_trab,
            'comprobante' => 'comprobantes/viejo.jpg',
        ]);

        Comprobante::create([
            'id_reporte' => $reporte->id_reporte,
            'mime' => 'image/jpeg',
            'tamano' => 4,
            'imagen' => base64_encode('JPEG'),
        ]);

        return compact('admin', 'modelo', 'moderador', 'dueno', 'reporte');
    }

    public function test_el_comprobante_se_sirve_desde_la_base_de_datos(): void
    {
        $ctx = $this->armarReporte();

        $this->actingAs($ctx['admin']);

        $respuesta = $this->get('/comprobantes/'.$ctx['reporte']->id_reporte);

        $respuesta->assertOk();
        $this->assertSame('JPEG', $respuesta->getContent());
        $this->assertEquals('image/jpeg', $respuesta->headers->get('Content-Type'));

        // Cuando existe la copia en BD, las vistas enlazan a la ruta BD (no al disco).
        $this->get(route('reportes.index'))
            ->assertSee('/comprobantes/'.$ctx['reporte']->id_reporte);
    }

    public function test_sin_copia_en_bd_sirve_la_del_disco(): void
    {
        $ctx = $this->armarReporte();
        Comprobante::where('id_reporte', $ctx['reporte']->id_reporte)->delete();

        Storage::fake('public');
        Storage::disk('public')->put($ctx['reporte']->comprobante, 'ARCHIVO-DISCO');

        $this->actingAs($ctx['admin']);

        $respuesta = $this->get('/comprobantes/'.$ctx['reporte']->id_reporte);

        $respuesta->assertOk();
        ob_start();
        $respuesta->sendContent();
        $this->assertSame('ARCHIVO-DISCO', ob_get_clean());
    }

    public function test_acceso_al_comprobante(): void
    {
        $ctx = $this->armarReporte();

        // Sin sesión se redirige al login.
        $this->get('/comprobantes/'.$ctx['reporte']->id_reporte)->assertRedirect(route('login'));

        $rolAdmin = Rol::create(['rol' => 'admin']);
        $admin = Trabajador::create(['nombre' => 'Otro', 'apellido' => 'Admin', 'id_rol' => $rolAdmin->id_rol]);

        $this->actingAs($ctx['modelo'])->get('/comprobantes/'.$ctx['reporte']->id_reporte)->assertOk();
        $this->actingAs($ctx['moderador'])->get('/comprobantes/'.$ctx['reporte']->id_reporte)->assertOk();
        $this->actingAs($ctx['dueno'])->get('/comprobantes/'.$ctx['reporte']->id_reporte)->assertOk();
        // Un admin cualquiera también (acceso total).
        $this->actingAs($admin)->get('/comprobantes/'.$ctx['reporte']->id_reporte)->assertOk();

        // Un moderador ajeno no puede verlo.
        $rolModerador = Rol::create(['rol' => 'moderador']);
        $otro = Trabajador::create(['nombre' => 'Otro', 'apellido' => 'Moderador', 'id_rol' => $rolModerador->id_rol]);
        $this->actingAs($otro)->get('/comprobantes/'.$ctx['reporte']->id_reporte)->assertForbidden();
    }
}
