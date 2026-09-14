<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PagoEmpleado extends Model
{
    public $timestamps = false;

    protected $table = 'pago_empleados';

    protected $primaryKey = 'id_pago';

    protected $fillable = ['id_trab', 'id_cierre', 'monto'];

    public function trabajador(): BelongsTo
    {
        return $this->belongsTo(Trabajador::class, 'id_trab');
    }

    public function cierreSemanal(): BelongsTo
    {
        return $this->belongsTo(CierreSemanal::class, 'id_cierre');
    }
}
