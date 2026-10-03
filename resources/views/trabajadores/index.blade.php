@extends('layouts.app')

@section('title', 'Trabajadores')

@section('content')
    <div class="flex-between mb-4">
        <p class="muted">Empleados registrados en el sistema y sus roles asignados.</p>
        <a href="{{ route('trabajadores.create') }}" class="btn btn-primary">+ Nuevo trabajador</a>
    </div>

    @if (auth()->user()?->esProgramador())
        <div class="flash info mb-4">
            Como programador puedes quitar la contraseña y desbloquear cuentas: usa el botón
            <strong>Quitar contraseña</strong> de cada fila y el usuario podrá crear una nueva en su próximo ingreso.
            Quien tenga 6 intentos fallidos queda bloqueado 15 minutos.
        </div>
    @endif

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
                        <th>Cuenta</th>
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
                                @if ($trabajador->estaBloqueado())
                                    <span class="badge bloqueado">Bloqueado</span>
                                    <span class="muted small">
                                        {{ $trabajador->intentos_fallidos }} intentos
                                    </span>
                                @elseif (! $trabajador->tienePassword())
                                    <span class="badge">Sin contraseña</span>
                                @else
                                    <span class="badge ok">Activa</span>
                                    @if ((int) $trabajador->intentos_fallidos > 0)
                                        <span class="muted small">
                                            {{ $trabajador->intentos_fallidos }} {{ Str::plural('intento fallido', $trabajador->intentos_fallidos) }}
                                        </span>
                                    @endif
                                @endif
                            </td>
                            <td>
                                <div class="actions">
                                    <a href="{{ route('trabajadores.edit', $trabajador) }}" class="btn btn-secondary btn-sm">Editar</a>
                                    @if (auth()->user()?->esProgramador())
                                        <form class="inline" method="POST" action="{{ route('trabajadores.password.eliminar', $trabajador) }}"
                                              onsubmit="return confirm('¿Eliminar la contraseña de este trabajador? Podrá crear una nueva en su próximo ingreso.');">
                                            @csrf
                                            <button class="btn btn-secondary btn-sm">Quitar contraseña</button>
                                        </form>
                                        @if ($trabajador->estaBloqueado())
                                            <form class="inline" method="POST" action="{{ route('trabajadores.desbloquear', $trabajador) }}"
                                                  onsubmit="return confirm('¿Desbloquear esta cuenta?');">
                                                @csrf
                                                <button class="btn btn-primary btn-sm">Desbloquear</button>
                                            </form>
                                        @endif
                                    @endif
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
        </div>
        <div class="pagination">{{ $trabajadores->links() }}</div>
    </div>
@endsection