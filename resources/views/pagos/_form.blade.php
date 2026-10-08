@php
    $pago ??= null;
    $saldos ??= [];
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
                        {{ $cierre->fecha_inicio->format('d/m/Y') }} / {{ $cierre->fecha_fin->format('d/m/Y') }}
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
            <div class="muted" id="avisoAdelantos" style="font-size:.78rem; margin-top:.35rem;">
                @if ($saldos)
                    Al elegir el trabajador se llena con su saldo pendiente de adelantos.
                @else
                    Este trabajador no tiene adelantos ni préstamos pendientes.
                @endif
            </div>
            <button type="button" class="btn btn-secondary btn-sm" id="usarSaldoAdelantos" style="display: none; margin-top: .5rem;">
                Usar saldo de adelantos
            </button>
            <label style="display:flex; align-items:center; gap:.4rem; margin-top:.5rem; font-weight:400; font-size:.8rem;">
                <input type="checkbox" name="aplicar_adelantos" value="1" id="aplicarAdelantos"
                       @checked(old('aplicar_adelantos', $pago ? 1 : 1)) style="width:auto;">
                Registrar el descuento como abono en Adelantos y préstamos
            </label>
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
    const workerSelect = document.querySelector('select[name="id_trab"]');
    const netInput = document.getElementById('monto_neto');
    const debtInput = document.getElementById('deuda');
    const finalInput = document.getElementById('monto_final');
    const aviso = document.getElementById('avisoAdelantos');
    const usarSaldo = document.getElementById('usarSaldoAdelantos');
    const saldos = @json($saldos);

    function calculateFinal() {
        const neto = parseFloat(netInput.value) || 0;
        const deuda = parseFloat(debtInput.value) || 0;
        finalInput.value = (neto - deuda).toFixed(2);
    }

    function saldoDe(trabajador) {
        return parseFloat(saldos[String(trabajador)] || 0).toFixed(2);
    }

    function mostrarSaldo() {
        const saldo = saldoDe(workerSelect.value);

        if (!workerSelect.value) {
            aviso.textContent = 'Selecciona un trabajador para ver su saldo de adelantos.';
            usarSaldo.style.display = 'none';
            return;
        }

        if (saldo > 0) {
            aviso.textContent = 'Saldo pendiente de adelantos y préstamos: $' + saldo;
            usarSaldo.style.display = 'inline-flex';
        } else {
            aviso.textContent = 'Este trabajador no tiene adelantos ni préstamos pendientes.';
            usarSaldo.style.display = 'none';
        }

        debtInput.value = saldo > 0 ? saldo : debtInput.value;
        calculateFinal();
    }

    workerSelect.addEventListener('change', mostrarSaldo);

    usarSaldo.addEventListener('click', function () {
        debtInput.value = saldoDe(workerSelect.value);
        calculateFinal();
    });

    netInput.addEventListener('input', calculateFinal);
    debtInput.addEventListener('input', calculateFinal);

    calculateFinal();
});
</script>