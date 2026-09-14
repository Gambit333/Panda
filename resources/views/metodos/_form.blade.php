@php
    $metodo ??= null;
@endphp
<form method="POST" action="{{ $metodo ? route('metodos.update', $metodo) : route('metodos.store') }}">
    @csrf
    @if ($metodo) @method('PUT') @endif

    <div class="form-grid">
        <div class="form-group">
            <label>Método de pago *</label>
            <input type="text" name="metodo_pago" value="{{ old('metodo_pago', $metodo?->metodo_pago) }}" required>
            @error('metodo_pago') <div class="text-danger">{{ $message }}</div> @enderror
        </div>

        <div class="form-group">
            <label>Impuesto ($)</label>
            <input type="number" step="0.01" min="0" name="impuesto" value="{{ old('impuesto', $metodo?->impuesto) }}">
            @error('impuesto') <div class="text-danger">{{ $message }}</div> @enderror
        </div>

        <div class="form-group">
            <label>Porcentaje de cuenta (%)</label>
            <input type="number" step="0.01" min="0" max="100" name="porcentaje_cuenta"
                   value="{{ old('porcentaje_cuenta', $metodo?->porcentaje_cuenta) }}">
            @error('porcentaje_cuenta') <div class="text-danger">{{ $message }}</div> @enderror
        </div>
    </div>

    <div class="flex-between">
        <a href="{{ route('metodos.index') }}" class="btn btn-secondary">Cancelar</a>
        <button type="submit" class="btn btn-primary">{{ $metodo ? 'Actualizar' : 'Guardar' }}</button>
    </div>
</form>