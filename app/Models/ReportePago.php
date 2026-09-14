<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReportePago extends Model
{
    public $timestamps = false;

    protected $table = 'reporte_pagos';

    protected $primaryKey = 'id_reporte';

    protected $fillable = [
        'id_modelo',
        'plataforma',
        'user_cliente',
        'id_mp',
        'precio',
        'servicio',
        'duracion',
        'fecha_reporte',
        'id_moderador',
        'id_cierre',
        'descripcion',
    ];

    protected function casts(): array
    {
        return [
            'fecha_reporte' => 'date',
        ];
    }

    public function modelo(): BelongsTo
    {
        return $this->belongsTo(Trabajador::class, 'id_modelo');
    }

    public function moderador(): BelongsTo
    {
        return $this->belongsTo(Trabajador::class, 'id_moderador');
    }

    public function metodoPago(): BelongsTo
    {
        return $this->belongsTo(MetodoPago::class, 'id_mp');
    }

    public function cierreSemanal(): BelongsTo
    {
        return $this->belongsTo(CierreSemanal::class, 'id_cierre');
    }
}
