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

    <div class="card mb-4">
        <h2 style="font-size:1rem; margin-bottom:1rem;">Reportes del cierre</h2>
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

    <div class="card">
        <h2 style="font-size:1rem; margin-bottom:1rem;">Pagos a empleados</h2>
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
@endsection