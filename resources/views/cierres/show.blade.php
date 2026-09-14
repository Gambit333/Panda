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
            <div class="label">Total del cierre</div>
            <div class="value positive">${{ number_format($cierre->total ?? 0, 2) }}</div>
        </div>
        <div class="stat">
            <div class="label">Total pagado a empleados</div>
            <div class="value">${{ number_format($cierre->pagosEmpleados->sum('monto'), 2) }}</div>
        </div>
    </div>

    <div class="card mb-4">
        <h2 style="font-size:1rem; margin-bottom:1rem;">Reportes del cierre</h2>
        <table>
            <thead>
                <tr>
                    <th>Modelo</th>
                    <th>Plataforma / Cliente</th>
                    <th>Método</th>
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
                        <td>{{ $reporte->metodoPago?->metodo_pago ?? '-' }}</td>
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
                    <th>Monto</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($cierre->pagosEmpleados as $pago)
                    <tr>
                        <td>{{ $pago->trabajador?->nombre_completo ?? '-' }}</td>
                        <td class="positive">${{ number_format($pago->monto, 2) }}</td>
                        <td>
                            <div class="actions">
                                <a href="{{ route('pagos.edit', $pago) }}" class="btn btn-secondary btn-sm">Editar</a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="empty">Aún no se registran pagos para este cierre.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection