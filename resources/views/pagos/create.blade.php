@extends('layouts.app')

@section('title', 'Nuevo pago a empleado')

@section('content')
    <div class="card" style="max-width: 900px;">
        <h2 style="font-size:1rem; margin-bottom:1.25rem;">Registrar pago a empleado</h2>
        @include('pagos._form')
    </div>
@endsection