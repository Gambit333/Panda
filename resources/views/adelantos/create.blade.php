@extends('layouts.app')

@section('title', 'Nuevo adelanto o préstamo')

@section('content')
    <div class="card" style="max-width: 900px;">
        <h2 style="font-size:1rem; margin-bottom:1.25rem;">Registrar adelanto o préstamo</h2>
        @include('adelantos._form')
    </div>
@endsection