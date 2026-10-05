<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('metodos_pago', 'id_propietario')) {
            Schema::table('metodos_pago', function (Blueprint $table): void {
                // Dueño de la cuenta: trabajador con rol "propietario" que puede ver
                // en el inicio los ingresos de este método de pago.
                $table->unsignedInteger('id_propietario')->nullable();

                $table->foreign('id_propietario')
                    ->references('id_trab')
                    ->on('trabajador')
                    ->onDelete('set null');
            });
        }

        // Rol de los dueños de los métodos de pago: sin permisos (solo el inicio).
        $existe = DB::table('roles')->where('rol', 'propietario')->exists();

        if (! $existe) {
            DB::table('roles')->insert([
                'rol' => 'propietario',
                'permisos' => json_encode([]),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('roles')->where('rol', 'propietario')->delete();

        if (Schema::hasColumn('metodos_pago', 'id_propietario')) {
            Schema::table('metodos_pago', function (Blueprint $table): void {
                $table->dropForeign(['id_propietario']);
                $table->dropColumn('id_propietario');
            });
        }
    }
};
