<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MetodoPago extends Model
{
    protected $table = 'metodos_pago';

    protected $primaryKey = 'id_mp';

    public $timestamps = false;

    protected $fillable = [
        'propietario',
        'metodo_pago',
        'porcentaje_cuenta',
    ];

    protected function casts(): array
    {
        return [
            'porcentaje_cuenta' => 'decimal:2',
        ];
    }

    public function reportes(): HasMany
    {
        return $this->hasMany(ReportePago::class, 'id_mp');
    }
}
