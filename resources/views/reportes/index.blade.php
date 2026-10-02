@extends('layouts.app')

@section('title', 'Reportes de pago')

@section('content')
    <div class="flex-between mb-4">
        <p class="muted">
            {{ $soloMios
                ? 'Aquí ves únicamente los reportes de pago que tú registraste.'
                : 'Registros de pagos recibidos por plataforma, cliente y moderador asignado.' }}
        </p>
        <a href="{{ route('reportes.create') }}" class="btn btn-primary">+ Nuevo reporte</a>
    </div>

    <div class="card">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Modelo</th>
                        <th>Plataforma</th>
                        <th>Cliente</th>
                        <th>Método / Titular</th>
                        <th>Precio</th>
                        <th>Servicio</th>
                        <th>Fecha</th>
                        @unless ($soloMios)
                            <th>Moderador</th>
                        @endunless
                        <th>Cierre</th>
                        <th>Comp.</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($reportes as $reporte)
                        <tr>
                            <td><strong>{{ $reporte->modelo?->nombre_completo ?? '-' }}</strong></td>
                            <td>{{ $reporte->plataforma }}</td>
                            <td><code>{{ $reporte->user_cliente }}</code></td>
                            <td>
                                @if ($reporte->metodoPago)
                                    {{ $reporte->metodoPago->metodo_pago }}
                                    @if ($reporte->metodoPago->propietario)
                                        <br><small class="muted">({{ $reporte->metodoPago->propietario }})</small>
                                    @endif
                                @else
                                    -
                                @endif
                            </td>
                            <td class="positive" style="font-weight: bold;">${{ number_format($reporte->precio, 2) }}</td>
                            <td>
                                {{ Str::limit($reporte->servicio, 25) }}
                                @if ($reporte->duracion)
                                    <br><small class="muted">{{ $reporte->duracion }}</small>
                                @endif
                            </td>
                            <td>{{ $reporte->fecha_reporte?->format('d/m/Y') ?? '-' }}</td>
                            @unless ($soloMios)
                                <td>{{ $reporte->moderador?->nombre_completo ?? '-' }}</td>
                            @endunless
                            <td>
                                @if ($reporte->cierreSemanal)
                                    <span class="badge">#{{ $reporte->cierreSemanal->id_cierre }}</span>
                                @else
                                    <span class="muted">Sin asignar</span>
                                @endif
                            </td>
                            <td>
                                @if ($reporte->comprobante)
                                    <a href="{{ $reporte->comprobante_url }}" target="_blank" title="Ver comprobante">
                                        <img src="{{ $reporte->comprobante_url }}" alt="Comprobante" style="width:40px;height:40px;object-fit:cover;border-radius:6px;">
                                    </a>
                                @else
                                    <span class="muted">-</span>
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
                        <tr><td colspan="{{ $soloMios ? 10 : 11 }}" class="empty">No hay reportes registrados.</td></tr>
                    @endforelse
                </tbody>

            </table>
        </div>
        <div class="pagination">{{ $reportes->links() }}</div>
    </div>
@endsection