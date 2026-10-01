<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reporte_pagos', function (Blueprint $table) {
            $table->increments('id_reporte');
            $table->unsignedInteger('id_modelo')->index();
            $table->string('plataforma');
            $table->string('user_cliente');
            $table->unsignedInteger('id_mp')->index();
            $table->decimal('precio', 12, 2);
            $table->text('servicio');
            $table->decimal('addon_extra', 12, 2)->nullable()->default(0.00); // Añadido
            $table->string('duracion')->nullable();
            $table->date('fecha_reporte')->nullable()->index();
            $table->unsignedInteger('id_moderador')->index();
            $table->unsignedInteger('id_cierre')->nullable()->index(); // Nullable si no ha cerrado la semana
            $table->text('descripcion')->nullable();
            $table->timestamps();

            $table->foreign('id_modelo')->references('id_trab')->on('trabajador');
            $table->foreign('id_moderador')->references('id_trab')->on('trabajador');
            $table->foreign('id_mp')->references('id_mp')->on('metodos_pago');
            $table->foreign('id_cierre')->references('id_cierre')->on('cierre_semanal')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reporte_pagos');
    }
};
