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
            <select name="id_rol" required>
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
    </div>

    <div class="form-group">
        <label>Dirección de habitación</label>
        <textarea name="direccion" rows="2" placeholder="Ciudad, sector, calle o referencia residencial...">{{ old('direccion', $trabajador?->direccion) }}</textarea>
        @error('direccion') <div class="text-danger">{{ $message }}</div> @enderror
    </div>

    @if (!empty($metodosSinDueno) || !empty($metodosAsignados))
        <div class="form-group" id="metodos-propietario" style="{{ strtolower($trabajador?->rol?->rol ?? '') !== 'propietario' && old('id_rol') != ($trabajador?->rol?->id_rol ?? '') && old('id_rol') !== $roles->firstWhere('rol','propietario')?->id_rol ? 'display:none' : '' }}">
            <label>Métodos de pago asignados (para rol propietario)</label>
            <div class="permisos-list">
                @php
                    $asignadosOld = collect(old('metodos_pago', []))->map(fn($m) => (int)$m)->all();
                    $asignados = $asignadosOld !== [] ? $asignadosOld : ($metodosAsignados->pluck('id_mp')->map(fn($m) => (int)$m)->all() ?? []);
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

    <div class="flex-between mt-4">
        <a href="{{ route('trabajadores.index') }}" class="btn btn-secondary">Cancelar</a>
        <button type="submit" class="btn btn-primary">{{ $trabajador ? 'Actualizar' : 'Guardar' }}</button>
    </div>
</form>