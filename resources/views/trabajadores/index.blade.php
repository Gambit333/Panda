@extends('layouts.app')

@section('title', 'Trabajadores')

@section('content')
    <div class="flex-between mb-4">
        <p class="muted">Empleados registrados en el sistema y sus roles asignados.</p>
        <a href="{{ route('trabajadores.create') }}" class="btn btn-primary">+ Nuevo trabajador</a>
    </div>

    <div class="card">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre completo</th>
                        <th>Rol</th>
                        <th>Teléfono</th>
                        <th>Email</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($trabajadores as $trabajador)
                        <tr>
                            <td>#{{ $trabajador->id_trab }}</td>
                            <td><strong>{{ $trabajador->nombre }} {{ $trabajador->apellido }}</strong></td>
                            <td><span class="badge">{{ $trabajador->rol?->rol ?? 'Sin Rol' }}</span></td>
                            <td>
                                @if ($trabajador->telefono)
                                    <a href="tel:{{ $trabajador->telefono }}">{{ $trabajador->telefono }}</a>
                                @else
                                    <span class="muted">-</span>
                                @endif
                            </td>
                            <td>
                                @if ($trabajador->email)
                                    <a href="mailto:{{ $trabajador->email }}">{{ $trabajador->email }}</a>
                                @else
                                    <span class="muted">-</span>
                                @endif
                            </td>
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
                        <tr><td colspan="6" class="empty">No hay trabajadores registrados.</td></tr>
                    @endforelse
                </tbody>

            </table>
        </div>
        <div class="pagination">{{ $trabajadores->links() }}</div>
    </div>
@endsection