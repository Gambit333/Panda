@extends('layouts.app')

@section('title', "Cierre #{$cierre->id_cierre}")

@section('content')
    <div class="flex-between mb-4">
        <a href="{{ route('cierres.index') }}" class="btn btn-secondary">← Cierres</a>
        <div class="actions">
            <a href="{{ route('pagos.create', ['id_cierre' => $cierre->id_cierre]) }}" class="btn btn-primary">
                + Registrar pago a empleado
            </a>
        </div>
    </div>

    <div class="cards" style="margin-bottom:1.5rem;">
        <div class="stat">
            <div class="label">Período</div>
            <div class="value" style="font-size:1.1rem;">{{ $cierre->fecha_inicio->format('d/m/Y') }} — {{ $cierre->fecha_fin->format('d/m/Y') }}</div>
        </div>
        <div class="stat">
            <div class="label">Reportes asignados</div>
            <div class="value">{{ $cierre->reportes->count() }}</div>
        </div>
        <div class="stat">
            <div class="label">Total Bruto</div>
            <div class="value positive">${{ number_format($cierre->total_bruto ?? 0, 2) }}</div>
        </div>
        <div class="stat">
            <div class="label">Total Neto (Sin comisiones)</div>
            <div class="value positive" style="font-weight:bold;">${{ number_format($cierre->total_neto ?? 0, 2) }}</div>
        </div>
        <div class="stat">
            <div class="label">Total pagado a empleados</div>
            <div class="value">${{ number_format($cierre->pagosEmpleados->sum('monto_final'), 2) }}</div>
        </div>
    </div>

    @if (count($calculos['grupos']) > 0)
        <div class="card mb-4">
            <h2 style="font-size:1rem; margin-bottom:0.5rem;">Cálculo de comisiones por cuenta</h2>
            <div style="border-bottom:1px solid var(--border,#ddd); padding-bottom:0.6rem; margin-bottom:0.6rem;">
                <div class="calc-row">
                    <span>TOTAL FACTURADO</span><strong>${{ number_format($calculos['total_facturado'], 2) }}</strong>
                </div>
                <div class="calc-row">
                    <span>TOTAL CON IMPUESTOS ({{ $calculos['impuesto_porcentaje'] }}%)</span><strong>${{ number_format($calculos['total_con_impuestos'], 2) }}</strong>
                </div>
                <div class="calc-row">
                    <span>Impuestos para pagar las cuentas ({{ $calculos['impuesto_porcentaje'] }}%)</span><strong>${{ number_format($calculos['total_impuestos'], 2) }}</strong>
                </div>
            </div>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Cuenta / Método</th>
                            <th>Bruto</th>
                            <th>Comisión</th>
                            <th>Neto</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($calculos['grupos'] as $grupo)
                            <tr>
                                <td>
                                    {{ $grupo['metodo'] }}
                                    @if ($grupo['propietario'])
                                        <small class="muted">({{ $grupo['propietario'] }})</small>
                                    @endif
                                    @if ($grupo['porcentaje'] > 0)
                                        <small class="muted">({{ number_format($grupo['porcentaje'], 0) }}%)</small>
                                    @endif
                                </td>
                                <td>${{ number_format($grupo['bruto'], 2) }}</td>
                                <td class="text-danger">${{ number_format($grupo['comision'], 2) }}</td>
                                <td class="positive" style="font-weight:bold;">${{ number_format($grupo['neto'], 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>

                </table>
            </div>
            <div class="calc-row calc-empty" style="padding-top:0.6rem; margin-top:0.6rem;">
                <span>COMISIÓN TOTAL</span><strong>${{ number_format($calculos['comision_total'], 2) }}</strong>
            </div>
            <div class="calc-row calc-empty" style="padding-top:0.6rem;">
                <span>RESTO PARA BREA (impuestos − comisión total)</span><strong>${{ number_format($calculos['resto_brea'], 2) }}</strong>
            </div>
        </div>
    @endif

    @if ($cierre->detallesPago->isNotEmpty())
        @php
            $pagos = $cierre->detallesPago->sortBy(fn ($d) => $d->trabajador?->nombre_completo ?? '');
            $totales = collect($conceptos)->mapWithKeys(fn ($label, $clave) => [$clave => $pagos->where('concepto', $clave)->sum('monto')]);
        @endphp
        <div class="card mb-4">
            <h2 style="font-size:1rem; margin-bottom:1rem;">Pagos calculados: modelos, moderadores, administración y programación</h2>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Trabajador</th>
                            <th>Total antes de impuestos</th>
                            <th>Total después de impuestos</th>
                            <th>Concepto</th>
                            <th>Detalle</th>
                            <th>Monto</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($pagos as $detalle)
                            <tr>
                                <td><strong>{{ $detalle->trabajador?->nombre_completo ?? 'Sin asignar' }}</strong></td>
                                <td>${{ number_format($detalle->total_antes_impuestos ?? 0, 2) }}</td>
                                <td>${{ number_format($detalle->total_despues_impuestos ?? 0, 2) }}</td>
                                <td>{{ $conceptos[$detalle->concepto] ?? $detalle->concepto }}</td>
                                <td><small class="muted">{{ $detalle->nota }}</small></td>
                                <td class="positive" style="font-weight:bold;">${{ number_format($detalle->monto, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>

                </table>
            </div>
            <div style="margin-top:0.75rem;">
                @foreach ($totales as $clave => $total)
                    <div class="calc-row">
                        <span>{{ $conceptos[$clave] ?? $clave }}</span><strong>${{ number_format($total, 2) }}</strong>
                    </div>
                @endforeach
                <div class="calc-row calc-empty" style="margin-top:0.5rem;">
                    <span>TOTAL A PAGAR</span><strong>${{ number_format($totales->sum(), 2) }}</strong>
                </div>
            </div>
        </div>
    @endif

    <div class="card mb-4">
        <h2 style="font-size:1rem; margin-bottom:1rem;">Reportes del cierre</h2>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Modelo</th>
                        <th>Plataforma / Cliente</th>
                        <th>Método / Cuenta</th>
                        <th>Servicio</th>
                        <th>Precio</th>
                        <th>Moderador</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($cierre->reportes as $reporte)
                        <tr>
                            <td>{{ $reporte->modelo?->nombre_completo ?? '-' }}</td>
                            <td>{{ $reporte->plataforma }} / {{ $reporte->user_cliente }}</td>
                            <td>
                                {{ $reporte->metodoPago?->metodo_pago ?? '-' }}
                                @if($reporte->metodoPago?->propietario)
                                    <small class="muted">({{ $reporte->metodoPago->propietario }})</small>
                                @endif
                            </td>
                            <td>{{ Str::limit($reporte->servicio, 25) }}</td>
                            <td class="positive">${{ number_format($reporte->precio, 2) }}</td>
                            <td>{{ $reporte->moderador?->nombre_completo ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="empty">Este cierre no tiene reportes asignados.</td></tr>
                    @endforelse
                </tbody>

            </table>
        </div>
    </div>

    <div class="card">
        <h2 style="font-size:1rem; margin-bottom:1rem;">Pagos a empleados</h2>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Empleado</th>
                        <th>Monto Bruto</th>
                        <th>Monto Neto</th>
                        <th>Deuda</th>
                        <th>Monto Final</th>
                        <th>Nota</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($cierre->pagosEmpleados as $pago)
                        <tr>
                            <td>{{ $pago->trabajador?->nombre_completo ?? '-' }}</td>
                            <td>${{ number_format($pago->monto_bruto, 2) }}</td>
                            <td>${{ number_format($pago->monto_neto, 2) }}</td>
                            <td class="text-danger">-${{ number_format($pago->deuda, 2) }}</td>
                            <td class="positive" style="font-weight:bold;">${{ number_format($pago->monto_final, 2) }}</td>
                            <td><small class="muted">{{ $pago->nota ?? '-' }}</small></td>
                            <td>
                                <div class="actions">
                                    <a href="{{ route('pagos.edit', $pago) }}" class="btn btn-secondary btn-sm">Editar</a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="empty">Aún no se registran pagos para este cierre.</td></tr>
                    @endforelse
                </tbody>

            </table>
        </div>
    </div>
@endsection