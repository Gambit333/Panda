@php
    $pago ??= null;
@endphp
<form method="POST" action="{{ $pago ? route('pagos.update', $pago) : route('pagos.store') }}">
    @csrf
    @if ($pago) @method('PUT') @endif

    <div class="form-grid">
        <div class="form-group">
            <label>Empleado *</label>
            <select name="id_trab" required>
                <option value="">Seleccionar...</option>
                @foreach ($trabajadores as $trab)
                    <option value="{{ $trab->id_trab }}" @selected(old('id_trab', $pago?->id_trab) == $trab->id_trab)>
                        {{ $trab->nombre_completo }}
                    </option>
                @endforeach
            </select>
            @error('id_trab') <div class="text-danger">{{ $message }}</div> @enderror
        </div>

        <div class="form-group">
            <label>Cierre semanal *</label>
            <select name="id_cierre" required>
                <option value="">Seleccionar...</option>
                @foreach ($cierres as $cierre)
                    <option value="{{ $cierre->id_cierre }}" @selected(old('id_cierre', $pago?->id_cierre, request('id_cierre')) == $cierre->id_cierre)>
                        #{{ $cierre->id_cierre }} — {{ $cierre->fecha_inicio->format('d/m/Y') }} / {{ $cierre->fecha_fin->format('d/m/Y') }}
                    </option>
                @endforeach
            </select>
            @error('id_cierre') <div class="text-danger">{{ $message }}</div> @enderror
        </div>

        <div class="form-group">
            <label>Monto ($) *</label>
            <input type="number" step="0.01" min="0" name="monto" value="{{ old('monto', $pago?->monto) }}" required>
            @error('monto') <div class="text-danger">{{ $message }}</div> @enderror
        </div>
    </div>

    <div class="flex-between">
        <a href="{{ route('pagos.index') }}" class="btn btn-secondary">Cancelar</a>
        <button type="submit" class="btn btn-primary">{{ $pago ? 'Actualizar' : 'Guardar' }}</button>
    </div>
</form>