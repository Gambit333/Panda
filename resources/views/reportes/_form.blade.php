@php
    $reporte ??= null;
@endphp
<form method="POST" action="{{ $reporte ? route('reportes.update', $reporte) : route('reportes.store') }}">
    @csrf
    @if ($reporte) @method('PUT') @endif

    <div class="form-grid">
        <div class="form-group">
            <label>Modelo *</label>
            <select name="id_modelo" required>
                <option value="">Seleccionar...</option>
                @foreach ($modelos as $trab)
                    <option value="{{ $trab->id_trab }}" @selected(old('id_modelo', $reporte?->id_modelo) == $trab->id_trab)>
                        {{ $trab->nombre_completo }}
                    </option>
                @endforeach
            </select>
            @error('id_modelo') <div class="text-danger">{{ $message }}</div> @enderror
        </div>

        <div class="form-group">
            <label>Plataforma *</label>
            <input type="text" name="plataforma" value="{{ old('plataforma', $reporte?->plataforma) }}" required>
            @error('plataforma') <div class="text-danger">{{ $message }}</div> @enderror
        </div>

        <div class="form-group">
            <label>Usuario del cliente *</label>
            <input type="text" name="user_cliente" value="{{ old('user_cliente', $reporte?->user_cliente) }}" required>
            @error('user_cliente') <div class="text-danger">{{ $message }}</div> @enderror
        </div>

        <div class="form-group">
            <label>Método de pago *</label>
            <select name="id_mp" required>
                <option value="">Seleccionar...</option>
                @foreach ($metodosPago as $mp)
                    <option value="{{ $mp->id_mp }}" @selected(old('id_mp', $reporte?->id_mp) == $mp->id_mp)>
                        {{ $mp->metodo_pago }}
                    </option>
                @endforeach
            </select>
            @error('id_mp') <div class="text-danger">{{ $message }}</div> @enderror
        </div>

        <div class="form-group">
            <label>Precio ($) *</label>
            <input type="number" step="0.01" min="0" name="precio" value="{{ old('precio', $reporte?->precio) }}" required>
            @error('precio') <div class="text-danger">{{ $message }}</div> @enderror
        </div>

        <div class="form-group">
            <label>Servicio *</label>
            <input type="text" name="servicio" value="{{ old('servicio', $reporte?->servicio) }}" required>
            @error('servicio') <div class="text-danger">{{ $message }}</div> @enderror
        </div>

        <div class="form-group">
            <label>Duración</label>
            <input type="text" name="duracion" value="{{ old('duracion', $reporte?->duracion) }}" placeholder="Ej: 30 min">
            @error('duracion') <div class="text-danger">{{ $message }}</div> @enderror
        </div>

        <div class="form-group">
            <label>Fecha del reporte</label>
            <input type="date" name="fecha_reporte" value="{{ old('fecha_reporte', $reporte?->fecha_reporte?->format('Y-m-d')) }}">
            @error('fecha_reporte') <div class="text-danger">{{ $message }}</div> @enderror
        </div>

        <div class="form-group">
            <label>Moderador *</label>
            <select name="id_moderador" required>
                <option value="">Seleccionar...</option>
                @foreach ($trabajadores as $trab)
                    <option value="{{ $trab->id_trab }}" @selected(old('id_moderador', $reporte?->id_moderador) == $trab->id_trab)>
                        {{ $trab->nombre_completo }}
                    </option>
                @endforeach
            </select>
            @error('id_moderador') <div class="text-danger">{{ $message }}</div> @enderror
        </div>

        <div class="form-group">
            <label>Cierre semanal</label>
            <select name="id_cierre">
                <option value="">Sin asignar</option>
                @foreach ($cierres as $cierre)
                    <option value="{{ $cierre->id_cierre }}" @selected(old('id_cierre', $reporte?->id_cierre) == $cierre->id_cierre)>
                        #{{ $cierre->id_cierre }} — {{ $cierre->fecha_inicio->format('d/m/Y') }} / {{ $cierre->fecha_fin->format('d/m/Y') }}
                    </option>
                @endforeach
            </select>
            @error('id_cierre') <div class="text-danger">{{ $message }}</div> @enderror
        </div>
    </div>

    <div class="form-group">
        <label>Descripción</label>
        <textarea name="descripcion" rows="3">{{ old('descripcion', $reporte?->descripcion) }}</textarea>
        @error('descripcion') <div class="text-danger">{{ $message }}</div> @enderror
    </div>

    <div class="flex-between">
        <a href="{{ route('reportes.index') }}" class="btn btn-secondary">Cancelar</a>
        <button type="submit" class="btn btn-primary">{{ $reporte ? 'Actualizar' : 'Guardar' }}</button>
    </div>
</form>