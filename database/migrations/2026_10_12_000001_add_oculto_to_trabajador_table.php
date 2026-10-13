<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Los trabajadores que dejan la empresa no se pueden borrar si tienen
        // reportes o pagos asociados (FK ON DELETE RESTRICT). Esta columna los
        // aparta de la lista principal sin perder su historial.
        if (! Schema::hasColumn('trabajador', 'oculto')) {
            Schema::table('trabajador', function (Blueprint $table): void {
                $table->boolean('oculto')->default(false);
            });
        }
    }

    public function down(): void
    {
        Schema::table('trabajador', function (Blueprint $table): void {
            $table->dropColumn('oculto');
        });
    }
};
