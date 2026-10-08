<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Porcentaje que cobra el moderador por cada modelo asignado en la tabla
     * pivote `modelo_moderador`. Vacío = se usa el `porcentaje` del moderador
     * o, si tampoco lo tiene, el defecto de moderador (20%).
     */
    public function up(): void
    {
        Schema::table('modelo_moderador', function (Blueprint $table) {
            $table->decimal('porcentaje', 5, 2)->nullable()->after('id_modelo');
        });
    }

    public function down(): void
    {
        Schema::table('modelo_moderador', function (Blueprint $table) {
            $table->dropColumn('porcentaje');
        });
    }
};
