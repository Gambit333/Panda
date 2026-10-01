<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reporte_pagos', function (Blueprint $table) {
            $table->string('comprobante')->nullable()->after('descripcion');
        });
    }

    public function down(): void
    {
        Schema::table('reporte_pagos', function (Blueprint $table) {
            $table->dropColumn('comprobante');
        });
    }
};
