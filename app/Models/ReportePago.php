<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class ReportePago extends Model
{
    protected $table = 'reporte_pagos';

    protected $primaryKey = 'id_reporte';

    public $timestamps = false;

    protected $fillable = [
        'id_modelo',
        'plataforma',
        'user_cliente',
        'id_mp',
        'precio',
        'servicio',
        'addon_extra',
        'duracion',
        'fecha_reporte',
        'id_moderador',
        'id_cierre',
        'descripcion',
        'comprobante',
    ];

    protected function casts(): array
    {
        return [
            'fecha_reporte' => 'date',
            'precio' => 'decimal:2',
            'addon_extra' => 'decimal:2',
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

    public function getComprobanteUrlAttribute(): ?string
    {
        return $this->comprobante ? Storage::disk('public')->url($this->comprobante) : null;
    }
}
