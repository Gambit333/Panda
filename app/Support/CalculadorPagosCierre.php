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
 *     Ese total se resta una vez (base compartida para TODOS los administrativos):
 *     TOTAL − ventas netas de los administrativos que a la vez participaron como
 *     modelo o moderador. Como la misma persona puede tener varias fichas (una como
 *     modelo y otra como ceo, cada una con su %), se reconocen por nombre+apellido.
 *     Si nadie de la sección administrativa vendió, la base es el total completo.
 *     Única excepción (BASE_COMPLETA): María Pinto siempre calcula sobre el total
 *     completo, sin restar nada.
 *
 * Los porcentajes se configuran en la ficha (un solo campo `porcentaje`, vale para
 * todos los contextos del trabajador). La única excepción por persona es
 * `BASE_COMPLETA` (María Pinto), para su parte administrativa.
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

    /**
     * Única excepción por persona: calcula su parte administrativa sobre el
     * total completo (sin restar las ventas netas de los administrativos).
     * Formato [nombre, apellido] en minúsculas sin acentos.
     */
    private const BASE_COMPLETA = [
        ['maria', 'pinto'],
    ];

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
        return $this->resumen($cierre->reportes)['filas'];
    }

    /**
     * Resumen "hasta el momento": misma lógica que un cierre semanal, pero
     * tomando los reportes que todavía no tienen cierre (id_cierre NULL).
     *
     * @return array{
     *   reportes:int,
     *   total_facturado:float,
     *   total_sin_impuestos:float,
     *   filas:array<int, array{id_trab:int, concepto:string, monto:float, nota:string, total_antes:float, total_despues:float, nombre:string}>,
     *   por_concepto:array<string, float>,
     *   moderador_por_modelo:array<int, array<int, array{id_modelo:int, modelo:string, total:float, porcentaje:float, monto:float}>>
     * }
     */
    public function resumenHastaAhora(): array
    {
        return $this->resumen(
            ReportePago::with(['modelo', 'moderador'])->whereNull('id_cierre')->get()
        );
    }

    /**
     * @param  Collection<int, ReportePago>  $reportes
     * @return array{
     *   reportes:int,
     *   total_facturado:float,
     *   total_sin_impuestos:float,
     *   filas:array<int, array{id_trab:int, concepto:string, monto:float, nota:string, total_antes:float, total_despues:float, nombre:string}>,
     *   por_concepto:array<string, float>,
     *   moderador_por_modelo:array<int, array<int, array{id_modelo:int, modelo:string, total:float, porcentaje:float, monto:float}>>
     * }
     */
    private function resumen(Collection $reportes): array
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

        foreach ($reportes as $reporte) {
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

            // Base de la sección administrativa: el total sin impuestos de los reportes,
            // sin descontar lo que cobran las modelos ni sus moderadores.
            $totalSinImpuestos = round($totalSinImpuestos + $despuesReporte, 2);
        }

        // Los porcentajes se aplican sobre el número final ya agregado de cada trabajador.
        foreach ($baseModelos as $idModelo => $total) {
            $pct = $this->porcentaje($trabajadores->get($idModelo), self::MODELO);
            $this->sumar($filas, $idModelo, 'modelo', $total * $pct, $this->txt($pct).'% de sus ganancias como modelo');
        }

        $porcentajesPorModelo = $this->porcentajesModeradorModelo();
        $moderadorPorModelo = [];

        foreach ($baseModeradores as $idModerador => $porModelo) {
            $moderador = $trabajadores->get($idModerador);

            foreach ($porModelo as $idModelo => $total) {
                $pct = $this->porcentajeModerador($moderador, $porcentajesPorModelo[$idModerador.'|'.$idModelo] ?? null);
                $modelo = $trabajadores->get($idModelo);
                $nota = $this->txt($pct).'% como moderador'
                    .($modelo ? ' ('.$modelo->nombre.' '.$modelo->apellido.')' : '');
                $monto = round($total * $pct, 2);
                $this->sumar($filas, $idModerador, 'moderador', $monto, $nota);
                $moderadorPorModelo[$idModerador][$idModelo] = [
                    'id_modelo' => $idModelo,
                    'modelo' => $modelo?->nombre_completo ?? 'Trabajador #'.$idModelo,
                    'total' => $total,
                    'porcentaje' => round($pct * 100, 2),
                    'monto' => $monto,
                ];
            }
        }

        if ($totalSinImpuestos > 0) {
            // Base compartida: al total sin impuestos se le restan (una sola vez) las
            // ventas netas de los administrativos que además vendieron como modelo o
            // moderador. Como una misma persona puede tener varias fichas (p. ej. una
            // como modelo y otra como ceo, cada una con su porcentaje), se reconocen
            // por nombre+apellido: las ventas de TODAS sus fichas se restan.
            $ventasAdministrativos = 0.0;
            foreach ($this->idsVentasAdministrativos($ceo, $admins, $soportes, $programadores) as $idVenta) {
                $ventasAdministrativos = round($ventasAdministrativos + ($despues[$idVenta] ?? 0), 2);
            }
            $baseCompartida = max(0.0, round($totalSinImpuestos - $ventasAdministrativos, 2));

            $pctCeo = $this->porcentaje($ceo, self::ADMIN_CEO);
            $this->sumar(
                $filas,
                $ceo?->id_trab,
                'admin',
                $this->baseAdministrativa($ceo, $baseCompartida, $totalSinImpuestos) * $pctCeo,
                $this->notaAdministrativa($pctCeo, 'rol ceo', $ceo, $baseCompartida, $totalSinImpuestos)
            );

            foreach ($admins as $admin) {
                $pctAdmin = $this->porcentaje($admin, self::ADMIN_CADA);
                $this->sumar(
                    $filas,
                    $admin->id_trab,
                    'admin',
                    $this->baseAdministrativa($admin, $baseCompartida, $totalSinImpuestos) * $pctAdmin,
                    $this->notaAdministrativa($pctAdmin, 'cada admin', $admin, $baseCompartida, $totalSinImpuestos)
                );
            }

            foreach ($soportes as $soporte) {
                $pctSupport = $this->porcentaje($soporte, self::ADMIN_SUPPORT);
                $this->sumar(
                    $filas,
                    $soporte->id_trab,
                    'admin',
                    $this->baseAdministrativa($soporte, $baseCompartida, $totalSinImpuestos) * $pctSupport,
                    $this->notaAdministrativa($pctSupport, 'rol support', $soporte, $baseCompartida, $totalSinImpuestos)
                );
            }

            foreach ($programadores as $programador) {
                $pctProgramador = $this->porcentaje($programador, self::ADMIN_PROGRAMADOR);
                $this->sumar(
                    $filas,
                    $programador->id_trab,
                    'programador',
                    $this->baseAdministrativa($programador, $baseCompartida, $totalSinImpuestos) * $pctProgramador,
                    $this->notaAdministrativa($pctProgramador, 'rol programador', $programador, $baseCompartida, $totalSinImpuestos)
                );
            }
        }

        // Cada fila del trabajador lleva sus totales informativos (antes y después del 15%).
        foreach ($filas as $key => $fila) {
            $filas[$key]['total_antes'] = round($antes[$fila['id_trab']] ?? 0, 2);
            $filas[$key]['total_despues'] = round($despues[$fila['id_trab']] ?? 0, 2);
            $filas[$key]['nombre'] = $trabajadores->get($fila['id_trab'])?->nombre_completo
                ?? 'Trabajador #'.$fila['id_trab'];
        }

        $porConcepto = [];
        foreach ($filas as $fila) {
            $porConcepto[$fila['concepto']] = round(($porConcepto[$fila['concepto']] ?? 0) + $fila['monto'], 2);
        }

        return [
            'reportes' => $reportes->count(),
            'total_facturado' => round((float) $reportes->sum('precio'), 2),
            'total_sin_impuestos' => $totalSinImpuestos,
            'filas' => array_values($filas),
            'por_concepto' => $porConcepto,
            'moderador_por_modelo' => $moderadorPorModelo,
        ];
    }

    /**
     * IDs únicos de todos los trabajadores de la sección administrativa.
     *
     * @param  Collection<int, Trabajador>  ...$grupos
     * @return array<int, int>
     */
    private function idsAdministrativos(?Trabajador $ceo, Collection ...$grupos): array
    {
        $ids = $ceo !== null ? [$ceo->id_trab] : [];

        foreach ($grupos as $grupo) {
            foreach ($grupo as $trabajador) {
                $ids[] = $trabajador->id_trab;
            }
        }

        return array_values(array_unique($ids));
    }

    /**
     * IDs de TODAS las fichas de los administrativos que también vendieron como
     * modelo o moderador, para restarlas de la base compartida. Como una misma
     * persona puede tener varias fichas (p. ej. una como modelo y otra como ceo,
     * cada una con su porcentaje), se reconocen por nombre+apellido: las ventas
     * de cualquiera de sus fichas se descuentan. No incluye a la excepción
     * (`BASE_COMPLETA`, p. ej. María Pinto), que nunca se resta.
     *
     * @param  Collection<int, Trabajador>  ...$grupos
     * @return array<int, int>
     */
    private function idsVentasAdministrativos(?Trabajador $ceo, Collection ...$grupos): array
    {
        $administrativos = [];
        if ($ceo !== null) {
            $administrativos[] = $ceo;
        }
        foreach ($grupos as $grupo) {
            foreach ($grupo as $trabajador) {
                $administrativos[] = $trabajador;
            }
        }

        $claves = [];
        foreach ($administrativos as $trabajador) {
            if ($this->baseCompleta($trabajador)) {
                continue;
            }
            $claves[$this->clavePersona($trabajador)] = true;
        }

        if ($claves === []) {
            return [];
        }

        return Trabajador::all()
            ->filter(fn (Trabajador $t) => isset($claves[$this->clavePersona($t)]))
            ->pluck('id_trab')
            ->all();
    }

    /** Clave para reconocer a la misma persona: nombre+apellido normalizados. */
    private function clavePersona(Trabajador $trabajador): string
    {
        return $this->normalizar($trabajador->nombre).'|'.$this->normalizar($trabajador->apellido);
    }

    /**
     * Base de la sección administrativa para un trabajador. Salvo la excepción
     * (`BASE_COMPLETA`, p. ej. María Pinto) usa la base compartida; la excepción
     * usa el total sin impuestos completo.
     */
    private function baseAdministrativa(?Trabajador $trabajador, float $baseCompartida, float $totalSinImpuestos): float
    {
        if ($this->baseCompleta($trabajador)) {
            return $totalSinImpuestos;
        }

        return $baseCompartida;
    }

    /**
     * Nota de una fila administrativa; aclara cuando la base es el total completo
     * por la excepción de `BASE_COMPLETA`.
     */
    private function notaAdministrativa(float $porcentaje, string $detalle, ?Trabajador $trabajador, float $baseCompartida, float $totalSinImpuestos): string
    {
        $excepcion = $this->baseCompleta($trabajador) && $baseCompartida < $totalSinImpuestos;

        return $this->txt($porcentaje).'% del total sin impuestos completo'
            .($excepcion ? ' (excepción)' : '')
            .' ('.$detalle.')';
    }

    /** ¿El trabajador calcula su parte administrativa sobre el total completo? */
    private function baseCompleta(?Trabajador $trabajador): bool
    {
        if ($trabajador === null) {
            return false;
        }

        $clave = [
            $this->normalizar($trabajador->nombre),
            $this->normalizar($trabajador->apellido),
        ];

        return in_array($clave, self::BASE_COMPLETA, true);
    }

    /** Normaliza un nombre para comparar: minúsculas y sin acentos. */
    private function normalizar(?string $texto): string
    {
        $texto = mb_strtolower(trim((string) $texto), 'UTF-8');

        return strtr($texto, [
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u', 'ñ' => 'n',
        ]);
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
