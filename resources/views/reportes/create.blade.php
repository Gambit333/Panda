@extends('layouts.app')

@section('title', 'Nuevo reporte de pago')

@section('content')
    <div class="card" style="max-width: 900px;">
        <h2 style="font-size:1rem; margin-bottom:1.25rem;">Registrar reporte de pago</h2>
        @include('reportes._form')
    </div>
@endsection