<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Un reporte puede tener hasta 2 comprobantes. Como la columna
     * `comprobantes.id_reporte` es UNIQUE (y no se puede eliminar la restricción
     * porque la BD no permite DROP), se añade un segundo juego de columnas `*2`
     * a la misma fila en vez de crear una segunda fila. La ruta del segundo
     * archivo de disco vive en `reporte_pagos.comprobante_2` (respaldo).
     *
     * La migración es **idempotente** (comprueba `Schema::hasColumn` antes de
     * añadir) para que no falle si las columnas ya se crearon a mano en la BD de
     * producción.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('comprobantes', 'mime2')) {
            Schema::table('comprobantes', function (Blueprint $table) {
                $table->string('mime2', 50)->nullable();
            });
        }

        if (! Schema::hasColumn('comprobantes', 'tamano2')) {
            Schema::table('comprobantes', function (Blueprint $table) {
                $table->unsignedInteger('tamano2')->nullable();
            });
        }

        if (! Schema::hasColumn('comprobantes', 'imagen2')) {
            Schema::table('comprobantes', function (Blueprint $table) {
                $table->text('imagen2')->nullable();
            });
        }

        if (! Schema::hasColumn('reporte_pagos', 'comprobante_2')) {
            Schema::table('reporte_pagos', function (Blueprint $table) {
                $table->string('comprobante_2')->nullable()->after('comprobante');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('reporte_pagos', 'comprobante_2')) {
            Schema::table('reporte_pagos', function (Blueprint $table) {
                $table->dropColumn('comprobante_2');
            });
        }

        foreach (['mime2', 'tamano2', 'imagen2'] as $columna) {
            if (Schema::hasColumn('comprobantes', $columna)) {
                Schema::table('comprobantes', function (Blueprint $table) use ($columna) {
                    $table->dropColumn($columna);
                });
            }
        }
    }
};
