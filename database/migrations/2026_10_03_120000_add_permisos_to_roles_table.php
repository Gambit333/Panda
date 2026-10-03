<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('roles', 'permisos')) {
            Schema::table('roles', function (Blueprint $table): void {
                // null = nunca se editó (el rol usa el acceso por defecto).
                $table->json('permisos')->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('roles', 'permisos')) {
            Schema::table('roles', function (Blueprint $table): void {
                $table->dropColumn('permisos');
            });
        }
    }
};
