@extends('layouts.app')

@section('title', 'Cierres semanales')

@section('content')
    <div class="flex-between mb-4">
        <p class="muted">Cierres generados por semana. Un cierre agrupa los reportes de pago del período.</p>
        <a href="{{ route('cierres.create') }}" class="btn btn-primary">+ Nuevo cierre</a>
    </div>

    <div class="card">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Fecha inicio</th>
                    <th>Fecha fin</th>
                    <th>Reportes</th>
                    <th>Pagos empleados</th>
                    <th>Total</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($cierres as $cierre)
                    <tr>
                        <td>#{{ $cierre->id_cierre }}</td>
                        <td>{{ $cierre->fecha_inicio->format('d/m/Y') }}</td>
                        <td>{{ $cierre->fecha_fin->format('d/m/Y') }}</td>
                        <td>{{ $cierre->reportes_count }}</td>
                        <td>{{ $cierre->pagos_empleados_count }}</td>
                        <td class="positive">${{ number_format($cierre->total ?? 0, 2) }}</td>
                        <td>
                            <div class="actions">
                                <a href="{{ route('cierres.show', $cierre) }}" class="btn btn-secondary btn-sm">Ver</a>
                                <form class="inline" method="POST" action="{{ route('cierres.destroy', $cierre) }}"
                                      onsubmit="return confirm('¿Eliminar este cierre? Los reportes quedarán sin asignar.');">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-danger btn-sm">Eliminar</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="empty">No hay cierres registrados.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="pagination">{{ $cierres->links() }}</div>
    </div>
@endsection