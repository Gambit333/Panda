<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('abonos_adelanto', function (Blueprint $table) {
            $table->increments('id_abono');
            $table->unsignedInteger('id_adelanto');
            $table->decimal('monto', 12, 2);
            $table->date('fecha');
            $table->string('nota')->nullable();
            $table->timestamps();

            $table->foreign('id_adelanto')->references('id_adelanto')->on('adelantos')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('abonos_adelanto');
    }
};
