@php
    $adelanto ??= null;
@endphp
<form method="POST" action="{{ $adelanto ? route('adelantos.update', $adelanto) : route('adelantos.store') }}">
    @csrf
    @if ($adelanto) @method('PUT') @endif

    <div class="form-grid">
        <div class="form-group">
            <label>Trabajador *</label>
            <select name="id_trab" required>
                <option value="">Seleccionar...</option>
                @foreach ($trabajadores as $trab)
                    <option value="{{ $trab->id_trab }}" @selected(old('id_trab', $adelanto?->id_trab) == $trab->id_trab)>
                        {{ $trab->nombre_completo }}
                    </option>
                @endforeach
            </select>
            @error('id_trab') <div class="text-danger">{{ $message }}</div> @enderror
        </div>

        <div class="form-group">
            <label>Tipo *</label>
            <select name="tipo" required>
                @foreach (App\Models\Adelanto::TIPOS as $tipo)
                    <option value="{{ $tipo }}" @selected(old('tipo', $adelanto?->tipo, 'adelanto') === $tipo)>
                        {{ $tipo === 'prestamo' ? 'Préstamo' : 'Adelanto' }}
                    </option>
                @endforeach
            </select>
            @error('tipo') <div class="text-danger">{{ $message }}</div> @enderror
        </div>

        <div class="form-group">
            <label>Monto ($) *</label>
            <input type="number" step="0.01" min="0.01" name="monto"
                   value="{{ old('monto', $adelanto?->monto) }}" required placeholder="0.00">
            @error('monto') <div class="text-danger">{{ $message }}</div> @enderror
        </div>

        <div class="form-group">
            <label>Fecha *</label>
            <input type="date" name="fecha"
                   value="{{ old('fecha', $adelanto?->fecha?->format('Y-m-d') ?? date('Y-m-d')) }}" required>
            @error('fecha') <div class="text-danger">{{ $message }}</div> @enderror
        </div>
    </div>

    <div class="form-group">
        <label>Nota / Observación</label>
        <textarea name="nota" rows="2" placeholder="Motivo del adelanto o préstamo, forma de pago acordada...">{{ old('nota', $adelanto?->nota) }}</textarea>
        @error('nota') <div class="text-danger">{{ $message }}</div> @enderror
    </div>

    <div class="flex-between mt-4">
        <a href="{{ route('adelantos.index') }}" class="btn btn-secondary">Cancelar</a>
        <button type="submit" class="btn btn-primary">{{ $adelanto ? 'Actualizar' : 'Guardar' }}</button>
    </div>
</form>