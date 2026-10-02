@extends('layouts.app')

@section('title', 'Editar pago a empleado')

@section('content')
    <div class="card" style="max-width: 900px;">
        <h2 style="font-size:1rem; margin-bottom:1.25rem;">Editar pago #{{ $pago->id_pago }}</h2>
        @include('pagos._form')
    </div>

    @if ($pago->abonosAdelanto->isNotEmpty())
        <div class="card mt-4" style="max-width: 900px;">
            <h2 style="font-size:1rem; margin-bottom:1rem;">Abonos de adelantos aplicados por este pago</h2>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Adelanto #</th>
                            <th>Trabajador</th>
                            <th>Monto</th>
                            <th>Fecha</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($pago->abonosAdelanto as $abono)
                            <tr>
                                <td>#{{ $abono->adelanto?->id_adelanto ?? '—' }}</td>
                                <td>{{ $abono->adelanto?->trabajador?->nombre_completo ?? '—' }}</td>
                                <td class="positive">${{ number_format($abono->monto, 2) }}</td>
                                <td>{{ $abono->fecha?->format('d/m/Y') ?? '-' }}</td>
                            </tr>
                        @endforeach
                    </tbody>

                </table>
            </div>
            <p class="muted" style="font-size:.78rem; margin-top:.75rem;">
                Si cambias la deuda o desmarcas la casilla de aplicación, estos abonos se rehacen automáticamente.
            </p>
        </div>
    @endif
@endsection