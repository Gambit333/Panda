<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('modelo_moderador', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_moderador');
            $table->unsignedBigInteger('id_modelo');
            $table->timestamps();

            // Claves foráneas hacia la tabla trabajador
            $table->foreign('id_moderador')->references('id_trab')->on('trabajador')->onDelete('cascade');
            $table->foreign('id_modelo')->references('id_trab')->on('trabajador')->onDelete('cascade');

            $table->unique(['id_moderador', 'id_modelo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('modelo_moderador');
    }
};
