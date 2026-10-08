<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Quita a los trabajadores con rol `ceo` de la asignación modelo<->moderador.
     * La ceo ya no se registra como modelo (si necesita cálculos aparte se le
     * crea una ficha con rol `modelo`), así que no debe aparecer en la selección
     * de modelos de ningún moderador. Solo borra filas de la tabla pivote:
     * no toca reportes ni pagos.
     */
    public function up(): void
    {
        DB::table('modelo_moderador')
            ->whereIn('id_modelo', function ($query) {
                $query->select('t.id_trab')
                    ->from('trabajador as t')
                    ->join('roles as r', 'r.id_rol', '=', 't.id_rol')
                    ->whereRaw('lower(r.rol) = ?', ['ceo']);
            })
            ->delete();
    }

    public function down(): void
    {
        // Irreversible: las asignaciones eliminadas no se pueden restaurar.
    }
};
