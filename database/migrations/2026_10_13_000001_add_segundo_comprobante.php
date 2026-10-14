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
     */
    public function up(): void
    {
        Schema::table('comprobantes', function (Blueprint $table) {
            $table->string('mime2', 50)->nullable();
            $table->unsignedInteger('tamano2')->nullable();
            $table->text('imagen2')->nullable();
        });

        Schema::table('reporte_pagos', function (Blueprint $table) {
            $table->string('comprobante_2')->nullable()->after('comprobante');
        });
    }

    public function down(): void
    {
        Schema::table('reporte_pagos', function (Blueprint $table) {
            $table->dropColumn('comprobante_2');
        });

        Schema::table('comprobantes', function (Blueprint $table) {
            $table->dropColumn(['mime2', 'tamano2', 'imagen2']);
        });
    }
};
