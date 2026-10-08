<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Copia persistente de los comprobantes en la base de datos: Railway usa un
     * storage efímero (`storage/app/public/comprobantes`) que se pierde en cada
     * deploy, así que el binario comprimido se guarda aquí en `imagen` (base64).
     * `reporte_pagos.comprobante` sigue guardando la ruta del disco como red de
     * respaldo; cuando existe fila en esta tabla, la app sirve esta copia.
     */
    public function up(): void
    {
        Schema::create('comprobantes', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('id_reporte')->unique();
            $table->string('mime', 50)->default('image/jpeg');
            $table->unsignedInteger('tamano')->default(0);
            $table->text('imagen');
            $table->timestamps();

            $table->foreign('id_reporte')->references('id_reporte')->on('reporte_pagos')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comprobantes');
    }
};
