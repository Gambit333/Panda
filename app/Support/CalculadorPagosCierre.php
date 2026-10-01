<?php

namespace App\Support;

use App\Models\CierreSemanal;
use App\Models\ReportePago;
use App\Models\Trabajador;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Calcula el pago de modelos, moderadores y sección administrativa de un cierre.
 *
 * Flujo (ocurre después del cálculo de comisiones por método de pago):
 *  1. Base por reporte = precio NETO (precio menos la comisión del método de pago).
 *  2. A esa base se le saca el 15%.
 *  3. Models lo restante:
 *     - Modelo normal: 50% a la modelo, 20% al moderador, 30% al fondo administrativo.
 *     - María Brea (ceo) como modelo: 75% Brea, 18% su moderador, 7% María Pinto.
 *  4. Fondo administrativo (lo que queda, 30% de cada modelo normal) se reparte:
 *     20% al rol ceo (María Brea), 7% a María Pinto, 1.5% a cada admin.
 */
class CalculadorPagosCierre
{
    private const CORTE_IMPUESTO = 0.15;   // al total de cada modelo se le saca el 15%

    // Modelo normal
    private const MODELO = 0.50;

    private const MODERADOR = 0.20;

    private const ADMIN = 0.30;            // lo que queda (30%)

    // María Brea (ceo) como modelo
    private const BREA_MODELO = 0.75;

    private const BREA_MODERADOR = 0.18;

    private const BREA_PINTO = 0.07;

    // Reparto del fondo administrativo
    private const ADMIN_CEO = 0.20;

    private const ADMIN_PINTO = 0.07;

    private const ADMIN_CADA = 0.015;

    public const CONCEPTOS = [
        'modelo' => 'Pago como modelo',
        'moderador' => 'Pago como moderador',
        'pinto' => 'Pago a María Pinto',
        'admin' => 'Pago sección administrativa',
    ];

    /**
     * @return array<int, array{id_trab:int, concepto:string, monto:float, nota:string}>
     */
    public function calcular(CierreSemanal $cierre): array
    {
        $ceo = $this->trabajadorPorRol('ceo');
        $admins = $this->trabajadoresPorRol('admin');
        $pinto = $this->trabajadorPorApellido('pinto');

        $filas = [];
        $fondoAdmin = 0.0;

        foreach ($cierre->reportes as $reporte) {
            $base = $this->baseNeta($reporte);
            if ($base <= 0) {
                continue;
            }

            if ($ceo && (int) $reporte->id_modelo === (int) $ceo->id_trab) {
                $this->sumar($filas, $ceo->id_trab, 'modelo', $base * self::BREA_MODELO, '75% de sus ganancias como modelo');
                $this->sumar($filas, $reporte->id_moderador, 'moderador', $base * self::BREA_MODERADOR, '18% de las ganancias de la CEO');
                $this->sumar($filas, $pinto?->id_trab, 'pinto', $base * self::BREA_PINTO, '7% de las ganancias de la CEO');
            } else {
                $this->sumar($filas, $reporte->id_modelo, 'modelo', $base * self::MODELO, '50% de sus ganancias como modelo');
                $this->sumar($filas, $reporte->id_moderador, 'moderador', $base * self::MODERADOR, '20% como moderador');
                $fondoAdmin += $base * self::ADMIN;
            }
        }

        $fondoAdmin = round($fondoAdmin, 2);

        if ($fondoAdmin > 0) {
            $this->sumar($filas, $ceo?->id_trab, 'admin', $fondoAdmin * self::ADMIN_CEO, '20% del fondo administrativo (rol ceo)');
            $this->sumar($filas, $pinto?->id_trab, 'admin', $fondoAdmin * self::ADMIN_PINTO, '7% del fondo administrativo');
            foreach ($admins as $admin) {
                $this->sumar($filas, $admin->id_trab, 'admin', $fondoAdmin * self::ADMIN_CADA, '1.5% del fondo administrativo (cada admin)');
            }
        }

        return array_values($filas);
    }

    /**
     * Precio del reporte menos la comisión del método de pago, y luego menos el 15%.
     */
    private function baseNeta(ReportePago $reporte): float
    {
        $porcentaje = (float) ($reporte->metodoPago?->porcentaje_cuenta ?? 0);
        $neto = round((float) $reporte->precio * (1 - ($porcentaje / 100)), 2);

        return round($neto * (1 - self::CORTE_IMPUESTO), 2);
    }

    /**
     * @param  array<string, array{id_trab:int, concepto:string, monto:float, nota:string}>  $filas
     */
    private function sumar(array &$filas, ?int $idTrab, string $concepto, float $monto, string $nota): void
    {
        if ($idTrab === null) {
            return;
        }

        $key = $idTrab.'|'.$concepto;

        if (! isset($filas[$key])) {
            $filas[$key] = [
                'id_trab' => $idTrab,
                'concepto' => $concepto,
                'monto' => 0.0,
                'nota' => $nota,
            ];
        }

        $filas[$key]['monto'] = round($filas[$key]['monto'] + $monto, 2);
    }

    private function trabajadorPorRol(string $rol): ?Trabajador
    {
        return Trabajador::whereHas('rol', fn ($q) => $q->whereIn(DB::raw('lower(rol)'), [$rol]))->first();
    }

    /**
     * @return Collection<int, Trabajador>
     */
    private function trabajadoresPorRol(string $rol): Collection
    {
        return Trabajador::whereHas('rol', fn ($q) => $q->whereIn(DB::raw('lower(rol)'), [$rol]))->get();
    }

    private function trabajadorPorApellido(string $apellido): ?Trabajador
    {
        return Trabajador::all()->first(fn (Trabajador $t) => str_contains(mb_strtolower((string) $t->apellido), $apellido));
    }
}
