<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('detalle_pago_cierre', function (Blueprint $table) {
            $table->decimal('total_antes_impuestos', 12, 2)->default(0)->after('monto');
            $table->decimal('total_despues_impuestos', 12, 2)->default(0)->after('total_antes_impuestos');
        });
    }

    public function down(): void
    {
        Schema::table('detalle_pago_cierre', function (Blueprint $table) {
            $table->dropColumn(['total_antes_impuestos', 'total_despues_impuestos']);
        });
    }
};
