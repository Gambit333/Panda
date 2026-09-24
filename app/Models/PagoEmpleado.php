<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PagoEmpleado extends Model
{
    protected $table = 'pago_empleados';
    protected $primaryKey = 'id_pago';

    public $timestamps = false;

    protected $fillable = [
        'id_trab',
        'id_cierre',
        'monto_bruto',
        'monto_neto',
        'deuda_descontada',
        'monto_final',
        'nota',
    ];



    protected function casts(): array
    {
        return [
            'monto_bruto' => 'decimal:2',
            'monto_neto' => 'decimal:2',
            'deuda_descontada' => 'decimal:2',
            'monto_final' => 'decimal:2',
        ];
    }

    public function trabajador(): BelongsTo
    {
        return $this->belongsTo(Trabajador::class, 'id_trab');
    }

    public function cierreSemanal(): BelongsTo
    {
        return $this->belongsTo(CierreSemanal::class, 'id_cierre');
    }
}