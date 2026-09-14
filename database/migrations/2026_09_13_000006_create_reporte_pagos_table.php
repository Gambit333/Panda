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
            $table->unsignedInteger('id_modelo');
            $table->string('plataforma');
            $table->string('user_cliente');
            $table->unsignedInteger('id_mp');
            $table->decimal('precio', 12, 2);
            $table->text('servicio');
            $table->string('duracion')->nullable();
            $table->date('fecha_reporte')->nullable();
            $table->unsignedInteger('id_moderador');
            $table->unsignedInteger('id_cierre')->nullable();
            $table->text('descripcion')->nullable();

            $table->foreign('id_modelo')->references('id_trab')->on('trabajador');
            $table->foreign('id_moderador')->references('id_trab')->on('trabajador');
            $table->foreign('id_mp')->references('id_mp')->on('metodos_pago');
            $table->foreign('id_cierre')->references('id_cierre')->on('cierre_semanal');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reporte_pagos');
    }
};
