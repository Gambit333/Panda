@extends('layouts.app')

@section('title', 'Trabajadores')

@section('content')
    <div class="flex-between mb-4">
        <p class="muted">Empleados del sistema, con su rol asignado.</p>
        <a href="{{ route('trabajadores.create') }}" class="btn btn-primary">+ Nuevo trabajador</a>
    </div>

    <div class="card">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Apellido</th>
                    <th>Teléfono</th>
                    <th>Email</th>
                    <th>Rol</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($trabajadores as $trabajador)
                    <tr>
                        <td>#{{ $trabajador->id_trab }}</td>
                        <td>{{ $trabajador->nombre }}</td>
                        <td>{{ $trabajador->apellido }}</td>
                        <td>{{ $trabajador->telefono ?? '-' }}</td>
                        <td>{{ $trabajador->email ?? '-' }}</td>
                        <td><span class="badge">{{ $trabajador->rol?->rol ?? '-' }}</span></td>
                        <td>
                            <div class="actions">
                                <a href="{{ route('trabajadores.edit', $trabajador) }}" class="btn btn-secondary btn-sm">Editar</a>
                                <form class="inline" method="POST" action="{{ route('trabajadores.destroy', $trabajador) }}"
                                      onsubmit="return confirm('¿Eliminar este trabajador?');">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-danger btn-sm">Eliminar</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="empty">No hay trabajadores registrados.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="pagination">{{ $trabajadores->links() }}</div>
    </div>
@endsection