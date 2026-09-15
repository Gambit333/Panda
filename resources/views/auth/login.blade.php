@extends('layouts.auth')

@section('title', 'Iniciar sesión')

@section('content')
    <h1>Iniciar sesión</h1>
    <p class="hint">Ingresa el email registrado de tu cuenta para continuar.</p>

    <form method="POST" action="{{ route('login.submit-email') }}">
        @csrf
        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="email">
            @error('email') <div class="text-danger">{{ $message }}</div> @enderror
        </div>

        <button type="submit" class="btn">Continuar</button>
    </form>
@endsection