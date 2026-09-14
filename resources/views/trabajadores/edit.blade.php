@extends('layouts.app')

@section('title', 'Editar trabajador')

@section('content')
    <div class="card" style="max-width: 900px;">
        <h2 style="font-size:1rem; margin-bottom:1.25rem;">Editar trabajador #{{ $trabajador->id_trab }}</h2>
        @include('trabajadores._form')
    </div>
@endsection