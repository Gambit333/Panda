<?php

namespace Tests\Feature;

use App\Models\Comprobante;
use App\Models\MetodoPago;
use App\Models\ReportePago;
use App\Models\Rol;
use App\Models\Trabajador;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SegundoComprobanteTest extends TestCase
{
    use RefreshDatabase;

    private function contexto(): array
    {
        $rolAdmin = Rol::create(['rol' => 'admin']);
        $admin = Trabajador::create(['nombre' => 'Root', 'apellido' => 'Admin', 'id_rol' => $rolAdmin->id_rol]);
        $modelo = Trabajador::create(['nombre' => 'Luis', 'apellido' => 'Gomez', 'id_rol' => Rol::create(['rol' => 'modelo'])->id_rol]);
        $moderador = Trabajador::create(['nombre' => 'Ana', 'apellido' => 'Perez', 'id_rol' => Rol::create(['rol' => 'moderador'])->id_rol]);
        $metodo = MetodoPago::create(['propietario' => 'Equipo', 'metodo_pago' => 'PayPal']);

        $campos = [
            'id_modelo' => $modelo->id_trab,
            'plataforma' => 'OnlyFans',
            'user_cliente' => 'cliente1',
            'id_mp' => $metodo->id_mp,
            'precio' => 100,
            'servicio' => 'Video privado',
            'fecha_reporte' => '2026-09-10',
            'id_moderador' => $moderador->id_trab,
        ];

        return compact('admin', 'modelo', 'moderador', 'metodo', 'campos');
    }

    public function test_guarda_dos_comprobantes_en_el_mismo_reporte(): void
    {
        Storage::fake('public');
        $ctx = $this->contexto();
        $this->actingAs($ctx['admin']);

        $this->post('/reportes', $ctx['campos'] + [
            'comprobante' => $this->pngDePrueba('uno.png'),
            'comprobante_2' => $this->pngDePrueba('dos.png'),
        ])->assertSessionHasNoErrors();

        $reporte = ReportePago::firstOrFail();
        $this->assertNotNull($reporte->comprobante);
        $this->assertNotNull($reporte->comprobante_2);
        $this->assertNotSame($reporte->comprobante, $reporte->comprobante_2);
        Storage::disk('public')->assertExists($reporte->comprobante);
        Storage::disk('public')->assertExists($reporte->comprobante_2);

        // Las dos quedan guardadas en la BD (una fila con las columnas *2).
        $this->assertSame(1, Comprobante::count());
        $copia = Comprobante::firstOrFail();
        $this->assertSame(strlen($copia->binario), $copia->tamano);
        $this->assertSame(strlen($copia->binario2), $copia->tamano2);
        $this->assertNotSame('', (string) $copia->imagen2);

        // Ambas se sirven por sus rutas y se ven en el listado.
        $this->get('/comprobantes/'.$reporte->id_reporte)->assertOk();
        $this->get('/comprobantes/'.$reporte->id_reporte.'/2')->assertOk();
        $this->get(route('reportes.index'))->assertSee('/comprobantes/'.$reporte->id_reporte.'/2');
    }

    public function test_puede_guardar_solo_el_segundo_comprobante(): void
    {
        Storage::fake('public');
        $ctx = $this->contexto();
        $this->actingAs($ctx['admin']);

        $this->post('/reportes', $ctx['campos'] + [
            'comprobante_2' => $this->pngDePrueba('dos.png'),
        ])->assertSessionHasNoErrors();

        $reporte = ReportePago::firstOrFail();
        $ruta = $reporte->id_reporte;

        $this->assertNull($reporte->comprobante);
        $this->assertNotNull($reporte->comprobante_2);
        $this->assertSame([route('comprobantes.show', [$ruta, 2])], $reporte->comprobante_urls);

        $this->get('/comprobantes/'.$ruta.'/2')->assertOk();
        // El hueco 1 no tiene imagen.
        $this->get('/comprobantes/'.$ruta)->assertNotFound();
    }

    public function test_reemplaza_y_elimina_el_segundo_comprobante(): void
    {
        Storage::fake('public');
        $ctx = $this->contexto();
        $this->actingAs($ctx['admin']);

        $this->post('/reportes', $ctx['campos'] + [
            'comprobante' => $this->pngDePrueba('uno.png'),
            'comprobante_2' => $this->pngDePrueba('dos.png'),
        ])->assertSessionHasNoErrors();

        $reporte = ReportePago::firstOrFail();
        $ruta1 = $reporte->comprobante;
        $ruta2Vieja = $reporte->comprobante_2;

        // Se reemplaza solo el segundo comprobante; el primero no se toca.
        $this->put("/reportes/{$reporte->id_reporte}", $ctx['campos'] + [
            'comprobante_2' => $this->pngDePrueba('dos-nuevo.png'),
        ])->assertSessionHasNoErrors();

        $reporte->refresh();
        $this->assertSame($ruta1, $reporte->comprobante);
        $this->assertNotSame($ruta2Vieja, $reporte->comprobante_2);
        Storage::disk('public')->assertMissing($ruta2Vieja);
        Storage::disk('public')->assertExists($reporte->comprobante_2);

        $ruta2Nueva = $reporte->comprobante_2;

        // Se elimina el segundo y el primero permanece.
        $this->put("/reportes/{$reporte->id_reporte}", $ctx['campos'] + [
            'eliminar_comprobante_2' => '1',
        ])->assertSessionHasNoErrors();

        $reporte->refresh();
        $this->assertSame($ruta1, $reporte->comprobante);
        $this->assertNull($reporte->comprobante_2);
        $this->assertCount(1, $reporte->comprobante_urls);
        Storage::disk('public')->assertMissing($ruta2Nueva);
        Storage::disk('public')->assertExists($ruta1);

        $copia = Comprobante::firstOrFail();
        $this->assertNull($copia->imagen2);
        $this->assertNotSame('', (string) $copia->imagen);
    }

    private function pngDePrueba(string $nombre): UploadedFile
    {
        $tmp = tempnam(sys_get_temp_dir(), 'comprobante').'.png';
        file_put_contents($tmp, base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII='
        ));

        return new UploadedFile($tmp, $nombre, 'image/png', null, true);
    }
}
