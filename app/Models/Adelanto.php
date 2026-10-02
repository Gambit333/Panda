<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Adelanto extends Model
{
    /** 'adelanto' = se descuenta de pagos futuros; 'prestamo' = préstamo a devolver */
    public const TIPOS = ['adelanto', 'prestamo'];

    protected $table = 'adelantos';

    protected $primaryKey = 'id_adelanto';

    protected $fillable = [
        'id_trab',
        'tipo',
        'monto',
        'fecha',
        'nota',
    ];

    protected function casts(): array
    {
        return [
            'monto' => 'decimal:2',
            'fecha' => 'date',
        ];
    }

    public function trabajador(): BelongsTo
    {
        return $this->belongsTo(Trabajador::class, 'id_trab');
    }

    public function abonos(): HasMany
    {
        return $this->hasMany(AbonoAdelanto::class, 'id_adelanto');
    }

    /** Monto ya abonado. Usa la suma eagerly loaded si está disponible. */
    public function getPagadoAttribute(): float
    {
        $suma = $this->attributes['abonos_sum_monto'] ?? null;

        return round((float) ($suma ?? $this->abonos()->sum('monto')), 2);
    }

    /** Saldo pendiente del trabajador. */
    public function getSaldoAttribute(): float
    {
        return round((float) $this->monto - $this->pagado, 2);
    }

    public function getSaldadoAttribute(): bool
    {
        return $this->saldo <= 0;
    }

    public function getTipoLabelAttribute(): string
    {
        return $this->tipo === 'prestamo' ? 'Préstamo' : 'Adelanto';
    }

    /**
     * Saldo pendiente por trabajador: [id_trab => saldo].
     *
     * @return array<int, float>
     */
    public static function saldosPorTrabajador(): array
    {
        $totales = static::query()
            ->selectRaw('id_trab, SUM(monto) as total')
            ->groupBy('id_trab')
            ->pluck('total', 'id_trab');

        if ($totales->isEmpty()) {
            return [];
        }

        $pagadoPorAdvance = AbonoAdelanto::query()
            ->selectRaw('id_adelanto, SUM(monto) as pagado')
            ->groupBy('id_adelanto')
            ->pluck('pagado', 'id_adelanto');

        $pagadoPorTrabajador = [];
        foreach (static::query()->get(['id_adelanto', 'id_trab']) as $adelanto) {
            $idTrab = (int) $adelanto->id_trab;
            $pagadoPorTrabajador[$idTrab] = ($pagadoPorTrabajador[$idTrab] ?? 0.0)
                + (float) ($pagadoPorAdvance[$adelanto->id_adelanto] ?? 0);
        }

        $saldos = [];
        foreach ($totales as $idTrab => $total) {
            $saldos[(int) $idTrab] = max(0.0, round((float) $total - (float) ($pagadoPorTrabajador[$idTrab] ?? 0), 2));
        }

        return $saldos;
    }

    public static function pendientePorTrabajador(int $idTrab): float
    {
        return self::saldosPorTrabajador()[$idTrab] ?? 0.0;
    }
}
