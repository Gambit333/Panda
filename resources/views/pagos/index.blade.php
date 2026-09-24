@extends('layouts.app')

@section('title', 'Pagos a empleados')

@section('content')
    <div class="flex-between mb-4">
        <p class="muted">Pagos registrados a los trabajadores por cada cierre semanal.</p>
        <a href="{{ route('pagos.create') }}" class="btn btn-primary">+ Nuevo pago</a>
    </div>

    <div class="card">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Empleado</th>
                    <th>Cierre</th>
                    <th>Período</th>
                    <th>Monto Bruto</th>
                    <th>Monto Neto</th>
                    <th>Deuda</th>
                    <th>Monto Final</th>
                    <th>Nota</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($pagos as $pago)
                    <tr>
                        <td>#{{ $pago->id_pago }}</td>
                        <td><strong>{{ $pago->trabajador?->nombre_completo ?? '-' }}</strong></td>
                        <td>#{{ $pago->cierreSemanal?->id_cierre ?? '-' }}</td>
                        <td>
                            @if ($pago->cierreSemanal)
                                {{ $pago->cierreSemanal->fecha_inicio->format('d/m/Y') }} — {{ $pago->cierreSemanal->fecha_fin->format('d/m/Y') }}
                            @else
                                -
                            @endif
                        </td>
                        <td>${{ number_format($pago->monto_bruto, 2) }}</td>
                        <td>${{ number_format($pago->monto_neto, 2) }}</td>
                        <td class="text-danger">-${{ number_format($pago->deuda, 2) }}</td>
                        <td class="positive" style="font-weight: bold;">${{ number_format($pago->monto_final, 2) }}</td>
                        <td><small class="muted">{{ Str::limit($pago->nota, 20) ?: '-' }}</small></td>
                        <td>
                            <div class="actions">
                                <a href="{{ route('pagos.edit', $pago) }}" class="btn btn-secondary btn-sm">Editar</a>
                                <form class="inline" method="POST" action="{{ route('pagos.destroy', $pago) }}"
                                      onsubmit="return confirm('¿Eliminar este pago?');">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-danger btn-sm">Eliminar</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="10" class="empty">No hay pagos registrados.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="pagination">{{ $pagos->links() }}</div>
    </div>
@endsection