@extends('layouts.app')

@section('title', 'Editar pago a empleado')

@section('content')
    <div class="card" style="max-width: 900px;">
        <h2 style="font-size:1rem; margin-bottom:1.25rem;">Editar pago #{{ $pago->id_pago }}</h2>
        @include('pagos._form')
    </div>
@endsection