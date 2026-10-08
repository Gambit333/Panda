<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('detalle_pago_cierre', function (Blueprint $table) {
            $table->increments('id_detalle');
            $table->unsignedInteger('id_cierre')->index();
            $table->unsignedInteger('id_trab')->nullable()->index();
            $table->string('concepto')->index(); // modelo | moderador | admin | programador
            $table->decimal('monto', 12, 2)->default(0);
            $table->string('nota')->nullable();
            $table->timestamps();

            $table->foreign('id_cierre')->references('id_cierre')->on('cierre_semanal')->onDelete('cascade');
            $table->foreign('id_trab')->references('id_trab')->on('trabajador')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('detalle_pago_cierre');
    }
};
