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
DB_HOST=aws-0-<region>.pooler.supabase.com
DB_PORT=6543
DB_DATABASE=postgres
DB_USERNAME=postgres.<project-ref>
DB_PASSWORD=<contraseña>
DB_SSLMODE=require
```

Nota pooler: puerto **6543 = modo transacción** (conexiones cortas; recomendado para PHP) · **5432 = modo sesión** (conexiones persistentes; se satura fácilmente con forkeo de conexiones). La app usa 6543.

3. Las tablas del negocio ya existen en Supabase (creadas desde el panel); NO se vuelven a migrar. Para sincronizar Laravel:
   - `php artisan migrate:install` (crea la tabla `migrations`).
   - Registrar como aplicadas las migraciones de las tablas ya existentes o usar `php artisan migrate --force` para crear solo `users`/`sessions`/`cache`/`jobs` (con `SESSION_DRIVER=database` la tabla `sessions` es obligatoria).
4. NO ejecutar `db:seed` si ya hay datos reales en Supabase (sobrescribiría roles/métodos).

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

## Deploy gratis: Google Cloud e2-micro (always free)

InfinityFree (gratis) **no permite** conexiones salientes a BDs externas ni tiene `pdo_pgsql`; verificado en su foro. Opción gratis que sí funciona con Supabase: **VM e2-micro de Google Cloud** (level always-free: sin expiración; la tarjeta solo se pide para verificar, no se cobra dentro del límite).

1. Crear proyecto en Google Cloud Console y activar Billing (verificación con tarjeta, sin cargo).
2. Habilitar Compute Engine y crear una VM:
   - Máquina: **e2-micro** (solo en regiones `us-west1`, `us-central1` o `us-east1` para mantenerse en el nivel gratis).
   - Disco: 30 GB estándar. Sistema: **Ubuntu 24.04**.
3. Abrir tráfico HTTP/HTTPS (firewall GCP). Con `gcloud`:
   `gcloud compute firewall-rules create allow-http-https --allow tcp:80,tcp:443`
4. Subir el código:
   - Opción A (recomendada): subir el proyecto a GitHub y luego `git clone` en la VM.
   - Opción B: `rsync -av --exclude=.env --exclude=vendor --exclude=.git ./ usuario@IP:/var/www/nomina/`
5. Ejecutar el aprovisionamiento (SCRIPT: `deploy/server-setup.sh`):
   ```
   SERVER_DB_HOST='aws-0-us-west-2.pooler.supabase.com' \
   SERVER_DB_USERNAME='postgres.<project-ref>' \
   SERVER_DB_PASSWORD='<clave>' \
   REPO_URL='git@github.com:tu/nomina.git' \
   bash deploy/server-setup.sh
   ```
   El script instala Nginx + PHP-FPM + `pdo_pgsql` + Composer, crea `.env`, cachea config y deja la app en `/var/www/nomina`.
6. Si faltara algo en la BD remota: `cd /var/www/nomina && php artisan migrate --force`.
7. Para dominio propio + HTTPS gratis: instalar `certbot` y configurar `APP_URL` en `.env` (y re-cachear `config`).
8. Recordatorios: NO subir `.env` a git; NO ejecutar `db:seed` sobre datos reales.

## Deploy a Laravel Cloud

1. Inicializar git en la raíz del proyecto y subir a GitHub.
2. En Laravel Cloud, crear una app apuntando al repo (stack PHP sin frontend build).
3. Configurar las variables de entorno en Laravel Cloud: `APP_KEY` (generada), `APP_ENV=production`, `APP_DEBUG=false`, y las variables `DB_*` de Supabase.
4. Migrar la BD remota una vez desde local: `php artisan migrate --force` contra Supabase.
5. Laravel Cloud corre el contenedor PHP automáticamente; no usa Nginx propio (frente manageado de Cloudflare).

## Notas

- El proyecto NO corre dentro de Cloudflare Workers, **Vercel** ni **InfinityFree gratis** (Vercel y Workers no ejecutan PHP; InfinityFree bloquea BDs externas y no tiene pdo_pgsql).
- El host directo de Supabase (`db.<ref>.supabase.co:5432`) es **solo IPv6**; PHP y Vercel/Lambda (egress IPv4) necesitan el **connection pooler** (`aws-0-<region>.pooler.supabase.com`, usuario `postgres.<ref>`, puerto **6543** — modos: 6543 transacción / 5432 sesión).
- El valor `DB_PASSWORD` está en `.env`; no debe subirse al repositorio (`.env` ya está en `.gitignore`).
- Para desarrollo local sin Supabase se puede cambiar temporalmente `DB_CONNECTION=sqlite` (el archivo `database/database.sqlite` ya existe localmente).