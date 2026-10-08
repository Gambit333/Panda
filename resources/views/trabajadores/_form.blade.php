@php
    $trabajador ??= null;
@endphp
<form method="POST" action="{{ $trabajador ? route('trabajadores.update', $trabajador) : route('trabajadores.store') }}">
    @csrf
    @if ($trabajador) @method('PUT') @endif

    <div class="form-grid">
        <div class="form-group">
            <label>Nombre *</label>
            <input type="text" name="nombre" value="{{ old('nombre', $trabajador?->nombre) }}" placeholder="Ej. Ana" required>
            @error('nombre') <div class="text-danger">{{ $message }}</div> @enderror
        </div>

        <div class="form-group">
            <label>Apellido *</label>
            <input type="text" name="apellido" value="{{ old('apellido', $trabajador?->apellido) }}" placeholder="Ej. Martínez" required>
            @error('apellido') <div class="text-danger">{{ $message }}</div> @enderror
        </div>

        <div class="form-group">
            <label>Rol en la empresa *</label>
            <select name="id_rol" id="rolSelect" required>
                <option value="">Seleccionar...</option>
                @foreach ($roles as $rol)
                    <option value="{{ $rol->id_rol }}" @selected(old('id_rol', $trabajador?->id_rol) == $rol->id_rol)>
                        {{ $rol->rol }}
                    </option>
                @endforeach
            </select>
            @error('id_rol') <div class="text-danger">{{ $message }}</div> @enderror
        </div>

        <div class="form-group">
            <label>Teléfono</label>
            <input type="text" name="telefono" value="{{ old('+58...', $trabajador?->telefono) }}" placeholder="+58 412 1234567">
            @error('telefono') <div class="text-danger">{{ $message }}</div> @enderror
        </div>

        <div class="form-group">
            <label>Correo Electrónico</label>
            <input type="email" name="email" value="{{ old('email', $trabajador?->email) }}" placeholder="ejemplo@correo.com">
            @error('email') <div class="text-danger">{{ $message }}</div> @enderror
        </div>

        <div class="form-group">
            <label>Porcentaje de pago (%)</label>
            <input type="number" step="0.01" min="0" max="100" name="porcentaje"
                   value="{{ old('porcentaje', $trabajador?->porcentaje) }}" placeholder="Según el rol">
            <small class="muted">
                Vacío = valor por defecto: 50% como modelo, 20% como moderador y,
                para la sección administrativa, 20% al rol ceo / 1.5% a cada admin /
                1.5% a cada support / 1.5% a cada programador. La sección administrativa
                se calcula sobre el total sin impuestos del cierre, no de lo que sobra
                de las modelos.
            </small>
            @error('porcentaje') <div class="text-danger">{{ $message }}</div> @enderror
        </div>
    </div>

    <div class="form-group">
        <label>Dirección de habitación</label>
        <textarea name="direccion" rows="2" placeholder="Ciudad, sector, calle o referencia residencial...">{{ old('direccion', $trabajador?->direccion) }}</textarea>
        @error('direccion') <div class="text-danger">{{ $message }}</div> @enderror
    </div>

    @php
        $rolTrabajador = strtolower($trabajador?->rol?->rol ?? '');
        $idRolPropietario = $roles->firstWhere('rol', 'propietario')?->id_rol;
        $idRolModerador = $roles->firstWhere('rol', 'moderador')?->id_rol;
        $mostrarMetodosPropietario = ($rolTrabajador === 'propietario') || ((string)old('id_rol') === (string)$idRolPropietario);
        $mostrarModelosModerador = ($rolTrabajador === 'moderador') || ((string)old('id_rol') === (string)$idRolModerador);
    @endphp

    @if (!empty($metodosSinDueno) || !empty($metodosAsignados ?? collect()))
        <div class="form-group" id="metodos-propietario" style="{{ $mostrarMetodosPropietario ? '' : 'display:none' }}">
            <label>Métodos de pago asignados (para rol propietario)</label>
            <div class="permisos-list">
                @php
                    $asignadosOld = collect(old('metodos_pago', []))->map(fn($m) => (int)$m)->all();
                    $asignados = $asignadosOld !== [] ? $asignadosOld : (($metodosAsignados ?? collect())->pluck('id_mp')->map(fn($m) => (int)$m)->all() ?? []);
                @endphp
                @foreach (($metodosAsignados ?? collect())->merge($metodosSinDueno ?? collect())->sortBy('metodo_pago') as $mp)
                    <label class="permiso-item">
                        <input type="checkbox" name="metodos_pago[]" value="{{ $mp->id_mp }}" @checked(in_array((int)$mp->id_mp, $asignados))>
                        <span>{{ $mp->metodo_pago }} @if($mp->propietario) <small class="muted">({{ $mp->propietario }})</small>@endif</span>
                    </label>
                @endforeach
            </div>
            <small class="muted">Solo se aplican cuando el rol seleccionado es <strong>propietario</strong>. Al cambiar de rol a otro, estas asignaciones se quitan automáticamente.</small>
            @error('metodos_pago') <div class="text-danger">{{ $message }}</div> @enderror
        </div>
    @endif

    @if (!empty($modelos))
        @php
            $pivotPorcentajes = ($modelosAsignados ?? collect())
                ->mapWithKeys(fn ($m) => [(int) $m->id_trab => $m->pivot?->porcentaje])
                ->all();
        @endphp
        <div class="form-group" id="modelos-moderador" style="{{ $mostrarModelosModerador ? '' : 'display:none' }}">
            <label>Modelos asignados (para rol moderador)</label>
            <div class="permisos-list">
                @php
                    $asignadosModelosOld = collect(old('modelos_moderador', []))->map(fn($m) => (int)$m)->all();
                    $asignadosModelos = $asignadosModelosOld !== [] ? $asignadosModelosOld : (($modelosAsignados ?? collect())->pluck('id_trab')->map(fn($m) => (int)$m)->all() ?? []);
                @endphp
                @foreach ($modelos as $mod)
                    @php
                        $pctModelo = old('porcentajes_modelo.'.$mod->id_trab, $pivotPorcentajes[$mod->id_trab] ?? null);
                    @endphp
                    <div class="permiso-item" style="justify-content:space-between">
                        <label style="display:flex;align-items:center;gap:.5rem;margin:0;flex:1;cursor:pointer;">
                            <input type="checkbox" name="modelos_moderador[]" value="{{ $mod->id_trab }}"
                                   class="modelo-check" @checked(in_array((int)$mod->id_trab, $asignadosModelos))>
                            <span>{{ $mod->nombre_completo }}</span>
                        </label>
                        <label style="display:flex;align-items:center;gap:.35rem;margin:0;white-space:nowrap;">
                            <input type="number" step="0.01" min="0" max="100"
                                   name="porcentajes_modelo[{{ $mod->id_trab }}]"
                                   value="{{ $pctModelo }}" placeholder="20"
                                   style="width:4.2rem;padding:.25rem .35rem;font-size:.85rem;"
                                   class="modelo-pct">
                            <small class="muted">%</small>
                        </label>
                    </div>
                @endforeach
            </div>
            <small class="muted">Solo se aplican cuando el rol seleccionado es <strong>moderador</strong>. El % por modelo es
                lo que gana el moderador con esa modelo: vacío = su "Porcentaje de pago" o 20% por defecto.</small>
            @error('modelos_moderador') <div class="text-danger">{{ $message }}</div> @enderror
        </div>
    @endif

    <div class="flex-between mt-4">
        <a href="{{ route('trabajadores.index') }}" class="btn btn-secondary">Cancelar</a>
        <button type="submit" class="btn btn-primary">{{ $trabajador ? 'Actualizar' : 'Guardar' }}</button>
    </div>
</form>

<script>
    const rolSelect = document.getElementById('rolSelect');
    const metodosBlock = document.getElementById('metodos-propietario');
    const modelosBlock = document.getElementById('modelos-moderador');

    function toggleBlocks() {
        if (!rolSelect) return;
        const rol = rolSelect.options[rolSelect.selectedIndex]?.text.toLowerCase() || '';
        if (metodosBlock) {
            metodosBlock.style.display = rol === 'propietario' ? '' : 'none';
        }
        if (modelosBlock) {
            modelosBlock.style.display = rol === 'moderador' ? '' : 'none';
        }
    }

    if (rolSelect) {
        rolSelect.addEventListener('change', toggleBlocks);
        toggleBlocks();
    }

    // El % por modelo solo aplica si su modelo está marcada.
    document.querySelectorAll('.modelo-check').forEach(function (cb) {
        const pct = cb.closest('.permiso-item').querySelector('.modelo-pct');
        pct.disabled = !cb.checked;
        cb.addEventListener('change', function () { pct.disabled = !cb.checked; });
    });
</script>