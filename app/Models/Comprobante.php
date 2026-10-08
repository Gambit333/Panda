<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Copia del comprobante de un reporte guardada en la BD para sobrevivir a los
 * deploys (Railway usa storage efímero). `imagen` guarda el binario comprimido
 * en base64; la ruta del disco sigue viviendo en `reporte_pagos.comprobante`.
 */
class Comprobante extends Model
{
    protected $table = 'comprobantes';

    protected $fillable = [
        'id_reporte',
        'mime',
        'tamano',
        'imagen',
    ];

    protected $hidden = [
        'imagen',
    ];

    public function reportePago(): BelongsTo
    {
        return $this->belongsTo(ReportePago::class, 'id_reporte', 'id_reporte');
    }

    /** Binario ya decodificado (ready para servir). */
    public function getBinarioAttribute(): string
    {
        return (string) base64_decode((string) $this->imagen);
    }
}
