<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CierreSemanal extends Model
{
    protected $table = 'cierre_semanal';

    protected $primaryKey = 'id_cierre';

    public $timestamps = false;

    protected $fillable = [
        'fecha_inicio',
        'fecha_fin',
        'total_bruto',
        'total_neto',
    ];

    protected function casts(): array
    {
        return [
            'fecha_inicio' => 'date',
            'fecha_fin' => 'date',
            'total_bruto' => 'decimal:2',
            'total_neto' => 'decimal:2',
        ];
    }

    public function pagosEmpleados(): HasMany
    {
        return $this->hasMany(PagoEmpleado::class, 'id_cierre');
    }

    public function reportes(): HasMany
    {
        return $this->hasMany(ReportePago::class, 'id_cierre');
    }

    public function detallesPago(): HasMany
    {
        return $this->hasMany(DetallePagoCierre::class, 'id_cierre');
    }
}
