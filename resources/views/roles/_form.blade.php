@php
    $rol ??= null;
    $modulos = App\Support\Permisos::MODULOS;
    // admin y programador siempre tienen acceso a todo: la lista se muestra fija.
    $bloqueado = $rol
        ? $rol->permisosBloqueados()
        : App\Support\Permisos::esBloqueado(old('rol'));
    $seleccionados = $rol
        ? ($rol->permisos ?? array_keys($modulos))
        : (old('permisos') ?? array_keys($modulos));
@endphp
<form method="POST" action="{{ $rol ? route('roles.update', $rol) : route('roles.store') }}">
    @csrf
    @if ($rol) @method('PUT') @endif

    <div class="form-group">
        <label>Nombre del rol *</label>
        <input type="text" name="rol" value="{{ old('rol', $rol?->rol) }}" placeholder="Ej. Moderador, Modelo, Administrador" required
               data-permisos-form>
        @error('rol') <div class="text-danger">{{ $message }}</div> @enderror
    </div>

    <div class="form-group">
        <label>Secciones a las que puede entrar *</label>
        <p class="text-muted small">
            Marca las secciones permitidas. Si no marcas ninguna, el rol solo verá el inicio.
            El rol <strong>admin</strong> y el <strong>programador</strong> siempre tienen acceso a todo
            y aquí sus permisos no se pueden cambiar.
        </p>
        {{-- Permite enviar la lista vacía cuando no hay ningún checkbox marcado. --}}
        <input type="hidden" name="permisos[]" value="">
        <div class="permisos-list">
            @foreach ($modulos as $clave => $etiqueta)
                <label class="permiso-item">
                    <input type="checkbox" name="permisos[]" value="{{ $clave }}"
                           @checked(in_array($clave, $seleccionados, true)) @disabled($bloqueado)>
                    <span>{{ $etiqueta }}</span>
                </label>
            @endforeach
        </div>
        <div class="permisos-aviso {{ $bloqueado ? '' : 'is-hidden' }}" id="permisosAviso">
            Este rol tiene acceso a todas las secciones, no se pueden cambiar sus permisos.
        </div>
        @error('permisos') <div class="text-danger">{{ $message }}</div> @enderror
        @error('permisos.*') <div class="text-danger">{{ $message }}</div> @enderror
    </div>

    <div class="flex-between mt-4">
        <a href="{{ route('roles.index') }}" class="btn btn-secondary">Cancelar</a>
        <button type="submit" class="btn btn-primary">{{ $rol ? 'Actualizar' : 'Guardar' }}</button>
    </div>
</form>

@push('scripts')
    <script>
        // Al escribir admin/programador en el alta, la lista se bloquea visualmente.
        (function () {
            var input = document.querySelector('[data-permisos-form]');
            var aviso = document.getElementById('permisosAviso');
            var checks = Array.prototype.slice.call(document.querySelectorAll('.permisos-list input[type="checkbox"]'));
            var bloqueados = @json(App\Support\Permisos::ROLES_BLOQUEADOS);

            if (!input || !aviso || {{ $rol ? 'true' : 'false' }}) {
                return;
            }

            function aplicar() {
                var bloqueado = bloqueados.indexOf(input.value.trim().toLowerCase()) !== -1;

                aviso.classList.toggle('is-hidden', !bloqueado);
                checks.forEach(function (check) {
                    check.checked = bloqueado ? true : check.checked;
                    check.disabled = bloqueado;
                });
            }

            input.addEventListener('input', aplicar);
            aplicar();
        })();
    </script>
@endpush