<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MetodoPago extends Model
{
    protected $table = 'metodos_pago';

    protected $primaryKey = 'id_mp';

    public $timestamps = false;

    protected $fillable = [
        'propietario',
        'id_propietario',
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

    /** Trabajador con rol "propietario" dueño de este método de pago. */
    public function dueno(): BelongsTo
    {
        return $this->belongsTo(Trabajador::class, 'id_propietario');
    }

    public function esDe(int $idTrab): bool
    {
        return (int) $this->id_propietario === $idTrab;
    }
}
