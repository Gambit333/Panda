@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    @if ($modoEmpleado)
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

        <div class="card">
            <div class="flex-between mb-4">
                <h2 style="font-size:1rem;">Mis ganancias recientes</h2>
                @if ($modoEmpleado === 'moderador')
                    <a href="{{ route('reportes.create') }}" class="btn btn-primary btn-sm">+ Nuevo reporte</a>
                @endif
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Plataforma / Cliente</th>
                        <th>Servicio</th>
                        <th>Precio</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($reportes as $reporte)
                        <tr>
                            <td>{{ $reporte->fecha_reporte?->format('d/m/Y') ?? '-' }}</td>
                            <td>
                                <strong>{{ $reporte->plataforma }}</strong>
                                <span class="muted">/ {{ $reporte->user_cliente }}</span>
                            </td>
                            <td>{{ Str::limit($reporte->servicio, 35) }}</td>
                            <td class="positive"><strong>${{ number_format($reporte->precio, 2) }}</strong></td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="empty">Aún no tienes reportes registrados.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @else
        {{-- VISTA MODO ADMINISTRADOR --}}
        <div class="cards">
            <div class="stat">
                <div class="label">Trabajadores</div>
                <div class="value">{{ number_format($stats['trabajadores'] ?? 0) }}</div>
            </div>
            <div class="stat">
                <div class="label">Reportes de pago</div>
                <div class="value">{{ number_format($stats['reportes'] ?? 0) }}</div>
            </div>
            <div class="stat">
                <div class="label">Ingresos totales</div>
                <div class="value positive">${{ number_format($stats['ingresos'] ?? 0, 2) }}</div>
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

        <div style="display:grid; grid-template-columns: 1fr; gap:1.5rem;">
            {{-- Ingresos por Método de Pago --}}
            <div class="card">
                <div class="flex-between mb-4">
                    <h2 style="font-size:1rem;">Ingresos por método de pago</h2>
                </div>
                @forelse ($ingresosPorMetodo as $item)
                    <div class="mb-4">
                        <div class="flex-between" style="font-size:.85rem;">
                            <span><strong>{{ $item->metodo_pago }}</strong></span>
                            <strong>${{ number_format($item->total, 2) }}</strong>
                        </div>
                        <div style="background: var(--border); border-radius:999px; height:8px; margin-top:.35rem; overflow:hidden;">
                            @php $max = $ingresosPorMetodo->max('total') ?: 1; @endphp
                            <div style="width: {{ ($item->total / $max) * 100 }}%; background: var(--primary); height:8px; border-radius:999px;"></div>
                        </div>
                    </div>
                @empty
                    <p class="empty">Sin ingresos registrados todavía.</p>
                @endforelse
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

            {{-- Últimos Cierres --}}
            <div class="card">
                <div class="flex-between mb-4">
                    <h2 style="font-size:1rem;">Últimos cierres semanales</h2>
                    <div class="actions">
                        <a href="{{ route('cierres.index') }}" class="btn btn-secondary btn-sm">Ver todos</a>
                        <a href="{{ route('cierres.create') }}" class="btn btn-primary btn-sm">+ Nuevo cierre</a>
                    </div>
                </div>
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
    @endif
@endsection