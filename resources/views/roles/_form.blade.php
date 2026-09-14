@php
    $rol ??= null;
@endphp
<form method="POST" action="{{ $rol ? route('roles.update', $rol) : route('roles.store') }}">
    @csrf
    @if ($rol) @method('PUT') @endif

    <div class="form-group">
        <label>Nombre del rol *</label>
        <input type="text" name="rol" value="{{ old('rol', $rol?->rol) }}" required>
        @error('rol') <div class="text-danger">{{ $message }}</div> @enderror
    </div>

    <div class="flex-between">
        <a href="{{ route('roles.index') }}" class="btn btn-secondary">Cancelar</a>
        <button type="submit" class="btn btn-primary">{{ $rol ? 'Actualizar' : 'Guardar' }}</button>
    </div>
</form>