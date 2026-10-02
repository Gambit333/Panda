<style>
    :root {
        --primary: #4f46e5;
        --primary-dark: #4338ca;
        --bg: #f1f5f9;
        --surface: #ffffff;
        --surface-alt: #f8fafc;
        --ink: #0f172a;
        --muted: #64748b;
        --border: #e2e8f0;
        --danger: #dc2626;
        --danger-bg: #fef2f2;
        --danger-border: #fecaca;
        --success: #16a34a;
        --success-bg: #f0fdf4;
        --success-border: #bbf7d0;
        --badge-bg: #eef2ff;
        --input-bg: #ffffff;
        --sidebar-bg: #1e293b;
        --sidebar-ink: #cbd5e1;
        --sidebar-hover: #334155;
        --on-primary: #ffffff;
        --overlay: rgba(15, 23, 42, .55);
        color-scheme: light;
    }

    [data-theme="dark"] {
        --primary: #818cf8;
        --primary-dark: #6366f1;
        --bg: #0b1120;
        --surface: #111827;
        --surface-alt: #1f2937;
        --ink: #e5e7eb;
        --muted: #94a3b8;
        --border: #1f2937;
        --danger: #f87171;
        --danger-bg: #2b1215;
        --danger-border: #7f1d1d;
        --success: #4ade80;
        --success-bg: #10241a;
        --success-border: #14532d;
        --badge-bg: #1e1b4b;
        --input-bg: #0b1220;
        --sidebar-bg: #0f172a;
        --sidebar-ink: #cbd5e1;
        --sidebar-hover: #1e293b;
        --on-primary: #0b1120;
        --overlay: rgba(0, 0, 0, .6);
        color-scheme: dark;
    }

    * { box-sizing: border-box; margin: 0; padding: 0; }
    html { -webkit-text-size-adjust: 100%; }
    body {
        font-family: system-ui, -apple-system, Segoe UI, Roboto, sans-serif;
        background: var(--bg); color: var(--ink);
        -webkit-tap-highlight-color: rgba(79, 70, 229, .15);
    }
    img { max-width: 100%; height: auto; }

    .layout { display: flex; min-height: 100vh; }
    .sidebar {
        width: 240px; background: var(--sidebar-bg); color: var(--sidebar-ink);
        padding: 1.25rem .75rem; display: flex; flex-direction: column; gap: .25rem; flex-shrink: 0;
    }
    .sidebar .brand { display: flex; align-items: center; gap: .6rem; padding: .5rem .75rem 1.25rem; color: #fff; font-weight: 700; font-size: 1.05rem; }
    .sidebar .brand .dot { width: 10px; height: 10px; border-radius: 999px; background: var(--primary); flex-shrink: 0; }
    .sidebar a {
        display: flex; align-items: center; gap: .6rem; padding: .55rem .75rem; border-radius: .5rem;
        color: var(--sidebar-ink); text-decoration: none; font-size: .9rem; transition: background .15s;
    }
    .sidebar a:hover { background: var(--sidebar-hover); color: #fff; }
    .sidebar a.active { background: var(--primary); color: #fff; }

    .sidebar-backdrop { display: none; }

    .main { flex: 1; display: flex; flex-direction: column; min-width: 0; }
    .topbar {
        background: var(--surface); border-bottom: 1px solid var(--border); padding: .9rem 1.5rem;
        display: flex; align-items: center; justify-content: space-between; gap: .75rem;
        position: sticky; top: 0; z-index: 20;
    }
    .topbar h1 { font-size: 1.05rem; min-width: 0; }
    .topbar-left, .topbar-right { display: flex; align-items: center; gap: .6rem; min-width: 0; }
    .topbar-right { flex-wrap: wrap; justify-content: flex-end; }

    .content { padding: 1.5rem; flex: 1; }
    .flash { padding: .9rem 1.1rem; border-radius: .5rem; margin-bottom: 1.25rem; font-size: .9rem; border: 1px solid; }
    .flash.success { background: var(--success-bg); color: var(--success); border-color: var(--success-border); }
    .flash.error { background: var(--danger-bg); color: var(--danger); border-color: var(--danger-border); }

    .card { background: var(--surface); border: 1px solid var(--border); border-radius: .75rem; padding: 1.25rem; min-width: 0; }
    .cards { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1rem; margin-bottom: 1.5rem; }
    .stat { background: var(--surface); border: 1px solid var(--border); border-radius: .75rem; padding: 1.1rem 1.25rem; }
    .stat .label { color: var(--muted); font-size: .78rem; text-transform: uppercase; letter-spacing: .04em; }
    .stat .value { font-size: 1.5rem; font-weight: 700; margin-top: .25rem; }

    table { width: 100%; border-collapse: collapse; font-size: .875rem; }
    th, td { text-align: left; padding: .6rem .75rem; border-bottom: 1px solid var(--border); }
    th { color: var(--muted); font-weight: 600; font-size: .76rem; text-transform: uppercase; letter-spacing: .03em; background: var(--surface-alt); }
    tr:hover td { background: var(--surface-alt); }

    /* Contenedor con scroll horizontal: la tabla se adapta al ancho disponible
       y, si no cabe (celulares), se desliza a la derecha sin romper el layout. */
    .table-wrap { width: 100%; max-width: 100%; overflow-x: auto; -webkit-overflow-scrolling: touch; }
    .table-wrap > table { min-width: 100%; }

    .btn {
        display: inline-flex; align-items: center; justify-content: center; gap: .35rem;
        padding: .5rem .9rem; border-radius: .5rem; border: 1px solid transparent;
        font-size: .85rem; text-decoration: none; cursor: pointer; white-space: nowrap;
        transition: background .15s, color .15s, border-color .15s; font-family: inherit;
    }
    .btn-primary { background: var(--primary); color: #fff; }
    .btn-primary:hover { background: var(--primary-dark); }
    .btn-secondary { background: var(--surface); color: var(--ink); border-color: var(--border); }
    .btn-secondary:hover { background: var(--surface-alt); }
    .btn-danger { background: var(--surface); color: var(--danger); border-color: var(--danger-border); }
    .btn-danger:hover { background: var(--danger-bg); }
    .btn-sm { padding: .35rem .65rem; font-size: .78rem; }

    .icon-btn {
        display: inline-flex; align-items: center; justify-content: center;
        width: 2.25rem; height: 2.25rem; border-radius: .5rem; cursor: pointer;
        background: var(--surface); color: var(--ink); border: 1px solid var(--border);
    }
    .icon-btn:hover { background: var(--surface-alt); }
    .icon-btn svg { width: 1.15rem; height: 1.15rem; }
    .nav-toggle { display: none; flex-shrink: 0; }
    .theme-toggle .icon-sun { display: none; }
    [data-theme="dark"] .theme-toggle .icon-sun { display: block; }
    [data-theme="dark"] .theme-toggle .icon-moon { display: none; }

    form.inline { display: inline; }
    .form-group { margin-bottom: 1rem; }
    .form-group label { display: block; font-size: .82rem; font-weight: 600; margin-bottom: .3rem; color: var(--ink); }
    .form-group input, .form-group select, .form-group textarea {
        width: 100%; padding: .55rem .7rem; border: 1px solid var(--border); border-radius: .5rem;
        font-size: .9rem; font-family: inherit; background: var(--input-bg); color: var(--ink);
    }
    .form-group input:focus, .form-group select:focus, .form-group textarea:focus {
        outline: 2px solid var(--primary); outline-offset: -1px; border-color: transparent;
    }
    .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1rem; }
    .input-sm {
        width: 110px; padding: .35rem .5rem; border: 1px solid var(--border); border-radius: .5rem;
        background: var(--input-bg); color: var(--ink); font-size: .8rem; font-family: inherit;
    }
    .input-sm:focus { outline: 2px solid var(--primary); outline-offset: -1px; border-color: transparent; }
    .text-danger { color: var(--danger); font-size: .78rem; margin-top: .25rem; }
    .empty { text-align: center; color: var(--muted); padding: 2.5rem 1rem; }
    .pagination { display: flex; align-items: center; justify-content: center; padding-top: 1rem; flex-wrap: wrap; }
    .page-links { display: flex; align-items: center; justify-content: center; flex-wrap: wrap; gap: .25rem; }
    .page-btn {
        display: inline-flex; align-items: center; justify-content: center; line-height: 1;
        min-width: 1.85rem; height: 1.85rem; padding: 0 .45rem; border-radius: .5rem;
        border: 1px solid var(--border); background: var(--surface); color: var(--ink);
        font-size: .82rem; font-weight: 600; text-decoration: none;
    }
    .page-btn:hover { background: var(--surface-alt); }
    .page-btn svg { width: 14px; height: 14px; }
    .page-btn.is-active { background: var(--primary); border-color: var(--primary); color: var(--on-primary); }
    .page-btn.is-disabled { opacity: .4; border-style: dashed; }
    .badge { display: inline-block; padding: .2rem .6rem; border-radius: 999px; font-size: .72rem; font-weight: 600; background: var(--badge-bg); color: var(--primary); }
    .mt-4 { margin-top: 1rem; }
    .mb-4 { margin-bottom: 1rem; }
    .flex-between { display: flex; align-items: center; justify-content: space-between; gap: 1rem; }
    .muted { color: var(--muted); }
    .positive { color: var(--success); font-weight: 600; }
    .actions { display: flex; gap: .4rem; }
    .calc-row { display: flex; align-items: center; justify-content: space-between; gap: 1rem; padding: .3rem 0; }
    .calc-row strong { font-weight: 700; }
    .calc-empty { border-top: 1px solid var(--border); }

    @media (max-width: 860px) {
        .layout { flex-direction: column; }
        .nav-toggle { display: inline-flex; }

        .sidebar {
            position: fixed; top: 0; left: 0; bottom: 0; width: 260px; height: 100dvh;
            max-height: 100vh; z-index: 40; overflow-y: auto;
            transform: translateX(-100%); transition: transform .2s ease;
        }
        .sidebar.open { transform: translateX(0); box-shadow: 0 0 40px rgba(0, 0, 0, .45); }
        .sidebar-backdrop {
            display: block; position: fixed; inset: 0; background: var(--overlay); z-index: 30;
            opacity: 0; pointer-events: none; transition: opacity .2s ease;
        }
        .sidebar-backdrop.show { opacity: 1; pointer-events: auto; }
        body.nav-open { overflow: hidden; }

        .topbar { padding: .6rem .75rem; }
        .topbar .user-email { display: none; }
        .content { padding: 1rem .75rem; }
        .card { padding: 1rem; }
        .cards { grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: .75rem; }
        .stat { padding: .9rem 1rem; }
        .stat .value { font-size: 1.25rem; }
        .form-grid { grid-template-columns: 1fr; }

        /* Tablas: se adaptan al ancho disponible y se scrollean a la derecha si no caben */
        .table-wrap { -webkit-overflow-scrolling: touch; }
        .table-wrap > table { min-width: 100%; white-space: nowrap; }
        th, td { padding: .5rem .6rem; }

        .form-group input, .form-group select, .form-group textarea { font-size: 1rem; }
        .input-sm { font-size: 1rem; }
        .actions, .flex-between { flex-wrap: wrap; }
        .page-btn { min-width: 2.1rem; height: 2.1rem; font-size: .9rem; }
    }

    @media (max-width: 420px) {
        .cards { grid-template-columns: 1fr 1fr; }
        .topbar h1 { font-size: .95rem; }
    }
</style>