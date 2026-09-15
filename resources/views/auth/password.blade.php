@extends('layouts.auth')

@section('title', $createsPassword ? 'Crear contraseña' : 'Contraseña')

@section('content')
    @if ($createsPassword)
        <h1>Bienvenido, {{ $trabajador->nombre }}</h1>
        <p class="hint">Tu cuenta no tiene contraseña todavía. Crea una para poder iniciar sesión.</p>
    @else
        <h1>Hola, {{ $trabajador->nombre }}</h1>
        <p class="hint">Ingresa la contraseña de tu cuenta.</p>
    @endif

    @if ($errors->has('password'))
        <div class="alert-danger">{{ $errors->first('password') }}</div>
    @endif

    <form method="POST" action="{{ route('login.password.submit') }}">
        @csrf
        <div class="form-group">
            <label for="password">{{ $createsPassword ? 'Nueva contraseña' : 'Contraseña' }}</label>
            <input type="password" id="password" name="password" required autofocus @if ($createsPassword) autocomplete="new-password" @else autocomplete="current-password" @endif>
            @if ($createsPassword)
                <div class="text-danger" style="color: var(--muted)">Mínimo 6 caracteres.</div>
            @endif
        </div>

        @if ($createsPassword)
            <div class="form-group">
                <label for="password_confirmation">Repite la contraseña</label>
                <input type="password" id="password_confirmation" name="password_confirmation" required autocomplete="new-password">
                @error('password') <div class="text-danger">{{ $message }}</div> @enderror
            </div>
        @endif

        <button type="submit" class="btn">{{ $createsPassword ? 'Crear contraseña e ingresar' : 'Ingresar' }}</button>
    </form>

    <a href="{{ route('login') }}" class="back-link">← Volver a ingresar otro email</a>
@endsection