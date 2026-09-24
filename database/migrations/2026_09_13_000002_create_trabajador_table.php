<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trabajador', function (Blueprint $table) {
            $table->increments('id_trab');
            $table->string('nombre');
            $table->string('apellido');
            $table->string('telefono')->nullable();
            $table->string('email')->unique(); // Añadido UNIQUE
            $table->string('password');
            $table->text('direccion')->nullable();
            $table->unsignedInteger('id_rol');
            $table->rememberToken(); // Requerido para Auth
            $table->timestamps();

            $table->foreign('id_rol')->references('id_rol')->on('roles')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trabajador');
    }
};