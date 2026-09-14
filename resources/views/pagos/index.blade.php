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
                    <th>Monto</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($pagos as $pago)
                    <tr>
                        <td>#{{ $pago->id_pago }}</td>
                        <td>{{ $pago->trabajador?->nombre_completo ?? '-' }}</td>
                        <td>#{{ $pago->cierreSemanal?->id_cierre ?? '-' }}</td>
                        <td>
                            @if ($pago->cierreSemanal)
                                {{ $pago->cierreSemanal->fecha_inicio->format('d/m/Y') }} — {{ $pago->cierreSemanal->fecha_fin->format('d/m/Y') }}
                            @else
                                -
                            @endif
                        </td>
                        <td class="positive">${{ number_format($pago->monto, 2) }}</td>
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
                    <tr><td colspan="6" class="empty">No hay pagos registrados.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="pagination">{{ $pagos->links() }}</div>
    </div>
@endsection