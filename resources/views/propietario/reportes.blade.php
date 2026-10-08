@extends('layouts.app')

@section('title', 'Mis reportes')

@section('content')
    <div class="flex-between mb-4">
        <p class="muted">
            Los pagos recibidos en tus métodos de pago, separados por cuenta.
            Aquí solo se muestra la fecha, el precio y el comprobante de cada pago.
        </p>
    </div>

    @forelse ($bloques as $bloque)
        <div class="card mb-4">
            <div class="flex-between mb-4">
                <h2 style="font-size:1rem;">
                    {{ $bloque['metodo']->metodo_pago }}
                    @if ($bloque['metodo']->propietario)
                        <small class="muted">({{ $bloque['metodo']->propietario }})</small>
                    @endif
                </h2>
                <span class="badge">${{ number_format($bloque['total'], 2) }}</span>
            </div>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Precio</th>
                            <th>Comprobante</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($bloque['reportes'] as $reporte)
                            <tr>
                                <td>{{ $reporte->fecha_reporte?->format('d/m/Y') ?? '-' }}</td>
                                <td class="positive" style="font-weight:bold;">${{ number_format($reporte->precio, 2) }}</td>
                                <td>
                                    @if ($reporte->comprobante_url)
                                        <a href="{{ $reporte->comprobante_url }}" target="_blank" title="Ver comprobante">
                                            <img src="{{ $reporte->comprobante_url }}" alt="Comprobante" style="width:40px;height:40px;object-fit:cover;border-radius:6px;">
                                        </a>
                                    @else
                                        <span class="muted">-</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="empty">Sin reportes registrados en este método.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @empty
        <div class="card">
            <p class="empty">No tienes métodos de pago asignados. Contacta a un administrador para que te asigne tus métodos.</p>
        </div>
    @endforelse
@endsection