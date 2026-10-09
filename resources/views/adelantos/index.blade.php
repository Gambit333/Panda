@extends('layouts.app')

@section('title', 'Adelantos y préstamos')

@section('content')
    <div class="flex-between mb-4">
        <p class="muted">Adelantos y préstamos entregados a los trabajadores, con el saldo pendiente de cada uno.</p>
        <a href="{{ route('adelantos.create') }}" class="btn btn-primary">+ Nuevo adelanto / préstamo</a>
    </div>

    <div class="cards">
        <div class="stat">
            <div class="label">Total entregado</div>
            <div class="value">${{ number_format($total, 2) }}</div>
        </div>
        <div class="stat">
            <div class="label">Total abonado</div>
            <div class="value positive">${{ number_format($pagado, 2) }}</div>
        </div>
        <div class="stat">
            <div class="label">Saldo pendiente</div>
            <div class="value" style="color: var(--danger);">${{ number_format($total - $pagado, 2) }}</div>
        </div>
    </div>

    <div class="card mb-4">
        <h2 style="font-size:1rem; margin-bottom:1rem;">Cuánto debe cada trabajador</h2>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Trabajador</th>
                        <th>Registros</th>
                        <th>Total entregado</th>
                        <th>Abonado</th>
                        <th>Saldo pendiente</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($deudas as $fila)
                        <tr>
                            <td><strong>{{ $fila['trabajador']?->nombre_completo ?? 'Trabajador' }}</strong></td>
                            <td>{{ $fila['cantidad'] }}</td>
                            <td>${{ number_format($fila['total'], 2) }}</td>
                            <td class="positive">${{ number_format($fila['pagado'], 2) }}</td>
                            <td style="font-weight: bold; {{ $fila['saldo'] > 0 ? 'color: var(--danger);' : '' }}">
                                {{ $fila['saldo'] > 0 ? '-' : '' }}${{ number_format(abs($fila['saldo']), 2) }}
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="empty">No hay adelantos ni préstamos registrados.</td></tr>
                    @endforelse
                </tbody>

            </table>
        </div>
    </div>

    <div class="card">
        <div class="flex-between mb-4">
            <h2 style="font-size:1rem;">Adelantos y préstamos</h2>
            <span class="muted" style="font-size:.8rem;">Usa “Abonar” para descontar lo pagado por el trabajador.</span>
        </div>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th class="col-id">ID</th>
                        <th>Trabajador</th>
                        <th>Tipo</th>
                        <th>Monto</th>
                        <th>Fecha</th>
                        <th>Abonado</th>
                        <th>Saldo</th>
                        <th>Abonar</th>
                        <th>Nota</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($adelantos as $adelanto)
                        <tr>
                            <td class="col-id">#{{ $adelanto->id_adelanto }}</td>
                            <td><strong>{{ $adelanto->trabajador?->nombre_completo ?? '-' }}</strong></td>
                            <td><span class="badge">{{ $adelanto->tipo_label }}</span></td>
                            <td>${{ number_format($adelanto->monto, 2) }}</td>
                            <td>{{ $adelanto->fecha?->format('d/m/Y') ?? '-' }}</td>
                            <td class="positive">${{ number_format($adelanto->pagado, 2) }}</td>
                            <td style="font-weight: bold; {{ $adelanto->saldado ? '' : 'color: var(--danger);' }}">
                                @if ($adelanto->saldado)
                                    <span class="badge">Saldado</span>
                                @else
                                    -${{ number_format($adelanto->saldo, 2) }}
                                @endif
                            </td>
                            <td>
                                @if ($adelanto->saldado)
                                    <span class="muted">—</span>
                                @else
                                    <form class="inline" method="POST" action="{{ route('adelantos.abonos.store', $adelanto) }}">
                                        @csrf
                                        <input type="hidden" name="id_adelanto" value="{{ $adelanto->id_adelanto }}">
                                        <input type="hidden" name="fecha" value="{{ old('fecha', date('Y-m-d')) }}">
                                        <input type="number" step="0.01" min="0.01" max="{{ $adelanto->saldo }}" name="monto"
                                               class="input-sm" placeholder="0.00"
                                               value="{{ (string) old('id_adelanto') === (string) $adelanto->id_adelanto ? old('monto') : '' }}">
                                        <button class="btn btn-secondary btn-sm">Abonar</button>
                                    </form>
                                    @error('monto')
                                        @if ((string) old('id_adelanto') === (string) $adelanto->id_adelanto)
                                            <div class="text-danger">{{ $message }}</div>
                                        @endif
                                    @enderror
                                @endif
                            </td>
                            <td><small class="muted">{{ Str::limit($adelanto->nota, 20) ?: '-' }}</small></td>
                            <td>
                                <div class="actions">
                                    <a href="{{ route('adelantos.edit', $adelanto) }}" class="btn btn-secondary btn-sm">Editar</a>
                                    <form class="inline" method="POST" action="{{ route('adelantos.destroy', $adelanto) }}"
                                          onsubmit="return confirm('¿Eliminar este adelanto/préstamo y sus abonos?');">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-danger btn-sm">Eliminar</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="empty">No hay adelantos ni préstamos registrados.</td></tr>
                    @endforelse
                </tbody>

            </table>
        </div>
        <div class="pagination">{{ $adelantos->links() }}</div>
    </div>
@endsection