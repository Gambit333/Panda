<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('adelantos', function (Blueprint $table) {
            $table->increments('id_adelanto');
            $table->unsignedInteger('id_trab');
            $table->string('tipo', 20)->default('adelanto');
            $table->decimal('monto', 12, 2);
            $table->date('fecha');
            $table->string('nota')->nullable();
            $table->timestamps();

            $table->foreign('id_trab')->references('id_trab')->on('trabajador')->onDelete('cascade');
            $table->index('id_trab');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('adelantos');
    }
};
