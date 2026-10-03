# Contexto del proyecto — Sistema de Nómina

## Descripción

Sistema de nómina para reportar pagos recibidos por plataformas (OnlyFans, etc.) y liquidar pagos a empleados por cierres semanales. Frontend en Blade (en app renderizada por el servidor), backend **Laravel 12** (PHP 8.2+), base de datos **PostgreSQL** hosteada en **Supabase**. Deploy objetivo: **Laravel Cloud** (PHP sobre la red de Cloudflare; no corre dentro de Cloudflare Workers, que es un runtime JS/WASM).

## Stack

- PHP 8.2 / Composer
- Laravel 12.69
- Blade (sin build de frontend; estilos en `resources/views/partials/styles.blade.php`, incluidos con `@include('partials.styles')` desde `layouts/app.blade.php` y `layouts/auth.blade.php`)
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
| `metodos_pago` | `id_mp` (int) | `metodo_pago`, `propietario?`, `porcentaje_cuenta?` — **sin `impuesto`** (el impuesto es global 15%; se quitó del modelo, la validación, el formulario y el listado, y con la migración `2026_10_02_120000_drop_impuesto_from_metodos_pago_table`) | — |
| `cierre_semanal` | `id_cierre` (int) | `fecha_inicio`, `fecha_fin`, `total?` | — |
| `pago_empleados` | `id_pago` (int) | `monto_bruto`, `monto_neto`, `deuda`, `monto_final`, `nota?` | `id_trab → trabajador`, `id_cierre → cierre_semanal` |
| `adelantos` | `id_adelanto` (int) | `tipo` (`adelanto`/`prestamo`), `monto`, `fecha`, `nota?` | `id_trab → trabajador` (cascade) |
| `abonos_adelanto` | `id_abono` (int) | `monto`, `fecha`, `nota?`, `id_pago?` | `id_adelanto → adelantos` (cascade), `id_pago? → pago_empleados` (cascade) |
| `reporte_pagos` | `id_reporte` (int) | `plataforma`, `user_cliente`, `precio`, `servicio`, `duracion?`, `fecha_reporte?`, `descripcion?`, **`comprobante?`** | `id_modelo → trabajador`, `id_moderador → trabajador`, `id_mp → metodos_pago`, `id_cierre? → cierre_semanal` |
| `detalle_pago_cierre` | `id_detalle` (int) | `concepto` (`modelo`/`moderador`/`pinto`/`admin`), `monto`, `total_antes_impuestos`, `total_despues_impuestos`, `nota?` | `id_cierre → cierre_semanal` (cascade), `id_trab? → trabajador` |

Nota: las PK usan `integer` (autoincrement) y las FK `unsignedInteger` para mantener consistencia de tipos en Postgres.

## Lógica de negocio

- **Cierre semanal** (`CierreSemanalController@store`): al crear un cierre con `fecha_inicio`/`fecha_fin`, se asignan automáticamente los reportes sin cierre (`id_cierre` NULL) cuya `fecha_reporte` cae dentro del período y se calcula `total = Σ precio`.
- **Reporte de pago**: registra un pago recibido del cliente, la plataforma, el método de pago y los dos trabajadores involucrados (modelo y moderador). El cierre semanal NO se selecciona en el formulario: lo asigna automáticamente el cierre según `fecha_reporte`.
- **Pago a empleado**: registro manual del monto liquidado a un trabajador vinculado a un cierre.
- **Comprobante de pago** (`reportes`): al crear/editar un reporte se puede adjuntar la imagen del comprobante. La imagen se guarda **en disco** (`storage/app/public/comprobantes`, enlazado en `/storage`) y la BD solo almacena la ruta (`reporte_pagos.comprobante`). Si GD está disponible se re-codifica a JPEG (máx. 1600px, calidad 72); si no, se guarda el original. Validación: imagen ≤ 4 MB (`jpeg/png/jpg/gif/webp`). Renombrar/eliminar el reporte borra el archivo del disco.
- **Comisión por cuenta** (`cierres.show`): el cierre agrupa sus reportes por `metodos_pago` y aplica `porcentaje_cuenta`: neto = `precio × (1 - pct/100)`, comisión = `precio × pct/100`. Impuesto global 15% (`CierreSemanalController::IMPUESTO_PORCENTAJE`) y el resto para Brea = impuestos − comisión total.
- **Pagos calculados** (`app/Support/CalculadorPagosCierre.php`): al generar un cierre se calcula y **guarda** en `detalle_pago_cierre` (tarjeta "Pagos calculados" en `cierres.show`; los cierres antiguos se regeneran al abrir su `show`). Flujo en dos pasos: (1) por trabajador se suma su **total de ventas** (Σ `precio` de sus reportes) y se muestra en la columna **total antes de impuestos**; ese número es solo informativo, no interviene en ningún cálculo. (2) A ese total de ventas se le descuenta únicamente el **15% de impuestos** (`precio − precio×0.15`; la comisión del método de pago NO se resta aquí porque ya se calcula en la tarjeta de comisiones por cuenta) y la suma por trabajador se muestra en **total después de impuestos**. Los porcentajes se aplican sobre ese número final ya agregado (ej.: 800 de ventas → 680 después de impuestos; María Brea 75% → 510). Modelo normal: 50% modelo / 20% moderador / 30% fondo administrativo. **María Brea (rol `ceo`) como modelo**: 75% Brea / 18% su moderador / 7% María Pinto (se busca por apellido "pinto"). Fondo administrativo: 20% rol `ceo`, 7% María Pinto, 1.5% **a cada** rol `admin`. Identificación de personas: rol `ceo`/`admin` + apellido `pinto`. Tests: `tests/Feature/CalculoPagosCierreTest.php`.

- **Adelantos ↔ Pagos**: el saldo pendiente de adelantos de un trabajador se descuenta automáticamente del pago (`PagoEmpleadoController@aplicarAdelantos`). Al guardar el pago, el `deuda` elegido se reparte **FIFO** (adelantos más antiguos primero) como abonos en `abonos_adelanto`, cada uno con `id_pago` = pago que lo generó; así el saldo de la sección de adelantos nunca queda duplicado. La operación es **idempotente**: en `update` primero se borran los abonos de ese pago (`$pago->abonosAdelanto()->delete()`) y se rehacen con la deuda nueva, y si se desmarca la casilla “Registrar el descuento como abono” no se crea ninguno (sirve para deudas/manuales). El formulario (`pagos/_form.blade.php`) trae los saldos como JSON y al elegir el trabajador llena `deuda` + `monto_final`; el botón “Usar saldo de adelantos” reaplica el valor. Ojo: la columna real en Supabase es `pago_empleados.deuda` (no `deuda_descontada`), y ambas deben coincidir con el `$fillable`/`casts` de `PagoEmpleado`. Tests: `tests/Feature/AdelantoTest.php` y `tests/Feature/PagoAdelantoTest.php`.

- **Adelantos y préstamos** (`/adelantos`, `adelantos.index`): módulo **solo para superroles** (`admin`, `ceo`, `support`); modelos y moderadores reciben redirect a `/`. Registra adelantos o préstamos a un trabajador (`tipo`, `monto`, `fecha`, `nota`) y lleva la cuenta de lo abonado en `abonos_adelanto` (`AbonoAdelantoController@store`, se registra inline desde el index con `POST /adelantos/{adelanto}/abonos`; se puede borrar con `DELETE /adelantos/abonos/{abono}`). El saldo **nunca** se guarda: `Adelanto@getSaldoAttribute` = `monto − abonos` (accesor `pagado` usa `withSum('abonos','monto')`); `saldado` = saldo ≤ 0. Al editar, el monto se sube al valor ya abonado si el usuario lo baja (`AdelantoController@validateData`). El index muestra 3 tarjetas (total entregado / abonado / saldo pendiente) y una tabla "Cuánto debe cada trabajador" (`AdelantoController@deudasPorTrabajador`, que reutiliza `Adelanto::saldosPorTrabajador()`; agregado en PHP porque un JOIN con los abonos duplicaría los montos). Para leer saldos desde otros módulos usar siempre `Adelanto::saldosPorTrabajador()` / `Adelanto::pendientePorTrabajador()` y **nunca** `pluck('id_adelanto','id_trab')` (colapsa claves duplicadas si hay varios adelantos del mismo trabajador). Tests: `tests/Feature/AdelantoTest.php`.

## Módulos y rutas

| Ruta raíz | Módulo | Notas |
|---|---|---|
| `/login` | Login | GET/POST email (protegido con `guest`); `/login/password` para contraseña |
| `/logout` | Logout | POST, invalida sesión |
| `/` | Dashboard | requiere `auth` |
| `/reportes` | Reportes de pago | CRUD completo |
| `/cierres` | Cierres semanales | index / create / store / show / destroy |
| `/pagos` | Pagos a empleados | CRUD completo |
| `/adelantos` | Adelantos y préstamos | CRUD + abonos; solo superroles |
| `/trabajadores` | Trabajadores | CRUD completo; **OJO** el resource usa `->parameters(['trabajadores' => 'trabajador'])`: sin eso Laravel genera `{trabajadore}` (singular de "trabajadores"), no hace model binding implícito, inyecta un modelo vacío y el `delete()` no borra nada (muestra éxito sin cambiar nada). `destroy` además bloquea borrarse a uno mismo y a quien tenga `pago_empleados` o `reporte_pagos` (FKs `ON DELETE RESTRICT` en Supabase) devolviendo `back()->withErrors('trabajador')` |
| `/metodos` | Métodos de pago | CRUD completo |
| `/roles` | Roles | CRUD completo |

## Acceso por roles

- Middleware `EnsureRole` (alias `role`): permite siempre a `admin`, `ceo`, `support` (const `Trabajador::SUPER_ROLES`, método `Trabajador@esSuperRol()`); los roles extra se pasan por argumento (`role:moderador`).
- Rutas (todas tras `auth` + `session.timeout`):
  - `reportes` → superroles + **moderador**.
  - **Moderador acotado**: `ReportePagoController@index` filtra por `id_moderador = auth()->id()` (`$soloMios`, que también oculta la columna "Moderador" y cambia el subtítulo), y `sinAcceso()` bloquea `edit`/`update`/`destroy` de reportes ajenos (redirect a `reportes.index` con `withErrors('access')`). En `store`/`update` el `id_moderador` del moderador se fuerza a su propio id, así no puede registrar reportes en nombre de otro. Los superroles ven y editan todo. Tests: `tests/Feature/ReportesPorModeradorTest.php`.
  - `cierres`, `pagos`, `adelantos`, `trabajadores`, `metodos`, `roles` → solo superroles.
  - `/` (dashboard) → todos (el contenido depende del rol).
- **Dashboard** (`DashboardController`): la tarjeta grande **"Ingresos sin cerrar"** (`.stat-destacado`, ocupa todo el ancho, cifra en 2.75rem) suma **solo reportes con `id_cierre` NULL** (`ReportePago::whereNull('id_cierre')->sum('precio')`) y debajo aclara "N reportes pendientes de cerrar"; por coherencia, la tabla **"Ingresos sin cerrar por método de pago"** (`$ingresosPorMetodo`) también filtra `whereNull('reporte_pagos.id_cierre')`. Los ingresos ya cerrados (con cierre) se ven en el módulo Cierres. Para moderador muestra sus ganancias reportadas (Σ `reporte_pagos.id_moderador = yo` + Σ `pago_empleados.id_trab = yo`); para `modelo` igual pero con `id_modelo`. Superroles ven el dashboard completo. Tests: `tests/Feature/DashboardIngresosTest.php` y `tests/Feature/DashboardRankingTest.php`. Además hay **dos tarjetas de ranking** lado a lado (`.grid-2`, una columna en móvil): "**Modelos que más venden**" y "**Moderadores que más venden**", cada una con el **top 5** (`DashboardController@ranking($columna)`: `SUM(precio)` y `COUNT(*)` de `reporte_pagos` con **`id_cierre` NULL**, agrupado por `id_modelo` / `id_moderador`, orden descendente, nombres resueltos con `Trabajador::nombre_completo`; CSS `.ranking`, el nº1 con círculo morado). Lista vacía: "No hay reportes pendientes de cerrar.".
- Sidebar (`layouts/app.blade.php`): moderador ve Dashboard + Reportes; modelo ve solo Dashboard (y Cerrar sesión); los superroles ven además Adelantos y préstamos, Pagos a empleados, Trabajadores, Métodos de pago y Roles.
- **PWA / Instalar como app**: la app se puede instalar desde el teléfono (Chrome/Edge muestran el prompt del navegador; el botón **"Instalar app"** de la topbar fuerza el prompt en Android y en iPhone/iPad muestra las instrucciones de *Compartir → Añadir a pantalla de inicio*). Archivos: `public/manifest.webmanifest` (`name` y `short_name`: **"Panda"** = etiqueta bajo el icono y nombre en Ajustes; `display: standalone`, iconos 192/512 + maskable en `public/icons/`, color `#7c3aed`), `public/sw.js` y `public/offline.html`; el `<head>` y el script van en el partial `resources/views/partials/pwa.blade.php` (incluido por `layouts/app` y `layouts/auth`). **El service worker nunca cachea páginas HTML** (sesión de 60 s + datos privados): solo iconos/manifiesto; si una navegación falla sin red devuelve `/offline.html`. Al tocar un archivo de `public/` hay que subir `sw.js` con la `VERSION` (`nomina-v4`) cambiada para forzar la actualización. **Iconos**: el original es `public/logo_panda.jpg` (2560x2560) y los derivados son `public/icons/icon-192.png`, `icon-512.png`, `icon-maskable-512.png` (la foto al 82% sobre el gris medio de la foto, para los máscaras del sistema), `apple-touch-icon.png` (180), `icon.svg` (PNG de 96 embebido) y `public/favicon.ico` (32, PNG dentro de ICO). Para regenerarlos desde el JPG hay que rehacerlos con `System.Drawing` en PowerShell (el PHP local **no tiene GD**). Al cambiar un icono hay que **desinstalar y reinstalar la app en el teléfono**, porque el icono se copia al instalarla. Tests: `tests/Feature/PwaTest.php`.
- **Errores**: `layouts/app.blade.php` muestra arriba un `.flash success` (session `success`) y un `.flash error` con `$errors->all()`, así que cualquier `back()->withErrors(...)` se ve en la página a la que vuelve (CSS `.flash.error` en `partials/styles.blade.php`).
- **Paginación**: NO usar el marcado por defecto de Laravel (`pagination::tailwind` deja flechas gigantes y texto "Previous/Next" en inglés porque aquí no hay Tailwind). Existe una vista propia en `resources/views/vendor/pagination/tailwind.blade.php`: `<nav class="page-links">` con flechas SVG de 14px (`aria-label` "Página anterior/siguiente", sin texto visible) y **solo números de página al centro** — todas si son ≤ 7 páginas, o una ventana de 5 alrededor de la actual (`$paginas`); sin puntos suspensivos ni palabras. Botones `.page-btn`, página actual `.is-active`, flechas inactivas `.is-disabled`. Contenedor `.pagination` centrado. Aplica a todas las vistas con `->paginate(15)` (`trabajadores`, `reportes`, `pagos`, `adelantos`, `cierres`; `roles` aún usa `get()`). Tests: `tests/Feature/PaginacionTest.php`.
- **Responsive**: en ≤ 860px el `.sidebar` pasa a cajón deslizable (botón `#navToggle`, overlay `#navBackdrop`, cierre con `Esc` o al pulsar un enlace) y el `.topbar` es `sticky`. **Toda tabla va envuelta en `<div class="table-wrap">`** (CSS en `partials/styles.blade.php`): el wrapper tiene `overflow-x: auto` y la tabla `min-width: 100%`, así que se adapta al ancho disponible y en celulares (≤ 860px, `white-space: nowrap`) se desliza a la derecha para verse completa sin romper el layout; los formularios pasan a una columna con `font-size: 1rem` (evita el zoom de iOS) y las tarjetas tienen `min-width: 0` para que no desbonden dentro de grids.
- **Dashboard** (`resources/views/dashboard.blade.php`): “Ingresos por método de pago” es una **tabla** (Método de pago / Ingresos / % del total / Participación con barra) envuelta en `.table-wrap`, igual que “Últimos reportes de pago”, “Últimos cierres semanales” y “Mis ganancias recientes”.
- **Modo oscuro**: variables CSS en `:root` (claro) y `[data-theme="dark"]`. Un script inline en el `<head>` aplica el tema guardado en `localStorage('nomina-tema')` antes de pintar; si no hay, usa `prefers-color-scheme`. El botón `#themeToggle` (ícono sol/luna SVG) alterna y persiste la preferencia.
- Prohibido → redirect a `/` con `withErrors('access')`.
- Tests: `tests/Feature/RoleAccessTest.php`.

## Autenticación

- Login por **email** contra `trabajador.email` (case-insensitive) en `LoginController`. Todo el sitio (menos `/login*`) está protegido con middleware `auth` (redirect a `route('login')` por defecto del framework).
- **Primer ingreso**: si el trabajador no tiene `password`, se le pide crearla dos veces y se guarda con `Hash::make`. Si ya tiene, se valida con `Hash::check`.
- Auth sobre el modelo `Trabajador` (implementa `Authenticatable` con el trait de Laravel); provider `users` (config/auth.php) apunta a `App\Models\Trabajador`. La columna `password` (nullable, hashed) vive en la migración `create_trabajador`.
- **Sesión por cookie simple**: `SESSION_DRIVER=database`, `SESSION_LIFETIME=1` (minuto) y `SESSION_EXPIRE_ON_CLOSE=false`. La cookie de Laravel expira a los 60 s de inactividad; no hay keepalive por JS ni middleware de timeout. El email pendiente del login vive en `session('auth_email')`.
- Tests: `tests/Feature/AuthFlowTest.php` (set de contraseña, login correcto/incorrecto, redirect de invitados).
- **Olvidé mi contraseña**: no hay correos ni códigos. En la pantalla `/login/password` el botón **"¿Olvidaste tu contraseña?"`** hace `POST /login/recuperar` (`LoginController@recuperarPassword`), que marca `session('crear_password')` y vuelve a la misma pantalla en modo "crear contraseña" (nueva + confirmación, `min:6`), igual que el primer ingreso; al guardar, entra al dashboard. Solo funciona con un email ya validado en sesión. Ojo: no hay verificación de identidad, así que quien conozca el email de un trabajador puede cambiarle la contraseña.
- Tests: `tests/Feature/AuthFlowTest.php` (set de contraseña, login correcto/incorrecto, redirect de invitados) y `tests/Feature/RecuperarPasswordTest.php` (creación de contraseña nueva).

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