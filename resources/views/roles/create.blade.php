@extends('layouts.app')

@section('title', 'Nuevo rol')

@section('content')
    <div class="card" style="max-width: 480px;">
        <h2 style="font-size:1rem; margin-bottom:1.25rem;">Registrar rol</h2>
        @include('roles._form')
    </div>
@endsection