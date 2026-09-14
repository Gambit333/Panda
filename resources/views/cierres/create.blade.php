@extends('layouts.app')

@section('title', 'Nuevo cierre semanal')

@section('content')
    <div class="card" style="max-width: 520px;">
        <h2 style="font-size:1rem; margin-bottom:1.25rem;">Generar cierre semanal</h2>
        <p class="muted mb-4" style="font-size:.85rem;">
            Al guardar se asignan automáticamente los reportes sin cierre cuya fecha esté dentro del período.
        </p>
        <form method="POST" action="{{ route('cierres.store') }}">
            @csrf

            <div class="form-group">
                <label>Fecha de inicio *</label>
                <input type="date" name="fecha_inicio" value="{{ old('fecha_inicio') }}" required>
                @error('fecha_inicio') <div class="text-danger">{{ $message }}</div> @enderror
            </div>

            <div class="form-group">
                <label>Fecha de fin *</label>
                <input type="date" name="fecha_fin" value="{{ old('fecha_fin') }}" required>
                @error('fecha_fin') <div class="text-danger">{{ $message }}</div> @enderror
            </div>

            <div class="flex-between">
                <a href="{{ route('cierres.index') }}" class="btn btn-secondary">Cancelar</a>
                <button type="submit" class="btn btn-primary">Generar cierre</button>
            </div>
        </form>
    </div>
@endsection