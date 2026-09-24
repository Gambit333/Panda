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

    <div class="flex-between mt-4">
        <a href="{{ route('trabajadores.index') }}" class="btn btn-secondary">Cancelar</a>
        <button type="submit" class="btn btn-primary">{{ $trabajador ? 'Actualizar' : 'Guardar' }}</button>
    </div>
</form>