@extends('layouts.app')

@section('title', 'Reportes de pago')

@section('content')
    <div class="flex-between mb-4">
        <p class="muted">Registros de pagos recibidos por plataforma y cliente.</p>
        <a href="{{ route('reportes.create') }}" class="btn btn-primary">+ Nuevo reporte</a>
    </div>

    <div class="card">
        <table>
            <thead>
                <tr>
                    <th>Modelo</th>
                    <th>Plataforma</th>
                    <th>Cliente</th>
                    <th>Método</th>
                    <th>Precio</th>
                    <th>Servicio</th>
                    <th>Fecha</th>
                    <th>Moderador</th>
                    <th>Cierre</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($reportes as $reporte)
                    <tr>
                        <td>{{ $reporte->modelo?->nombre_completo ?? '-' }}</td>
                        <td>{{ $reporte->plataforma }}</td>
                        <td>{{ $reporte->user_cliente }}</td>
                        <td>{{ $reporte->metodoPago?->metodo_pago ?? '-' }}</td>
                        <td class="positive">${{ number_format($reporte->precio, 2) }}</td>
                        <td>{{ Str::limit($reporte->servicio, 25) }}</td>
                        <td>{{ $reporte->fecha_reporte?->format('d/m/Y') ?? '-' }}</td>
                        <td>{{ $reporte->moderador?->nombre_completo ?? '-' }}</td>
                        <td>
                            @if ($reporte->cierreSemanal)
                                <span class="badge">#{{ $reporte->cierreSemanal->id_cierre }}</span>
                            @else
                                <span class="muted">Sin asignar</span>
                            @endif
                        </td>
                        <td>
                            <div class="actions">
                                <a href="{{ route('reportes.edit', $reporte) }}" class="btn btn-secondary btn-sm">Editar</a>
                                <form class="inline" method="POST" action="{{ route('reportes.destroy', $reporte) }}"
                                      onsubmit="return confirm('¿Eliminar este reporte?');">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-danger btn-sm">Eliminar</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="10" class="empty">No hay reportes registrados.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="pagination">{{ $reportes->links() }}</div>
    </div>
@endsection