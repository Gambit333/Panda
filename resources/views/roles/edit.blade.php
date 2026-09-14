@extends('layouts.app')

@section('title', 'Editar rol')

@section('content')
    <div class="card" style="max-width: 480px;">
        <h2 style="font-size:1rem; margin-bottom:1.25rem;">Editar rol #{{ $rol->id_rol }}</h2>
        @include('roles._form')
    </div>
@endsection