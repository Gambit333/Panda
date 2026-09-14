<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cierre_semanal', function (Blueprint $table) {
            $table->increments('id_cierre');
            $table->date('fecha_inicio');
            $table->date('fecha_fin');
            $table->decimal('total', 12, 2)->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cierre_semanal');
    }
};
