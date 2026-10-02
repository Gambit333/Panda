@extends('layouts.app')

@section('title', 'Editar adelanto o préstamo')

@section('content')
    <div class="card" style="max-width: 900px;">
        <div class="flex-between mb-4">
            <h2 style="font-size:1rem;">
                Adelanto #{{ $adelanto->id_adelanto }} — {{ $adelanto->trabajador?->nombre_completo ?? 'trabajador eliminado' }}
            </h2>
            <span class="muted" style="font-size:.8rem;">
                Saldo pendiente: <strong style="color: {{ $adelanto->saldado ? 'var(--success)' : 'var(--danger)' }};">
                    ${{ number_format(abs($adelanto->saldo), 2) }}
                </strong>
            </span>
        </div>

        @include('adelantos._form')
    </div>

    <div class="card mt-4" style="max-width: 900px;">
        <h2 style="font-size:1rem; margin-bottom:1rem;">Abonos registrados</h2>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Monto</th>
                        <th>Nota</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($adelanto->abonos as $abono)
                        <tr>
                            <td>{{ $abono->fecha?->format('d/m/Y') ?? '-' }}</td>
                            <td class="positive">${{ number_format($abono->monto, 2) }}</td>
                            <td><small class="muted">{{ $abono->nota ?: '-' }}</small></td>
                            <td>
                                <form class="inline" method="POST" action="{{ route('adelantos.abonos.destroy', $abono) }}"
                                      onsubmit="return confirm('¿Eliminar este abono?');">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-danger btn-sm">Eliminar</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="empty">Este adelanto todavía no tiene abonos.</td></tr>
                    @endforelse
                </tbody>

            </table>
        </div>
    </div>
@endsection