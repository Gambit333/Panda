@extends('layouts.app')

@section('title', 'Editar trabajador')

@section('content')
    <div class="card" style="max-width: 900px;">
        <h2 style="font-size:1rem; margin-bottom:1.25rem;">Editar trabajador</h2>
        @include('trabajadores._form', [
            'roles' => $roles,
            'metodosSinDueno' => $metodosSinDueno,
            'metodosAsignados' => $metodosAsignados,
        ])
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var rolSelect = document.querySelector('select[name="id_rol"]');
            var contenedor = document.getElementById('metodos-propietario');
            if (!rolSelect || !contenedor) return;

            @php
                $idPropietario = $roles->firstWhere('rol', 'propietario')?->id_rol;
            @endphp

            function actualizar() {
                var valor = rolSelect.value;
                var esPropietario = valor && "{{ $idPropietario }}" && valor == "{{ $idPropietario }}";
                contenedor.style.display = esPropietario ? '' : 'none';
            }

            rolSelect.addEventListener('change', actualizar);
            actualizar();
        });
    </script>
@endsection