@extends('layouts.app')

@section('title', 'Nuevo método de pago')

@section('content')
    <div class="card" style="max-width: 600px;">
        <h2 style="font-size:1rem; margin-bottom:1.25rem;">Registrar método de pago</h2>
        @include('metodos._form')
    </div>
@endsection