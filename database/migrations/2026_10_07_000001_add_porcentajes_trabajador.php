<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trabajador', function (Blueprint $table) {
            $table->decimal('porcentaje_modelo', 5, 2)->nullable()->after('id_rol');
            $table->decimal('porcentaje_moderador', 5, 2)->nullable()->after('porcentaje_modelo');
            $table->decimal('porcentaje_administrativo', 5, 2)->nullable()->after('porcentaje_moderador');
            $table->decimal('porcentaje_pinto', 5, 2)->nullable()->after('porcentaje_administrativo');
        });
    }

    public function down(): void
    {
        Schema::table('trabajador', function (Blueprint $table) {
            $table->dropColumn([
                'porcentaje_modelo',
                'porcentaje_moderador',
                'porcentaje_administrativo',
                'porcentaje_pinto',
            ]);
        });
    }
};
