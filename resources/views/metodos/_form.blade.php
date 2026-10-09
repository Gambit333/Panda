@php
    $metodo ??= null;
    $propietarios ??= collect();
    $duenoActual = old('id_propietario', $metodo?->id_propietario);
@endphp
<form method="POST" action="{{ $metodo ? route('metodos.update', $metodo) : route('metodos.store') }}">
    @csrf
    @if ($metodo) @method('PUT') @endif

    <div class="form-grid">
        <div class="form-group">
            <label>Método de pago *</label>
            <input type="text" name="metodo_pago" value="{{ old('metodo_pago', $metodo?->metodo_pago) }}" placeholder="Ej. Zelle, Binance, PayPal" required>
            @error('metodo_pago') <div class="text-danger">{{ $message }}</div> @enderror
        </div>

        <div class="form-group">
            <label>Propietario / Titular</label>
            <input type="text" name="propietario" value="{{ old('propietario', $metodo?->propietario) }}" placeholder="Ej. Juan Pérez / Cta Principal">
            @error('propietario') <div class="text-danger">{{ $message }}</div> @enderror
        </div>

        <div class="form-group">
            <label>Dueño de la cuenta</label>
            <select name="id_propietario">
                <option value="">— Sin dueño (no aparece en el inicio de nadie) —</option>
                @foreach ($propietarios as $opcion)
                    <option value="{{ $opcion->id_trab }}" @selected((string) $duenoActual === (string) $opcion->id_trab)>
                        {{ $opcion->nombre_completo }}
                    </option>
                @endforeach
            </select>
            <small class="muted">Solo trabajadores con rol <strong>propietario</strong>: al entrar verán en el inicio solo los ingresos de este método.</small>
            @error('id_propietario') <div class="text-danger">{{ $message }}</div> @enderror
        </div>

        <div class="form-group">
            <label>Porcentaje de cuenta (%)</label>
            <input type="number" step="0.01" min="0" max="100" name="porcentaje_cuenta"
                   value="{{ old('porcentaje_cuenta', $metodo?->porcentaje_cuenta) }}" placeholder="0.00">
            @error('porcentaje_cuenta') <div class="text-danger">{{ $message }}</div> @enderror
        </div>
    </div>

    <div class="flex-between mt-4">
        <a href="{{ route('metodos.index') }}" class="btn btn-secondary">Cancelar</a>
        <button type="submit" class="btn btn-primary">{{ $metodo ? 'Actualizar' : 'Guardar' }}</button>
    </div>
</form>