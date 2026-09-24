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
            $table->decimal('monto_bruto', 12, 2)->default(0.00);       
            $table->decimal('monto_neto', 12, 2)->default(0.00);        
            $table->decimal('deuda_descontada', 12, 2)->default(0.00);  
            $table->decimal('monto_final', 12, 2)->default(0.00);       
            $table->string('nota')->nullable();                          
            $table->timestamps();

            $table->foreign('id_trab')->references('id_trab')->on('trabajador')->onDelete('cascade');
            $table->foreign('id_cierre')->references('id_cierre')->on('cierre_semanal')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pago_empleados');
    }
};