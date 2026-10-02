<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('metodos_pago', 'impuesto')) {
            Schema::table('metodos_pago', function (Blueprint $table): void {
                $table->dropColumn('impuesto');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('metodos_pago', 'impuesto')) {
            Schema::table('metodos_pago', function (Blueprint $table): void {
                $table->decimal('impuesto', 5, 2)->default(0.00);
            });
        }
    }
};
