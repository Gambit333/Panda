@extends('layouts.app')

@section('title', 'Roles')

@section('content')
    <div class="flex-between mb-4">
        <p class="muted">Roles asignables a los trabajadores (Moderador, Modelo, etc.).</p>
        <a href="{{ route('roles.create') }}" class="btn btn-primary">+ Nuevo rol</a>
    </div>

    <div class="card">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Rol</th>
                    <th>Trabajadores</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($roles as $rol)
                    <tr>
                        <td>#{{ $rol->id_rol }}</td>
                        <td>{{ $rol->rol }}</td>
                        <td>{{ $rol->trabajadores_count }}</td>
                        <td>
                            <div class="actions">
                                <a href="{{ route('roles.edit', $rol) }}" class="btn btn-secondary btn-sm">Editar</a>
                                <form class="inline" method="POST" action="{{ route('roles.destroy', $rol) }}"
                                      onsubmit="return confirm('¿Eliminar este rol?');">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-danger btn-sm">Eliminar</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="empty">No hay roles registrados.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection