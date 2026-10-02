<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('abonos_adelanto', function (Blueprint $table) {
            $table->unsignedInteger('id_pago')->nullable()->after('id_adelanto');

            $table->foreign('id_pago')->references('id_pago')->on('pago_empleados')->onDelete('cascade');
            $table->index('id_pago');
        });
    }

    public function down(): void
    {
        Schema::table('abonos_adelanto', function (Blueprint $table) {
            $table->dropForeign(['id_pago']);
            $table->dropColumn('id_pago');
        });
    }
};
