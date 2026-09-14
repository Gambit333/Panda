<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CierreSemanal extends Model
{
    public $timestamps = false;

    protected $table = 'cierre_semanal';

    protected $primaryKey = 'id_cierre';

    protected $fillable = ['fecha_inicio', 'fecha_fin', 'total'];

    protected function casts(): array
    {
        return [
            'fecha_inicio' => 'date',
            'fecha_fin' => 'date',
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
}
