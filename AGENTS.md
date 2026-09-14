# Contexto del proyecto — Sistema de Nómina

## Descripción

Sistema de nómina para reportar pagos recibidos por plataformas (OnlyFans, etc.) y liquidar pagos a empleados por cierres semanales. Frontend en Blade (en app renderizada por el servidor), backend **Laravel 12** (PHP 8.2+), base de datos **PostgreSQL** hosteada en **Supabase**. Deploy objetivo: **Laravel Cloud** (PHP sobre la red de Cloudflare; no corre dentro de Cloudflare Workers, que es un runtime JS/WASM).

## Stack

- PHP 8.2 / Composer
- Laravel 12.69
- Blade (sin build de frontend; CSS embebido en `resources/views/layouts/app.blade.php`)
- PostgreSQL (Supabase) vía `pdo_pgsql` con `sslmode=require`
- Tests con PHPUnit (`!` SQLite en memoria por defecto)

## Configuración

1. Instalar extensiones PHP: `pdo_pgsql` y `pgsql` (en XAMPP editar `C:\xampp\php\php.ini`, líneas `extension=pdo_pgsql` y `extension=pgsql`).
2. Configurar `.env` con los datos de Supabase:

```dotenv
APP_NAME=Nomina
DB_CONNECTION=pgsql
DB_HOST=<proyecto>.supabase.co
DB_PORT=5432
DB_DATABASE=postgres
DB_USERNAME=postgres
DB_PASSWORD=<contraseña>
DB_SSLMODE=require
```

3. Generar esquema: `php artisan migrate` (crea las tablas del esquema).
4. Datos base: `php artisan db:seed` (roles y métodos de pago iniciales).

## Esquema de base de datos

| Tabla | PK | Columnas | FKs |
|---|---|---|---|
| `roles` | `id_rol` (int) | `rol` varchar | — |
| `trabajador` | `id_trab` (int) | `nombre`, `apellido`, `telefono?`, `email?`, `direccion?` | `id_rol → roles` |
| `metodos_pago` | `id_mp` (int) | `metodo_pago`, `impuesto?`, `porcentaje_cuenta?` | — |
| `cierre_semanal` | `id_cierre` (int) | `fecha_inicio`, `fecha_fin`, `total?` | — |
| `pago_empleados` | `id_pago` (int) | `monto` | `id_trab → trabajador`, `id_cierre → cierre_semanal` |
| `reporte_pagos` | `id_reporte` (int) | `plataforma`, `user_cliente`, `precio`, `servicio`, `duracion?`, `fecha_reporte?`, `descripcion?` | `id_modelo → trabajador`, `id_moderador → trabajador`, `id_mp → metodos_pago`, `id_cierre? → cierre_semanal` |

Nota: las PK usan `integer` (autoincrement) y las FK `unsignedInteger` para mantener consistencia de tipos en Postgres.

## Lógica de negocio

- **Cierre semanal** (`CierreSemanalController@store`): al crear un cierre con `fecha_inicio`/`fecha_fin`, se asignan automáticamente los reportes sin cierre (`id_cierre` NULL) cuya `fecha_reporte` cae dentro del período y se calcula `total = Σ precio`.
- **Reporte de pago**: registra un pago recibido del cliente, la plataforma, el método de pago y los dos trabajadores involucrados (modelo y moderador).
- **Pago a empleado**: registro manual del monto liquidado a un trabajador vinculado a un cierre.

## Módulos y rutas

| Ruta raíz | Módulo | Notas |
|---|---|---|
| `/` | Dashboard | estadísticas + últimos reportes/cierres |
| `/reportes` | Reportes de pago | CRUD completo |
| `/cierres` | Cierres semanales | index / create / store / show / destroy |
| `/pagos` | Pagos a empleados | CRUD completo |
| `/trabajadores` | Trabajadores | CRUD completo |
| `/metodos` | Métodos de pago | CRUD completo |
| `/roles` | Roles | CRUD completo |

## Comandos útiles

- Servidor local: `php artisan serve`
- Migraciones: `php artisan migrate` / `php artisan migrate:fresh`
- Tests: `php artisan test` (usa SQLite en memoria; `*.env*` no requiere Supabase para los tests)
- Lint: `vendor\bin\pint` (listas de archivos explícitas si no hay git)
- Compilar vistas: `php artisan view:cache`

## Deploy a Laravel Cloud

1. Inicializar git en la raíz del proyecto y subir a GitHub.
2. En Laravel Cloud, crear una app apuntando al repo (stack PHP sin frontend build).
3. Configurar las variables de entorno en Laravel Cloud: `APP_KEY` (generada), `APP_ENV=production`, `APP_DEBUG=false`, y las variables `DB_*` de Supabase.
4. Migrar la BD remota una vez desde local: `php artisan migrate --force` contra Supabase.
5. Laravel Cloud corre el contenedor PHP automáticamente; no usa Nginx propio (frente manageado de Cloudflare).

## Notas

- El proyecto NO corre dentro de Cloudflare Workers; para eso se eligió Laravel Cloud.
- El valor `DB_PASSWORD` y `DB_HOST` quedaron marcados como reemplazables en `.env`; no deben subirse al repositorio (`.env` ya está en `.gitignore`).
- Para desarrollo local sin Supabase se puede cambiar temporalmente `DB_CONNECTION=sqlite` (el archivo `database/database.sqlite` ya existe localmente).