<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('metodos_pago', function (Blueprint $table) {
            $table->increments('id_mp');
            $table->string('metodo_pago');
            $table->decimal('impuesto', 12, 2)->nullable();
            $table->decimal('porcentaje_cuenta', 5, 2)->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('metodos_pago');
    }
};
