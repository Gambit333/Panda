<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Nómina') · Sistema de Nómina</title>
    <style>
        :root {
            --primary: #4f46e5;
            --primary-dark: #4338ca;
            --bg: #f1f5f9;
            --ink: #0f172a;
            --muted: #64748b;
            --border: #e2e8f0;
            --danger: #dc2626;
            --success: #16a34a;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: system-ui, -apple-system, Segoe UI, Roboto, sans-serif; background: var(--bg); color: var(--ink); }
        .layout { display: flex; min-height: 100vh; }
        .sidebar {
            width: 240px; background: #1e293b; color: #cbd5e1; padding: 1.25rem .75rem;
            display: flex; flex-direction: column; gap: .25rem; flex-shrink: 0;
        }
        .sidebar .brand { display: flex; align-items: center; gap: .6rem; padding: .5rem .75rem 1.25rem; color: #fff; font-weight: 700; font-size: 1.05rem; }
        .sidebar .brand .dot { width: 10px; height: 10px; border-radius: 999px; background: var(--primary); }
        .sidebar a {
            display: flex; align-items: center; gap: .6rem; padding: .55rem .75rem; border-radius: .5rem;
            color: #cbd5e1; text-decoration: none; font-size: .9rem; transition: background .15s;
        }
        .sidebar a:hover { background: #334155; color: #fff; }
        .sidebar a.active { background: var(--primary); color: #fff; }
        .main { flex: 1; display: flex; flex-direction: column; min-width: 0; }
        .topbar { background: #fff; border-bottom: 1px solid var(--border); padding: .9rem 1.5rem; display: flex; align-items: center; justify-content: space-between; }
        .topbar h1 { font-size: 1.05rem; }
        .content { padding: 1.5rem; flex: 1; }
        .flash { padding: .9rem 1.1rem; border-radius: .5rem; margin-bottom: 1.25rem; font-size: .9rem; border: 1px solid; }
        .flash.success { background: #f0fdf4; color: var(--success); border-color: #bbf7d0; }
        .card { background: #fff; border: 1px solid var(--border); border-radius: .75rem; padding: 1.25rem; }
        .cards { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1rem; margin-bottom: 1.5rem; }
        .stat { background: #fff; border: 1px solid var(--border); border-radius: .75rem; padding: 1.1rem 1.25rem; }
        .stat .label { color: var(--muted); font-size: .78rem; text-transform: uppercase; letter-spacing: .04em; }
        .stat .value { font-size: 1.5rem; font-weight: 700; margin-top: .25rem; }
        table { width: 100%; border-collapse: collapse; font-size: .875rem; }
        th, td { text-align: left; padding: .6rem .75rem; border-bottom: 1px solid var(--border); }
        th { color: var(--muted); font-weight: 600; font-size: .76rem; text-transform: uppercase; letter-spacing: .03em; background: #f8fafc; }
        tr:hover td { background: #f8fafc; }
        .btn {
            display: inline-flex; align-items: center; gap: .35rem; padding: .5rem .9rem; border-radius: .5rem;
            border: 1px solid transparent; font-size: .85rem; text-decoration: none; cursor: pointer;
            transition: background .15s; font-family: inherit;
        }
        .btn-primary { background: var(--primary); color: #fff; }
        .btn-primary:hover { background: var(--primary-dark); }
        .btn-secondary { background: #fff; color: var(--ink); border-color: var(--border); }
        .btn-secondary:hover { background: #f8fafc; }
        .btn-danger { background: #fff; color: var(--danger); border-color: #fecaca; }
        .btn-danger:hover { background: #fef2f2; }
        .btn-sm { padding: .3rem .6rem; font-size: .78rem; }
        form.inline { display: inline; }
        .form-group { margin-bottom: 1rem; }
        .form-group label { display: block; font-size: .82rem; font-weight: 600; margin-bottom: .3rem; color: var(--ink); }
        .form-group input, .form-group select, .form-group textarea {
            width: 100%; padding: .55rem .7rem; border: 1px solid var(--border); border-radius: .5rem;
            font-size: .9rem; font-family: inherit; background: #fff;
        }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus {
            outline: 2px solid var(--primary); outline-offset: -1px; border-color: transparent;
        }
        .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1rem; }
        .text-danger { color: var(--danger); font-size: .78rem; margin-top: .25rem; }
        .empty { text-align: center; color: var(--muted); padding: 2.5rem 1rem; }
        .pagination { display: flex; justify-content: flex-end; padding-top: 1rem; font-size: .875rem; }
        .badge { display: inline-block; padding: .2rem .6rem; border-radius: 999px; font-size: .72rem; font-weight: 600; background: #eef2ff; color: var(--primary); }
        .mt-4 { margin-top: 1rem; }
        .mb-4 { margin-bottom: 1rem; }
        .flex-between { display: flex; align-items: center; justify-content: space-between; gap: 1rem; }
        .muted { color: var(--muted); }
        .positive { color: var(--success); font-weight: 600; }
        .actions { display: flex; gap: .4rem; }
        @media (max-width: 768px) { .layout { flex-direction: column; } .sidebar { width: 100%; } }
    </style>
</head>
<body>
<div class="layout">
    <aside class="sidebar">
        <div class="brand">
            <span class="dot"></span> Sistema de Nómina
        </div>
        <a href="{{ route('dashboard') }}" class="{{ active('dashboard') }}">Dashboard</a>
        <a href="{{ route('reportes.index') }}" class="{{ active('reportes') }}">Reportes de pago</a>
        <a href="{{ route('cierres.index') }}" class="{{ active('cierres') }}">Cierres semanales</a>
        <a href="{{ route('pagos.index') }}" class="{{ active('pagos') }}">Pagos a empleados</a>
        <a href="{{ route('trabajadores.index') }}" class="{{ active('trabajadores') }}">Trabajadores</a>
        <a href="{{ route('metodos.index') }}" class="{{ active('metodos') }}">Métodos de pago</a>
        <a href="{{ route('roles.index') }}" class="{{ active('roles') }}">Roles</a>
    </aside>
    <div class="main">
        <header class="topbar">
            <h1>@yield('title', 'Dashboard')</h1>
            <div style="display: flex; align-items: center; gap: 1rem;">
                <span class="muted" style="font-size: .85rem;">{{ Auth::user()->email }}</span>
                <form method="POST" action="{{ route('logout') }}" class="inline">
                    @csrf
                    <button type="submit" class="btn btn-secondary btn-sm">Cerrar sesión</button>
                </form>
            </div>
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
    function ping() {
        fetch('{{ route('session.keepalive') }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            },
            cache: 'no-store',
            credentials: 'same-origin'
        }).catch(function () {});
    }
    setInterval(ping, 2000);
}());
</script>
</body>
</html>