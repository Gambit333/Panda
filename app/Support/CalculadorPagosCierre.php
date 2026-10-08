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
 *  1. Base por reporte = precio de venta (la comisión por método de pago se calcula
 *     aparte, en la tarjeta de comisiones por cuenta, por eso no se resta aquí).
 *  2. A esa base se le saca el 15%.
 *  3. Cada trabajador cobra su PORCENTAJE (columna `trabajador.porcentaje`) sobre ese
 *     número; si está vacío se usa el valor por defecto del contexto:
 *     - Como modelo: 50% de sus ventas.
 *     - Como moderador: su porcentaje se define POR MODELO en la asignación
 *       (`modelo_moderador.porcentaje`); vacío = el `porcentaje` del moderador o,
 *       si tampoco, 20% de las ventas de cada modelo que modera.
 *  4. La sección administrativa NO cobra de "lo que sobra" de las modelos: sus pagos
 *     salen del TOTAL sin impuestos (Σ precio − 15%) del cierre, cada uno con su %:
 *     20% rol `ceo`, 1.5% a cada rol `admin`, 1.5% a cada rol `support` y 1.5% a cada
 *     rol `programador` (ceo/admin/support → concepto `admin`; programador → `programador`).
 *
 * No hay casos especiales por persona: quien quiera un reparto distinto (p. ej. a
 * María Brea cobrando como modelo o a María Pinto cobrando de la sección
 * administrativa) lo define con su propio `porcentaje` y el rol que le corresponda.
 * Un solo campo `porcentaje` por trabajador: se aplica en todos los contextos en los
 * que participe.
 */
class CalculadorPagosCierre
{
    private const CORTE_IMPUESTO = 0.15;   // al total de cada modelo se le saca el 15%

    // Valores por defecto (solo se usan si el trabajador no tiene `porcentaje`)
    private const MODELO = 50;

    private const MODERADOR = 20;

    // Sección administrativa: porcentaje sobre el TOTAL sin impuestos del cierre
    private const ADMIN_CEO = 20;

    private const ADMIN_CADA = 1.5;

    private const ADMIN_SUPPORT = 1.5;

    private const ADMIN_PROGRAMADOR = 1.5;

    public const CONCEPTOS = [
        'modelo' => 'Pago como modelo',
        'moderador' => 'Pago como moderador',
        'admin' => 'Pago sección administrativa',
        'programador' => 'Pago a programación',
    ];

    /**
     * @return array<int, array{id_trab:int, concepto:string, monto:float, nota:string, total_antes:float, total_despues:float}>
     */
    public function calcular(CierreSemanal $cierre): array
    {
        $ceo = $this->trabajadorPorRol('ceo');
        $admins = $this->trabajadoresPorRol('admin');
        $soportes = $this->trabajadoresPorRol('support');
        $programadores = $this->trabajadoresPorRol('programador');
        $trabajadores = Trabajador::all()->keyBy('id_trab');

        $filas = [];
        $totalSinImpuestos = 0.0;
        $antes = [];
        $despues = [];
        $baseModelos = [];
        $baseModeradores = [];

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
            $baseModeradores[$idModerador][$idModelo] = round(($baseModeradores[$idModerador][$idModelo] ?? 0) + $despuesReporte, 2);

            // Base de la sección administrativa: el total sin impuestos del cierre,
            // sin descontar lo que cobran las modelos ni sus moderadores.
            $totalSinImpuestos = round($totalSinImpuestos + $despuesReporte, 2);
        }

        // Los porcentajes se aplican sobre el número final ya agregado de cada trabajador.
        foreach ($baseModelos as $idModelo => $total) {
            $pct = $this->porcentaje($trabajadores->get($idModelo), self::MODELO);
            $this->sumar($filas, $idModelo, 'modelo', $total * $pct, $this->txt($pct).'% de sus ganancias como modelo');
        }

        $porcentajesPorModelo = $this->porcentajesModeradorModelo();

        foreach ($baseModeradores as $idModerador => $porModelo) {
            $moderador = $trabajadores->get($idModerador);

            foreach ($porModelo as $idModelo => $total) {
                $pct = $this->porcentajeModerador($moderador, $porcentajesPorModelo[$idModerador.'|'.$idModelo] ?? null);
                $modelo = $trabajadores->get($idModelo);
                $nota = $this->txt($pct).'% como moderador'
                    .($modelo ? ' ('.$modelo->nombre.' '.$modelo->apellido.')' : '');
                $this->sumar($filas, $idModerador, 'moderador', $total * $pct, $nota);
            }
        }

        if ($totalSinImpuestos > 0) {
            $pctCeo = $this->porcentaje($ceo, self::ADMIN_CEO);
            $this->sumar($filas, $ceo?->id_trab, 'admin', $totalSinImpuestos * $pctCeo, $this->txt($pctCeo).'% del total sin impuestos (rol ceo)');
            foreach ($admins as $admin) {
                $pctAdmin = $this->porcentaje($admin, self::ADMIN_CADA);
                $this->sumar($filas, $admin->id_trab, 'admin', $totalSinImpuestos * $pctAdmin, $this->txt($pctAdmin).'% del total sin impuestos (cada admin)');
            }
            foreach ($soportes as $soporte) {
                $pctSupport = $this->porcentaje($soporte, self::ADMIN_SUPPORT);
                $this->sumar($filas, $soporte->id_trab, 'admin', $totalSinImpuestos * $pctSupport, $this->txt($pctSupport).'% del total sin impuestos (rol support)');
            }
            foreach ($programadores as $programador) {
                $pctProgramador = $this->porcentaje($programador, self::ADMIN_PROGRAMADOR);
                $this->sumar($filas, $programador->id_trab, 'programador', $totalSinImpuestos * $pctProgramador, $this->txt($pctProgramador).'% del total sin impuestos (rol programador)');
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
     * Porcentaje (en tanto por uno) que cobra un trabajador en un contexto dado.
     * Si no tiene `porcentaje` definido se usa el valor por defecto del contexto.
     */
    private function porcentaje(?Trabajador $trabajador, float $porDefecto): float
    {
        if ($trabajador === null || $trabajador->porcentaje === null) {
            return $porDefecto / 100;
        }

        return ((float) $trabajador->porcentaje) / 100;
    }

    /**
     * Porcentaje (en tanto por uno) que cobra un moderador por las ventas de una
     * modelo. Prevalencia: % definido en la asignación (pivote) > % propio del
     * moderador > defecto del rol (20%).
     *
     * @param  float|null  $porModelo  % guardado en `modelo_moderador.porcentaje`
     */
    private function porcentajeModerador(?Trabajador $moderador, ?float $porModelo): float
    {
        if ($porModelo !== null) {
            return $porModelo / 100;
        }

        return $this->porcentaje($moderador, self::MODERADOR);
    }

    /**
     * Porcentajes guardados en la asignación modelo-moderador.
     *
     * @return array<string, float> clave "id_moderador|id_modelo" => porcentaje
     */
    private function porcentajesModeradorModelo(): array
    {
        return DB::table('modelo_moderador')
            ->whereNotNull('porcentaje')
            ->get()
            ->mapWithKeys(fn ($fila) => [$fila->id_moderador.'|'.$fila->id_modelo => (float) $fila->porcentaje])
            ->all();
    }

    /** Formatea un porcentaje (tanto por uno) para las notas: 0.015 → "1.5". */
    private function txt(float $porcentaje): string
    {
        return rtrim(rtrim(number_format($porcentaje * 100, 2, '.', ''), '0'), '.');
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
}
