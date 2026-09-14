<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MetodoPago extends Model
{
    public $timestamps = false;

    protected $table = 'metodos_pago';

    protected $primaryKey = 'id_mp';

    protected $fillable = ['metodo_pago', 'impuesto', 'porcentaje_cuenta'];

    public function reportes(): HasMany
    {
        return $this->hasMany(ReportePago::class, 'id_mp');
    }
}
