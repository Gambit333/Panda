@extends('layouts.app')

@section('title', 'Trabajadores')

@section('content')
    <div class="flex-between mb-4">
        <div>
            <p class="muted">Empleados registrados en el sistema y sus roles asignados.</p>
            <div class="tabs mt-4">
                <a href="{{ route('trabajadores.index') }}" class="tab {{ $estado === 'activos' ? 'is-active' : '' }}">
                    Activos
                </a>
                <a href="{{ route('trabajadores.index', ['estado' => 'ocultos']) }}" class="tab {{ $estado === 'ocultos' ? 'is-active' : '' }}">
                    Ocultos
                    @if (($totalOcultos ?? 0) > 0)
                        <span class="badge">{{ $totalOcultos }}</span>
                    @endif
                </a>
            </div>
        </div>
        <a href="{{ route('trabajadores.create') }}" class="btn btn-primary">+ Nuevo trabajador</a>
    </div>

    @if ($estado === 'ocultos')
        <div class="flash info mb-4">
            Aquí aparecen los trabajadores que se ocultaron, por ejemplo quienes dejaron la empresa.
            No se eliminan para no perder su historial de reportes y pagos; usa <strong>Mostrar</strong>
            para devolverlos a la lista de activos.
        </div>
    @endif

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
                        <th class="col-id">ID</th>
                        <th>Nombre completo</th>
                        <th>Rol</th>
                        <th>% pago</th>
                        <th>Teléfono</th>
                        <th>Email</th>
                        <th>Cuenta</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($trabajadores as $trabajador)
                        <tr>
                            <td class="col-id">#{{ $trabajador->id_trab }}</td>
                            <td>
                                <strong>{{ $trabajador->nombre }} {{ $trabajador->apellido }}</strong>
                                @if ($trabajador->oculto)
                                    <span class="badge oculto">Oculto</span>
                                @endif
                            </td>
                            <td><span class="badge">{{ $trabajador->rol?->rol ?? 'Sin Rol' }}</span></td>
                            <td>
                                @if ($trabajador->porcentaje !== null)
                                    {{ rtrim(rtrim(number_format((float) $trabajador->porcentaje, 2, '.', ''), '0'), '.') }}%
                                @else
                                    <span class="muted">-</span>
                                @endif
                            </td>
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
                                    @if ($trabajador->oculto)
                                        <form class="inline" method="POST" action="{{ route('trabajadores.mostrar', $trabajador) }}"
                                              onsubmit="return confirm('¿Mostrar de nuevo a este trabajador en la lista principal?');">
                                            @csrf
                                            <button class="btn btn-primary btn-sm">Mostrar</button>
                                        </form>
                                    @else
                                        <form class="inline" method="POST" action="{{ route('trabajadores.ocultar', $trabajador) }}"
                                              onsubmit="return confirm('¿Ocultar a este trabajador? No se borra: pasa a la pestaña «Ocultos» y puedes volver a mostrarlo.');">
                                            @csrf
                                            <button class="btn btn-secondary btn-sm">Ocultar</button>
                                        </form>
                                    @endif
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
                        <tr><td colspan="7" class="empty">{{ $estado === 'ocultos' ? 'No hay trabajadores ocultos.' : 'No hay trabajadores registrados.' }}</td></tr>
                    @endforelse
                </tbody>

            </table>
        </div>
        <div class="pagination">{{ $trabajadores->links() }}</div>
    </div>
@endsection