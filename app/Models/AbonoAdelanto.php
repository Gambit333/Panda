<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AbonoAdelanto extends Model
{
    protected $table = 'abonos_adelanto';

    protected $primaryKey = 'id_abono';

    protected $fillable = [
        'id_adelanto',
        'id_pago',
        'monto',
        'fecha',
        'nota',
    ];

    protected function casts(): array
    {
        return [
            'monto' => 'decimal:2',
            'fecha' => 'date',
        ];
    }

    public function adelanto(): BelongsTo
    {
        return $this->belongsTo(Adelanto::class, 'id_adelanto');
    }

    public function pago(): BelongsTo
    {
        return $this->belongsTo(PagoEmpleado::class, 'id_pago');
    }
}
