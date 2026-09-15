@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    @if ($modoEmpleado)
        <div class="cards">
            <div class="stat">
                <div class="label">Mis ingresos reportados</div>
                <div class="value positive">${{ number_format($stats['reportado'], 2) }}</div>
            </div>
            <div class="stat">
                <div class="label">Reportes hechos</div>
                <div class="value">{{ number_format($stats['reportes']) }}</div>
            </div>
            <div class="stat">
                <div class="label">Monto liquidado</div>
                <div class="value">${{ number_format($stats['liquidado'], 2) }}</div>
            </div>
        </div>

        <div class="card">
            <div class="flex-between mb-4">
                <h2 style="font-size:1rem;">Mis ganancias</h2>
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
                            <td>{{ $reporte->plataforma }} / {{ $reporte->user_cliente }}</td>
                            <td>{{ Str::limit($reporte->servicio, 30) }}</td>
                            <td class="positive">${{ number_format($reporte->precio, 2) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="empty">Aún no tienes reportes registrados.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @else
        <div class="cards">
            <div class="stat">
                <div class="label">Trabajadores</div>
                <div class="value">{{ number_format($stats['trabajadores']) }}</div>
            </div>
            <div class="stat">
                <div class="label">Reportes de pago</div>
                <div class="value">{{ number_format($stats['reportes']) }}</div>
            </div>
            <div class="stat">
                <div class="label">Ingresos totales</div>
                <div class="value positive">${{ number_format($stats['ingresos'], 2) }}</div>
            </div>
            <div class="stat">
                <div class="label">Cierres semanales</div>
                <div class="value">{{ number_format($stats['cierres']) }}</div>
            </div>
            <div class="stat">
                <div class="label">Pagos a empleados</div>
                <div class="value">${{ number_format($stats['pagos_empleados'], 2) }}</div>
            </div>
        </div>

        <div style="display:grid; grid-template-columns: 1fr; gap:1.5rem;">
            <div class="card">
                <div class="flex-between mb-4">
                    <h2 style="font-size:1rem;">Ingresos por método de pago</h2>
                </div>
                @forelse ($ingresosPorMetodo as $item)
                    <div class="mb-4">
                        <div class="flex-between" style="font-size:.85rem;">
                            <span>{{ $item->metodo_pago }}</span>
                            <strong>${{ number_format($item->total, 2) }}</strong>
                        </div>
                        <div style="background:#e2e8f0; border-radius:999px; height:8px; margin-top:.35rem;">
                            @php $max = $ingresosPorMetodo->max('total') ?: 1; @endphp
                            <div style="width: {{ $item->total / $max * 100 }}%; background:#4f46e5; height:8px; border-radius:999px;"></div>
                        </div>
                    </div>
                @empty
                    <p class="empty">Sin ingresos registrados todavía.</p>
                @endforelse
            </div>

            <div class="card">
                <div class="flex-between mb-4">
                    <h2 style="font-size:1rem;">Últimos reportes de pago</h2>
                    <a href="{{ route('reportes.create') }}" class="btn btn-primary btn-sm">+ Nuevo reporte</a>
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
                                <td>{{ $reporte->modelo?->nombre_completo ?? '-' }}</td>
                                <td>{{ $reporte->plataforma }} / {{ $reporte->user_cliente }}</td>
                                <td>{{ Str::limit($reporte->servicio, 30) }}</td>
                                <td>${{ number_format($reporte->precio, 2) }}</td>
                                <td>{{ $reporte->fecha_reporte?->format('d/m/Y') ?? '-' }}</td>
                                <td>{{ $reporte->moderador?->nombre_completo ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="empty">No hay reportes registrados.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="card">
                <div class="flex-between mb-4">
                    <h2 style="font-size:1rem;">Últimos cierres semanales</h2>
                    <a href="{{ route('cierres.create') }}" class="btn btn-primary btn-sm">+ Nuevo cierre</a>
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
                                <td>{{ $cierre->fecha_inicio->format('d/m/Y') }} — {{ $cierre->fecha_fin->format('d/m/Y') }}</td>
                                <td>{{ $cierre->reportes_count }}</td>
                                <td class="positive">${{ number_format($cierre->total ?? 0, 2) }}</td>
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