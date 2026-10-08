@php
    $reporte ??= null;
    $usuarioActual ??= auth()->user();
    // Asumiendo roles 1 (CEO) y 3 (Admin) como administradores
    $esAdmin ??= in_array($usuarioActual?->id_rol, [1, 3]);
@endphp

<form method="POST" action="{{ $reporte ? route('reportes.update', $reporte) : route('reportes.store') }}" enctype="multipart/form-data">
    @csrf
    @if ($reporte) @method('PUT') @endif

    <div class="form-grid">
        <div class="form-group">
            <label>Modelo *</label>
            <select name="id_modelo" required>
                <option value="">Seleccionar...</option>
                @foreach ($modelos as $trab)
                    <option value="{{ $trab->id_trab }}" @selected(old('id_modelo', $reporte?->id_modelo) == $trab->id_trab)>
                        {{ $trab->nombre_completo ?? ($trab->nombre . ' ' . $trab->apellido) }}
                    </option>
                @endforeach
            </select>
            @error('id_modelo') <div class="text-danger">{{ $message }}</div> @enderror
        </div>

        <div class="form-group">
            <label>Plataforma *</label>
            <input type="text" name="plataforma" value="{{ old('plataforma', $reporte?->plataforma) }}" placeholder="Ej. OnlyFans, Fansly, Cam4" required>
            @error('plataforma') <div class="text-danger">{{ $message }}</div> @enderror
        </div>

        <div class="form-group">
            <label>Usuario del cliente *</label>
            <input type="text" name="user_cliente" value="{{ old('user_cliente', $reporte?->user_cliente) }}" placeholder="Ej. @john_doe" required>
            @error('user_cliente') <div class="text-danger">{{ $message }}</div> @enderror
        </div>

        <div class="form-group">
            <label>Método de pago *</label>
            <select name="id_mp" required>
                <option value="">Seleccionar...</option>
                @foreach ($metodosPago as $mp)
                    <option value="{{ $mp->id_mp }}" @selected(old('id_mp', $reporte?->id_mp) == $mp->id_mp)>
                        {{ $mp->metodo_pago }} {{ $mp->propietario ? '('.$mp->propietario.')' : '' }}
                    </option>
                @endforeach
            </select>
            @error('id_mp') <div class="text-danger">{{ $message }}</div> @enderror
        </div>

        <div class="form-group">
            <label>Precio ($) *</label>
            <input type="number" step="0.01" min="0" name="precio" value="{{ old('precio', $reporte?->precio) }}" placeholder="0.00" required>
            @error('precio') <div class="text-danger">{{ $message }}</div> @enderror
        </div>

        <div class="form-group">
            <label>Servicio *</label>
            <input type="text" name="servicio" value="{{ old('servicio', $reporte?->servicio) }}" placeholder="Ej. Videollamada, Custom video, Propina" required>
            @error('servicio') <div class="text-danger">{{ $message }}</div> @enderror
        </div>

        <div class="form-group">
            <label>Duración</label>
            <input type="text" name="duracion" value="{{ old('duracion', $reporte?->duracion) }}" placeholder="Ej: 30 min, N/A">
            @error('duracion') <div class="text-danger">{{ $message }}</div> @enderror
        </div>

        <div class="form-group">
            <label>Fecha del reporte *</label>
            <input type="date" name="fecha_reporte" value="{{ old('fecha_reporte', $reporte?->fecha_reporte?->format('Y-m-d') ?? now()->format('Y-m-d')) }}" required>
            @error('fecha_reporte') <div class="text-danger">{{ $message }}</div> @enderror
        </div>

        {{-- Oculta el campo si es Moderador, pero envía su ID automáticamente al guardar --}}
        <div class="form-group @if(!$esAdmin) hidden @endif">
            <label>Moderador / Chatter *</label>
            <select name="id_moderador" required>
                @if($esAdmin)
                    <option value="">Seleccionar...</option>
                @endif

                @foreach ($moderadores as $trab)
                    <option value="{{ $trab->id_trab }}" 
                        @selected(old('id_moderador', $reporte?->id_moderador ?? $usuarioActual?->id_trab) == $trab->id_trab)>
                        {{ $trab->nombre_completo ?? ($trab->nombre . ' ' . $trab->apellido) }}
                    </option>
                @endforeach
            </select>
            @error('id_moderador') <div class="text-danger">{{ $message }}</div> @enderror
        </div>
    </div>

    <div class="form-group">
        <label>Descripción / Detalles adicionales</label>
        <textarea name="descripcion" rows="3" placeholder="Observaciones sobre la transacción o especificaciones del cliente...">{{ old('descripcion', $reporte?->descripcion) }}</textarea>
        @error('descripcion') <div class="text-danger">{{ $message }}</div> @enderror
    </div>

    <div class="form-group">
        <label>Comprobante de pago (imagen)</label>
        @if ($reporte?->comprobante_url)
            <div style="display:flex; align-items:center; gap:.75rem; margin-bottom:.5rem;">
                <a href="{{ $reporte->comprobante_url }}" target="_blank">
                    <img src="{{ $reporte->comprobante_url }}" alt="Comprobante actual" style="width:64px;height:64px;object-fit:cover;border-radius:8px;">
                </a>
                <label style="font-weight:400;">
                    <input type="checkbox" name="eliminar_comprobante" value="1"> <span class="muted">Eliminar comprobante actual</span>
                </label>
            </div>
        @endif
        <input type="file" name="comprobante" accept="image/*">
        @error('comprobante') <div class="text-danger">{{ $message }}</div> @enderror
        <small class="muted">Se guarda en disco y se comprime; la BD solo almacena la ruta. Máx. 4 MB.</small>
    </div>

    <div class="flex-between mt-4">
        <a href="{{ route('reportes.index') }}" class="btn btn-secondary">Cancelar</a>
        <button type="submit" class="btn btn-primary">{{ $reporte ? 'Actualizar' : 'Guardar' }}</button>
    </div>
</form>