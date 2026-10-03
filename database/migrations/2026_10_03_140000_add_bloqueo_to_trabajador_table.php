<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('trabajador', 'intentos_fallidos')) {
            Schema::table('trabajador', function (Blueprint $table): void {
                $table->unsignedTinyInteger('intentos_fallidos')->default(0);
            });
        }

        if (! Schema::hasColumn('trabajador', 'bloqueado')) {
            Schema::table('trabajador', function (Blueprint $table): void {
                $table->boolean('bloqueado')->default(false);
                // El bloqueo expira solo; un programador también puede limpiarlo antes.
                $table->timestamp('bloqueado_hasta')->nullable();
            });
        }
    }

    public function down(): void
    {
        Schema::table('trabajador', function (Blueprint $table): void {
            $table->dropColumn(['intentos_fallidos', 'bloqueado', 'bloqueado_hasta']);
        });
    }
};
