<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Nómina') · Sistema de Nómina</title>
    <script>
        (function () {
            try {
                var guardado = localStorage.getItem('nomina-tema');
                var tema = guardado || (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
                document.documentElement.setAttribute('data-theme', tema);
            } catch (e) {}
        }());
    </script>
    @include('partials.styles')
</head>
<body>
<div class="layout">
    <aside class="sidebar" id="sidebar">
        <div class="brand">
            <span class="dot"></span> Panda Multiverse
        </div>
        @php $rol = strtolower(Auth::user()->rol?->rol ?? ''); @endphp
        <a href="{{ route('dashboard') }}" class="{{ active('dashboard') }}">Dashboard</a>
        
        @if (in_array($rol, ['moderador', 'modelo'], true))
            <a href="{{ route('reportes.index') }}" class="{{ active('reportes') }}">Reportes de pago</a>
        @else
            <a href="{{ route('reportes.index') }}" class="{{ active('reportes') }}">Reportes de pago</a>
            <a href="{{ route('cierres.index') }}" class="{{ active('cierres') }}">Cierres semanales</a>
            <a href="{{ route('pagos.index') }}" class="{{ active('pagos') }}">Pagos a empleados</a>
            <a href="{{ route('trabajadores.index') }}" class="{{ active('trabajadores') }}">Trabajadores</a>
            <a href="{{ route('metodos.index') }}" class="{{ active('metodos') }}">Métodos de pago</a>
            <a href="{{ route('roles.index') }}" class="{{ active('roles') }}">Roles</a>
        @endif
    </aside>
    <div class="sidebar-backdrop" id="navBackdrop"></div>
    <div class="main">
        <header class="topbar">
    <div class="topbar-left">
        <button type="button" class="icon-btn nav-toggle" id="navToggle" aria-label="Abrir menú" aria-expanded="false" aria-controls="sidebar">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                <path d="M4 6h16M4 12h16M4 18h16" />
            </svg>
        </button>
    <h1>@yield('title', 'Dashboard')</h1>
    </div>
    @auth
        <div class="topbar-right">
            <button type="button" class="icon-btn theme-toggle" id="themeToggle" aria-label="Cambiar entre modo claro y oscuro" title="Modo claro / oscuro">
                <svg class="icon-moon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z" />
                </svg>
                <svg class="icon-sun" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="4" />
                    <path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M4.93 19.07l1.41-1.41M17.66 6.34l1.41-1.41" />
                </svg>
            </button>
            <div style="text-align: right;">
                <div style="font-size: .85rem; font-weight: 600;">{{ Auth::user()->nombre_completo ?? Auth::user()->nombre }}</div>
                <div class="muted" style="font-size: .75rem;">{{ Auth::user()->email }}</div>
            </div>
            <form method="POST" action="{{ route('logout') }}" class="inline">
                @csrf
                <button type="submit" class="btn btn-secondary btn-sm">Cerrar sesión</button>
            </form>
        </div>
    @endauth
</header>
        <main class="content">
            @if (session('success'))
                <div class="flash success">{{ session('success') }}</div>
            @endif
            @yield('content')
        </main>
    </div>
</div>
<script>
    (function () {
        var root = document.documentElement;
        var toggle = document.getElementById('themeToggle');

        if (toggle) {
            toggle.addEventListener('click', function () {
                var nuevo = root.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
                root.setAttribute('data-theme', nuevo);
                try { localStorage.setItem('nomina-tema', nuevo); } catch (e) {}
            });
        }

        var sidebar = document.getElementById('sidebar');
        var navToggle = document.getElementById('navToggle');
        var backdrop = document.getElementById('navBackdrop');

        function menu(abrir) {
            if (!sidebar) {
                return;
            }
            sidebar.classList.toggle('open', abrir);
            if (backdrop) {
                backdrop.classList.toggle('show', abrir);
            }
            document.body.classList.toggle('nav-open', abrir);
        }

        if (navToggle) {
            navToggle.addEventListener('click', function () {
                menu(!sidebar.classList.contains('open'));
            });
        }
        if (backdrop) {
            backdrop.addEventListener('click', function () { menu(false); });
        }
    }());
</script>
</body>
</html>