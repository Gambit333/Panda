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
            <label>Monto Bruto ($) *</label>
            <input type="number" step="0.01" min="0" name="monto_bruto" id="monto_bruto" value="{{ old('monto_bruto', $pago?->monto_bruto) }}" required placeholder="0.00">
            @error('monto_bruto') <div class="text-danger">{{ $message }}</div> @enderror
        </div>

        <div class="form-group">
            <label>Monto Neto ($) *</label>
            <input type="number" step="0.01" min="0" name="monto_neto" id="monto_neto" value="{{ old('monto_neto', $pago?->monto_neto) }}" required placeholder="0.00">
            @error('monto_neto') <div class="text-danger">{{ $message }}</div> @enderror
        </div>

        <div class="form-group">
            <label>Deuda / Descuento ($)</label>
            <input type="number" step="0.01" min="0" name="deuda" id="deuda" value="{{ old('deuda', $pago?->deuda ?? 0) }}" placeholder="0.00">
            @error('deuda') <div class="text-danger">{{ $message }}</div> @enderror
        </div>

        <div class="form-group">
            <label>Monto Final ($) *</label>
            <input type="number" step="0.01" name="monto_final" id="monto_final" value="{{ old('monto_final', $pago?->monto_final) }}" required placeholder="0.00">
            @error('monto_final') <div class="text-danger">{{ $message }}</div> @enderror
        </div>
    </div>

    <div class="form-group">
        <label>Nota / Observación</label>
        <textarea name="nota" rows="2" placeholder="Detalles adicionales sobre el pago o saldo pendiente...">{{ old('nota', $pago?->nota) }}</textarea>
        @error('nota') <div class="text-danger">{{ $message }}</div> @enderror
    </div>

    <div class="flex-between mt-4">
        <a href="{{ route('pagos.index') }}" class="btn btn-secondary">Cancelar</a>
        <button type="submit" class="btn btn-primary">{{ $pago ? 'Actualizar' : 'Guardar' }}</button>
    </div>
</form>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const netInput = document.getElementById('monto_neto');
    const debtInput = document.getElementById('deuda');
    const finalInput = document.getElementById('monto_final');

    function calculateFinal() {
        const neto = parseFloat(netInput.value) || 0;
        const deuda = parseFloat(debtInput.value) || 0;
        finalInput.value = (neto - deuda).toFixed(2);
    }

    netInput.addEventListener('input', calculateFinal);
    debtInput.addEventListener('input', calculateFinal);
});
</script>