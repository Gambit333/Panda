@extends('layouts.app')

@section('title', 'Inicio')

@section('content')
    @if (isset($modoPropietario) && $modoPropietario)
        {{-- VISTA MODO PROPIETARIO (dueños de métodos de pago) --}}

        <div class="cards">
            <div class="stat stat-destacado">
                <div class="label">Ingresos de mis métodos de pago</div>
                <div class="value positive">${{ number_format($stats['ingresos'] ?? 0, 2) }}</div>
                <div class="stat-note">{{ number_format($stats['reportes'] ?? 0) }} {{ ($stats['reportes'] ?? 0) === 1 ? 'reporte' : 'reportes' }} · {{ $modoPropietario['periodo'] }}</div>
                @if (($stats['ganancia_propietario'] ?? 0) != 0)
                    <div class="stat-note mt-2">Ganancia estimada para ti: <strong class="positive">${{ number_format($stats['ganancia_propietario'], 2) }}</strong></div>
                @endif
            </div>
        </div>

        {{-- Ganancias hasta el momento del propietario (solo las suyas: % de sus métodos) --}}
        <div class="card" style="margin-bottom:1.5rem;">
            <div class="flex-between mb-4">
                <h2 style="font-size:1rem;">Mis ganancias hasta el momento</h2>
                <span class="badge">{{ $modoPropietario['periodo'] }}</span>
            </div>
            <div class="calc-row">
                <span>INGRESOS DE MIS MÉTODOS</span><strong>${{ number_format($stats['ingresos'] ?? 0, 2) }}</strong>
            </div>
            <div class="calc-row calc-empty" style="margin-top:.5rem;">
                <span>MIS GANANCIAS (según el % de mis métodos)</span>
                <strong class="positive">${{ number_format($stats['ganancia_propietario'] ?? 0, 2) }}</strong>
            </div>
        </div>

        <div class="card">
            <div class="flex-between mb-4">
                <h2 style="font-size:1rem;">Ingresos por método de pago</h2>
                <span class="badge">{{ $modoPropietario['periodo'] }}</span>
            </div>
            @php $max = $ingresosPorMetodo->max('total') ?: 1; $totalMetodos = $ingresosPorMetodo->sum('total') ?: 1; @endphp
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Método de pago</th>
                            <th>Titular</th>
                            <th>Reportes</th>
                            <th>Ingresos</th>
                            <th>% del total</th>
                            <th>Participación</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($ingresosPorMetodo as $item)
                            <tr>
                                <td><strong>{{ $item['metodo_pago'] }}</strong></td>
                                <td class="muted">{{ $item['propietario'] ?? '-' }}</td>
                                <td>{{ $item['reportes'] }}</td>
                                <td class="positive"><strong>${{ number_format($item['total'], 2) }}</strong></td>
                                <td>{{ number_format(($item['total'] / $totalMetodos) * 100, 1) }}%</td>
                                <td style="min-width: 140px;">
                                    <span style="display: block; background: var(--border); border-radius: 999px; height: 8px; overflow: hidden;">
                                        <span style="display: block; width: {{ ($item['total'] / $max) * 100 }}%; background: var(--primary); height: 8px; border-radius: 999px;"></span>
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="empty">No tienes métodos de pago asignados. Pídele a un administrador que te asigne uno.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card">
            <div class="flex-between mb-4">
                <h2 style="font-size:1rem;">Ingresos registrados</h2>
                <span class="badge">{{ $modoPropietario['periodo'] }}</span>
            </div>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Plataforma</th>
                            <th>Cliente</th>
                            <th>Servicio</th>
                            <th>Método de pago</th>
                            <th>Precio</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($reportes as $reporte)
                            <tr>
                                <td>{{ $reporte->fecha_reporte?->format('d/m/Y') ?? '-' }}</td>
                                <td><strong>{{ $reporte->plataforma }}</strong></td>
                                <td class="muted">{{ $reporte->user_cliente }}</td>
                                <td>{{ Str::limit($reporte->servicio, 30) }}</td>
                                <td>{{ $reporte->metodoPago?->metodo_pago ?? '-' }}</td>
                                <td class="positive"><strong>${{ number_format($reporte->precio, 2) }}</strong></td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="empty">Todavía no hay ingresos registrados en tus métodos de pago.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @elseif ($modoEmpleado)
        {{-- VISTA MODO EMPLEADO (Moderador / Modelo) --}}
        <div class="cards">
            <div class="stat">
                <div class="label">Mis ingresos reportados</div>
                <div class="value positive">${{ number_format($stats['reportado'] ?? 0, 2) }}</div>
            </div>
            <div class="stat">
                <div class="label">Reportes realizados</div>
                <div class="value">{{ number_format($stats['reportes'] ?? 0) }}</div>
            </div>
            <div class="stat">
                <div class="label">Monto liquidado</div>
                <div class="value">${{ number_format($stats['liquidado'] ?? 0, 2) }}</div>
            </div>
        </div>

        {{-- Ganancias hasta el momento con el porcentaje del trabajador (igual que el cierre semanal) --}}
        <div class="card" style="margin-bottom:1.5rem;">
            <div class="flex-between mb-4">
                <h2 style="font-size:1rem;">Ganancias hasta el momento</h2>
                <span class="badge">{{ $modoEmpleado === 'modelo' ? 'Como modelo' : 'Como moderador' }}</span>
            </div>
            <div class="calc-row">
                <span>TOTAL ANTES DE IMPUESTOS</span><strong>${{ number_format($miResumen['total_antes'] ?? 0, 2) }}</strong>
            </div>
            <div class="calc-row">
                <span>TOTAL DESPUÉS DE IMPUESTOS (15%)</span><strong>${{ number_format($miResumen['total_despues'] ?? 0, 2) }}</strong>
            </div>
            <div class="calc-row calc-empty" style="margin-top:.5rem;">
                <span>MIS GANANCIAS (con mi porcentaje)</span>
                <strong class="positive">${{ number_format($miResumen['ganancia'] ?? 0, 2) }}</strong>
            </div>
            @if ($modoEmpleado === 'moderador')
                <p class="muted small" style="margin-top:.5rem;">El desglose por modelo aparece en «Ganancias de mis modelos».</p>
            @endif
        </div>

        <div class="card">
            <div class="flex-between mb-4">
                <h2 style="font-size:1rem;">Mis ganancias recientes</h2>
                @if ($modoEmpleado === 'moderador')
                    <a href="{{ route('reportes.create') }}" class="btn btn-primary btn-sm">+ Nuevo reporte</a>
                @endif
            </div>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            @if ($modoEmpleado === 'moderador')
                                <th>Modelo</th>
                            @endif
                            <th>Fecha</th>
                            <th>Plataforma / Cliente</th>
                            <th>Servicio</th>
                            <th>Precio</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($reportes as $reporte)
                            <tr>
                                @if ($modoEmpleado === 'moderador')
                                    <td><strong>{{ $reporte->modelo?->nombre_completo ?? '-' }}</strong></td>
                                @endif
                                <td>{{ $reporte->fecha_reporte?->format('d/m/Y') ?? '-' }}</td>
                                <td>
                                    <strong>{{ $reporte->plataforma }}</strong>
                                    <span class="muted">/ {{ $reporte->user_cliente }}</span>
                                </td>
                                <td>{{ Str::limit($reporte->servicio, 35) }}</td>
                                <td class="positive"><strong>${{ number_format($reporte->precio, 2) }}</strong></td>
                            </tr>
                        @empty
                            <tr><td colspan="{{ $modoEmpleado === 'moderador' ? 5 : 4 }}" class="empty">Aún no tienes reportes registrados.</td></tr>
                        @endforelse
                    </tbody>

                </table>
            </div>
        </div>

        @if ($modoEmpleado === 'moderador' && $modelosGanancias->isNotEmpty())
            <div class="card">
                <div class="flex-between mb-4">
                    <h2 style="font-size:1rem;">Ganancias de mis modelos</h2>
                    <span class="badge">Ventas · Mi ganancia</span>
                </div>
                <ol class="ranking">
                    @forelse ($modelosGanancias as $item)
                        <li>
                            <span class="rank">{{ $loop->iteration }}</span>
                            <span class="who">{{ $item['nombre'] }}</span>
                            <span class="amount positive" title="Ventas de la modelo">${{ number_format($item['total'], 2) }}</span>
                            <span class="muted small">{{ $item['reportes'] }} {{ $item['reportes'] === 1 ? 'reporte' : 'reportes' }}</span>
                            <span class="amount positive" title="Tu ganancia por esta modelo">+${{ number_format($item['ganancia'] ?? 0, 2) }}</span>
                        </li>
                    @empty
                        <li class="empty">No tienes modelos asignadas.</li>
                    @endforelse
                </ol>
            </div>
        @endif
    @else
        {{-- VISTA MODO ADMINISTRADOR --}}
        <div class="cards">
            <div class="stat stat-destacado">
                <div class="label">Ingresos sin cerrar</div>
                <div class="value positive">${{ number_format($stats['ingresos'] ?? 0, 2) }}</div>
                <div class="stat-note">{{ number_format($stats['reportes_sin_cierre'] ?? 0) }} {{ ($stats['reportes_sin_cierre'] ?? 0) === 1 ? 'reporte pendiente' : 'reportes pendientes' }} de cerrar</div>
            </div>
            <div class="stat">
                <div class="label">Trabajadores</div>
                <div class="value">{{ number_format($stats['trabajadores'] ?? 0) }}</div>
            </div>
            <div class="stat">
                <div class="label">Reportes de pago</div>
                <div class="value">{{ number_format($stats['reportes'] ?? 0) }}</div>
            </div>
            <div class="stat">
                <div class="label">Cierres semanales</div>
                <div class="value">{{ number_format($stats['cierres'] ?? 0) }}</div>
            </div>
            <div class="stat">
                <div class="label">Pagos a empleados</div>
                <div class="value">${{ number_format($stats['pagos_empleados'] ?? 0, 2) }}</div>
            </div>
        </div>

        {{-- Ganancias hasta el momento: mismo cálculo que un cierre semanal, con los reportes sin cerrar --}}
        <div class="card" style="margin-bottom:1.5rem;">
            <div class="flex-between mb-4">
                <h2 style="font-size:1rem;">Ganancias hasta el momento (como cierre semanal)</h2>
                <span class="badge">{{ number_format($resumen['reportes'] ?? 0) }} reportes sin cerrar</span>
            </div>
            @php $filasResumen = $resumen['filas'] ?? []; @endphp
            <div style="border-bottom:1px solid var(--border,#ddd); padding-bottom:.6rem; margin-bottom:.6rem;">
                <div class="calc-row">
                    <span>TOTAL FACTURADO</span><strong>${{ number_format($resumen['total_facturado'] ?? 0, 2) }}</strong>
                </div>
                <div class="calc-row">
                    <span>TOTAL SIN IMPUESTOS (15%)</span><strong>${{ number_format($resumen['total_sin_impuestos'] ?? 0, 2) }}</strong>
                </div>
            </div>
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
                        @forelse ($filasResumen as $fila)
                            <tr>
                                <td><strong>{{ $fila['nombre'] }}</strong></td>
                                <td>${{ number_format($fila['total_antes'] ?? 0, 2) }}</td>
                                <td>${{ number_format($fila['total_despues'] ?? 0, 2) }}</td>
                                <td>{{ $resumenConceptos[$fila['concepto']] ?? $fila['concepto'] }}</td>
                                <td><small class="muted">{{ $fila['nota'] }}</small></td>
                                <td class="positive" style="font-weight:bold;">${{ number_format($fila['monto'], 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="empty">Todavía no hay ganancias por calcular.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div style="margin-top:.75rem;">
                @foreach ($resumenConceptos as $clave => $label)
                    <div class="calc-row">
                        <span>{{ $label }}</span><strong>${{ number_format($resumen['por_concepto'][$clave] ?? 0, 2) }}</strong>
                    </div>
                @endforeach
                <div class="calc-row calc-empty" style="margin-top:.5rem;">
                    <span>TOTAL A PAGAR</span><strong>${{ number_format(collect($filasResumen)->sum('monto'), 2) }}</strong>
                </div>
            </div>
        </div>

        <div style="display:grid; grid-template-columns: 1fr; gap:1.5rem;">
            {{-- Ingresos sin cerrar por Método de Pago (solo reportes con id_cierre NULL) --}}
            <div class="card">
                <div class="flex-between mb-4">
                    <h2 style="font-size:1rem;">Ingresos sin cerrar por método de pago</h2>
                </div>
                @php $max = $ingresosPorMetodo->max('total') ?: 1; $totalMetodos = $ingresosPorMetodo->sum('total') ?: 1; @endphp
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Método de pago</th>
                                <th>Ingresos</th>
                                <th>% del total</th>
                                <th>Participación</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($ingresosPorMetodo as $item)
                                <tr>
                                    <td><strong>{{ $item->metodo_pago }}</strong></td>
                                    <td class="positive"><strong>${{ number_format($item->total, 2) }}</strong></td>
                                    <td>{{ number_format(($item->total / $totalMetodos) * 100, 1) }}%</td>
                                    <td style="min-width: 140px;">
                                        <span style="display: block; background: var(--border); border-radius: 999px; height: 8px; overflow: hidden;">
                                            <span style="display: block; width: {{ ($item->total / $max) * 100 }}%; background: var(--primary); height: 8px; border-radius: 999px;"></span>
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="empty">No hay ingresos pendientes de cerrar.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Ranking de modelos y moderadores (solo reportes sin cerrar) --}}
            <div class="grid-2">
                <div class="card">
                    <div class="flex-between mb-4">
                        <h2 style="font-size:1rem;">Modelos que más venden</h2>
                        <span class="badge">Sin cerrar</span>
                    </div>
                    <ol class="ranking">
                        @forelse ($topModelos as $item)
                            <li>
                                <span class="rank">{{ $loop->iteration }}</span>
                                <span class="who">{{ $item['nombre'] }}</span>
                                <span class="amount positive">${{ number_format($item['total'], 2) }}</span>
                                <span class="muted small">{{ $item['reportes'] }} {{ $item['reportes'] === 1 ? 'reporte' : 'reportes' }}</span>
                            </li>
                        @empty
                            <li class="empty">No hay reportes pendientes de cerrar.</li>
                        @endforelse
                    </ol>
                </div>

                <div class="card">
                    <div class="flex-between mb-4">
                        <h2 style="font-size:1rem;">Moderadores que más venden</h2>
                        <span class="badge">Sin cerrar</span>
                    </div>
                    <ol class="ranking">
                        @forelse ($topModeradores as $item)
                            <li>
                                <span class="rank">{{ $loop->iteration }}</span>
                                <span class="who">{{ $item['nombre'] }}</span>
                                <span class="amount positive">${{ number_format($item['total'], 2) }}</span>
                                <span class="muted small">{{ $item['reportes'] }} {{ $item['reportes'] === 1 ? 'reporte' : 'reportes' }}</span>
                            </li>
                        @empty
                            <li class="empty">No hay reportes pendientes de cerrar.</li>
                        @endforelse
                    </ol>
                </div>
            </div>

            {{-- Últimos Reportes --}}
            <div class="card">
                <div class="flex-between mb-4">
                    <h2 style="font-size:1rem;">Últimos reportes de pago</h2>
                    <div class="actions">
                        <a href="{{ route('reportes.index') }}" class="btn btn-secondary btn-sm">Ver todos</a>
                        <a href="{{ route('reportes.create') }}" class="btn btn-primary btn-sm">+ Nuevo reporte</a>
                    </div>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Modelo</th>
                                <th>Plataforma / Cliente</th>
                                <th>Servicio</th>
                                <th>Precio</th>
                                <th>Fecha</th>
                                <th>Moderador</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($ultimosReportes as $reporte)
                                <tr>
                                    <td><strong>{{ $reporte->modelo?->nombre_completo ?? '-' }}</strong></td>
                                    <td>{{ $reporte->plataforma }} / {{ $reporte->user_cliente }}</td>
                                    <td>{{ Str::limit($reporte->servicio, 30) }}</td>
                                    <td class="positive"><strong>${{ number_format($reporte->precio, 2) }}</strong></td>
                                    <td>{{ $reporte->fecha_reporte?->format('d/m/Y') ?? '-' }}</td>
                                    <td>{{ $reporte->moderador?->nombre_completo ?? '-' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="empty">No hay reportes registrados.</td></tr>
                            @endforelse
                        </tbody>

                    </table>
                </div>
            </div>

            {{-- Últimos Cierres --}}
            <div class="card">
                <div class="flex-between mb-4">
                    <h2 style="font-size:1rem;">Últimos cierres semanales</h2>
                    <div class="actions">
                        <a href="{{ route('cierres.index') }}" class="btn btn-secondary btn-sm">Ver todos</a>
                        <a href="{{ route('cierres.create') }}" class="btn btn-primary btn-sm">+ Nuevo cierre</a>
                    </div>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Período</th>
                                <th>Reportes</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($ultimosCierres as $cierre)
                                <tr>
                                    <td>
                                        <strong>
                                            {{ $cierre->fecha_inicio?->format('d/m/Y') ?? '-' }} — {{ $cierre->fecha_fin?->format('d/m/Y') ?? '-' }}
                                        </strong>
                                    </td>
                                    <td><span class="badge">{{ $cierre->reportes_count }} reportes</span></td>
                                    <td class="positive"><strong>${{ number_format($cierre->total ?? 0, 2) }}</strong></td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="empty">No hay cierres registrados.</td></tr>
                            @endforelse
                        </tbody>

                    </table>
                </div>
            </div>
        </div>
    @endif
@endsection