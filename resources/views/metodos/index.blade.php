@extends('layouts.app')

@section('title', 'Métodos de pago')

@section('content')
    <div class="flex-between mb-4">
        <p class="muted">Plataformas usadas para recibir pagos y sus porcentajes.</p>
        <a href="{{ route('metodos.create') }}" class="btn btn-primary">+ Nuevo método</a>
    </div>

    <div class="card">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Método de pago</th>
                    <th>Impuesto</th>
                    <th>% Cuenta</th>
                    <th>Reportes</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($metodos as $metodo)
                    <tr>
                        <td>#{{ $metodo->id_mp }}</td>
                        <td>{{ $metodo->metodo_pago }}</td>
                        <td>{{ $metodo->impuesto !== null ? '$'.number_format($metodo->impuesto, 2) : '-' }}</td>
                        <td>{{ $metodo->porcentaje_cuenta !== null ? $metodo->porcentaje_cuenta.'%' : '-' }}</td>
                        <td>{{ $metodo->reportes_count }}</td>
                        <td>
                            <div class="actions">
                                <a href="{{ route('metodos.edit', $metodo) }}" class="btn btn-secondary btn-sm">Editar</a>
                                <form class="inline" method="POST" action="{{ route('metodos.destroy', $metodo) }}"
                                      onsubmit="return confirm('¿Eliminar este método de pago?');">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-danger btn-sm">Eliminar</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="empty">No hay métodos de pago registrados.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection