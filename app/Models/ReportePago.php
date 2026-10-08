<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
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

    /** Copia del comprobante persistida en la BD (sobrevive a los deploys). */
    public function comprobanteBinario(): HasOne
    {
        return $this->hasOne(Comprobante::class, 'id_reporte', 'id_reporte');
    }

    public function getComprobanteUrlAttribute(): ?string
    {
        // Si hay copia en la BD se sirve por la ruta comprobantes.show; si no,
        // se usa la ruta del disco (compatibilidad con reportes antiguos).
        if ($this->comprobanteBinario !== null && filled($this->comprobanteBinario->imagen)) {
            return route('comprobantes.show', $this->id_reporte);
        }

        return $this->comprobante ? Storage::disk('public')->url($this->comprobante) : null;
    }
}
