<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DetallePagoCierre extends Model
{
    protected $table = 'detalle_pago_cierre';

    protected $primaryKey = 'id_detalle';

    protected $fillable = [
        'id_cierre',
        'id_trab',
        'concepto',
        'monto',
        'total_antes_impuestos',
        'total_despues_impuestos',
        'nota',
    ];

    protected function casts(): array
    {
        return [
            'monto' => 'decimal:2',
            'total_antes_impuestos' => 'decimal:2',
            'total_despues_impuestos' => 'decimal:2',
        ];
    }

    public function cierreSemanal(): BelongsTo
    {
        return $this->belongsTo(CierreSemanal::class, 'id_cierre');
    }

    public function trabajador(): BelongsTo
    {
        return $this->belongsTo(Trabajador::class, 'id_trab');
    }
}
