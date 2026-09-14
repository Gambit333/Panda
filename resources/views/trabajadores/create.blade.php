@extends('layouts.app')

@section('title', 'Nuevo trabajador')

@section('content')
    <div class="card" style="max-width: 900px;">
        <h2 style="font-size:1rem; margin-bottom:1.25rem;">Registrar trabajador</h2>
        @include('trabajadores._form')
    </div>
@endsection