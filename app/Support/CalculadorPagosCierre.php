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
     * @return array<int, array{id_trab:int, concepto:string, monto:float, nota:string, total_antes:float, total_despues:float}>
     */
    public function calcular(CierreSemanal $cierre): array
    {
        $ceo = $this->trabajadorPorRol('ceo');
        $admins = $this->trabajadoresPorRol('admin');
        $pinto = $this->trabajadorPorApellido('pinto');

        $filas = [];
        $fondoAdmin = 0.0;
        $antes = [];
        $despues = [];
        $baseModelos = [];
        $baseModeradores = [];
        $baseModeradoresCeo = [];

        foreach ($cierre->reportes as $reporte) {
            $antesReporte = round((float) $reporte->precio, 2);
            $despuesReporte = $this->baseConImpuestos($reporte);

            if ($despuesReporte <= 0) {
                continue;
            }

            // Totales por trabajador: total de ventas (antes de impuestos) y el número
            // final que entrega el cálculo de comisiones por cuenta (después de impuestos).
            foreach (array_unique([(int) $reporte->id_modelo, (int) $reporte->id_moderador]) as $idParticipa) {
                $antes[$idParticipa] = round(($antes[$idParticipa] ?? 0) + $antesReporte, 2);
                $despues[$idParticipa] = round(($despues[$idParticipa] ?? 0) + $despuesReporte, 2);
            }

            $idModelo = (int) $reporte->id_modelo;
            $idModerador = (int) $reporte->id_moderador;
            $baseModelos[$idModelo] = round(($baseModelos[$idModelo] ?? 0) + $despuesReporte, 2);

            if ($ceo && $idModelo === (int) $ceo->id_trab) {
                $baseModeradoresCeo[$idModerador] = round(($baseModeradoresCeo[$idModerador] ?? 0) + $despuesReporte, 2);
            } else {
                $baseModeradores[$idModerador] = round(($baseModeradores[$idModerador] ?? 0) + $despuesReporte, 2);
            }
        }

        // Los porcentajes se aplican sobre el número final ya agregado de cada trabajador.
        foreach ($baseModelos as $idModelo => $total) {
            if ($ceo && $idModelo === (int) $ceo->id_trab) {
                $this->sumar($filas, $idModelo, 'modelo', $total * self::BREA_MODELO, '75% de sus ganancias como modelo');
                $this->sumar($filas, $pinto?->id_trab, 'pinto', $total * self::BREA_PINTO, '7% de las ganancias de la CEO');
            } else {
                $this->sumar($filas, $idModelo, 'modelo', $total * self::MODELO, '50% de sus ganancias como modelo');
                $fondoAdmin += $total * self::ADMIN;
            }
        }

        foreach ($baseModeradores as $idModerador => $total) {
            $this->sumar($filas, $idModerador, 'moderador', $total * self::MODERADOR, '20% como moderador');
        }

        foreach ($baseModeradoresCeo as $idModerador => $total) {
            $this->sumar($filas, $idModerador, 'moderador', $total * self::BREA_MODERADOR, '18% de las ganancias de la CEO');
        }

        $fondoAdmin = round($fondoAdmin, 2);

        if ($fondoAdmin > 0) {
            $this->sumar($filas, $ceo?->id_trab, 'admin', $fondoAdmin * self::ADMIN_CEO, '20% del fondo administrativo (rol ceo)');
            $this->sumar($filas, $pinto?->id_trab, 'admin', $fondoAdmin * self::ADMIN_PINTO, '7% del fondo administrativo');
            foreach ($admins as $admin) {
                $this->sumar($filas, $admin->id_trab, 'admin', $fondoAdmin * self::ADMIN_CADA, '1.5% del fondo administrativo (cada admin)');
            }
        }

        // Cada fila del trabajador lleva sus totales informativos (antes y después del 15%).
        foreach ($filas as $key => $fila) {
            $filas[$key]['total_antes'] = round($antes[$fila['id_trab']] ?? 0, 2);
            $filas[$key]['total_despues'] = round($despues[$fila['id_trab']] ?? 0, 2);
        }

        return array_values($filas);
    }

    /**
     * Número final para los pagos: precio de venta − 15% de impuestos.
     * La comisión del método de pago ya se calcula aparte en la tarjeta de
     * comisiones por cuenta, por eso no se resta aquí.
     */
    private function baseConImpuestos(ReportePago $reporte): float
    {
        $precio = round((float) $reporte->precio, 2);

        return round($precio * (1 - self::CORTE_IMPUESTO), 2);
    }

    /**
     * @param  array<string, array{id_trab:int, concepto:string, monto:float, nota:string, total_antes?:float, total_despues?:float}>  $filas
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
