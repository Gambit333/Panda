<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title') · Sistema de Nómina</title>
    <style>
        :root {
            --primary: #4f46e5;
            --primary-dark: #4338ca;
            --bg: #f1f5f9;
            --ink: #0f172a;
            --muted: #64748b;
            --border: #e2e8f0;
            --danger: #dc2626;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: system-ui, -apple-system, Segoe UI, Roboto, sans-serif;
            background: var(--bg); color: var(--ink);
            min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 1.5rem;
        }
        .auth-card { width: 100%; max-width: 380px; background: #fff; border: 1px solid var(--border); border-radius: .9rem; padding: 2rem 1.75rem; box-shadow: 0 10px 30px rgba(15, 23, 42, .06); }
        .brand { display: flex; align-items: center; gap: .6rem; font-weight: 700; font-size: 1.05rem; margin-bottom: 1.5rem; }
        .brand .dot { width: 10px; height: 10px; border-radius: 999px; background: var(--primary); }
        .auth-card h1 { font-size: 1.15rem; margin-bottom: .35rem; }
        .auth-card .hint { color: var(--muted); font-size: .85rem; margin-bottom: 1.25rem; line-height: 1.4; }
        .form-group { margin-bottom: 1rem; }
        .form-group label { display: block; font-size: .82rem; font-weight: 600; margin-bottom: .3rem; }
        .form-group input { width: 100%; padding: .55rem .7rem; border: 1px solid var(--border); border-radius: .5rem; font-size: .9rem; font-family: inherit; background: #fff; }
        .form-group input:focus { outline: 2px solid var(--primary); outline-offset: -1px; border-color: transparent; }
        .btn { display: inline-flex; align-items: center; justify-content: center; width: 100%; padding: .6rem .9rem; border-radius: .5rem; border: 1px solid transparent; font-size: .9rem; font-family: inherit; cursor: pointer; background: var(--primary); color: #fff; transition: background .15s; }
        .btn:hover { background: var(--primary-dark); }
        .text-danger { color: var(--danger); font-size: .78rem; margin-top: .25rem; }
        .alert-danger { background: #fef2f2; color: var(--danger); border: 1px solid #fecaca; border-radius: .5rem; padding: .7rem .9rem; font-size: .85rem; margin-bottom: 1rem; }
        .back-link { display: block; text-align: center; margin-top: 1rem; font-size: .82rem; color: var(--muted); text-decoration: none; }
        .back-link:hover { color: var(--ink); }
    </style>
</head>
<body>
    <div class="auth-card">
        <div class="brand"><span class="dot"></span> Panda Multiverse</div>
        @yield('content')
    </div>
</body>
</html>