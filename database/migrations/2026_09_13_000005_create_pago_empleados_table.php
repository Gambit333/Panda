<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pago_empleados', function (Blueprint $table) {
            $table->increments('id_pago');
            $table->unsignedInteger('id_trab');
            $table->unsignedInteger('id_cierre');
            $table->decimal('monto', 12, 2);

            $table->foreign('id_trab')->references('id_trab')->on('trabajador');
            $table->foreign('id_cierre')->references('id_cierre')->on('cierre_semanal');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pago_empleados');
    }
};
