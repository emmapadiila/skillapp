# Desarrollo y despliegue

## Requisitos

- PHP **8.3+** con `pdo_pgsql`, `mbstring`, `openssl`, `curl`.
- **Composer 2**
- **Node.js 20+** y **npm** (probado con Node 22 / npm 12).

## Configuración de Supabase

La aplicación necesita **dos** cosas distintas de Supabase: la **base de datos** (Postgres) y
la **autenticación** (API keys).

### 1. Esquema

Desde el SQL Editor de Supabase, una sola vez:

```sql
create schema if not exists laravel;
```

### 2. Cadena de conexión

En **Project Settings → Connect** elige **Session Pooler** (no la conexión directa: es
solo IPv6). Copia host, puerto, base y usuario, y completa `.env`:

```dotenv
DB_CONNECTION=pgsql
DB_HOST=aws-0-<region>.pooler.supabase.com
DB_PORT=5432
DB_DATABASE=postgres
DB_USERNAME=postgres.<PROJECT_REF>
DB_PASSWORD=<contraseña>
DB_SCHEMA=laravel
DB_SSLMODE=require
```

> Si la contraseña tiene caracteres reservados, codifícala para URL. Nunca la escribas en
> `.env.example` ni en código versionado.

### 3. API keys

En **Project Settings → API Keys → Legacy API keys** copia:

```dotenv
SUPABASE_URL=https://<PROJECT_REF>.supabase.co
SUPABASE_ANON_KEY=eyJ...        # rol "anon"  — necesaria para iniciar sesión
SUPABASE_SERVICE_ROLE_KEY=eyJ... # rol "service_role" — para invitar/crear usuarios
```

Verifica que la segunda no sea una copia de la primera: su payload debe decir
`"role":"service_role"`.

## Puesta en marcha

```bash
composer install
npm install
cp .env.example .env      # si no existe
php artisan key:generate
php artisan migrate
npm run build
composer run dev          # Laravel + Vite (+ logs/cola)
```

La aplicación queda en `http://localhost:8000`.

### Comandos habituales

```bash
php artisan serve              # servidor de desarrollo
npm run dev                    # Vite con HMR
composer run dev               # todo junto
php artisan migrate            # aplicar migraciones
php artisan migrate:status     # estado de migraciones
php artisan db:seed            # catálogos base (ejes y categorías)
php artisan schedule:work      # ejecutar el scheduler en local
```

## Crear usuarios

Un usuario necesita existir en **Supabase Auth** y en la tabla `users` con el mismo correo.

### Opción A — API admin (necesita `service_role`)

```bash
curl -X POST "$SUPABASE_URL/auth/v1/admin/users" \
  -H "apikey: $SUPABASE_SERVICE_ROLE_KEY" \
  -H "Authorization: Bearer $SUPABASE_SERVICE_ROLE_KEY" \
  -H "Content-Type: application/json" \
  -d '{"email":"usuario@empresa.com","password":"...","email_confirm":true}'
```

Luego crea la fila local (rol y empresa):

```php
App\Models\User::create([
    'email' => 'usuario@empresa.com',
    'name' => 'Usuario',
    'role' => App\Enums\UserRole::Collaborator,
    'company_id' => $companyId,
    'is_active' => true,
    'email_verified_at' => now(),
]);
```

Si la fila local existe con `supabase_user_id` en `null`, se enlaza por correo en el primer
login.

### Opción B — Panel de Supabase

**Authentication → Users → Add user** con "Auto Confirm User" activado, y crea la fila local
por separado.

### Cuentas de demo (entorno local)

| Rol | Correo | Contraseña |
|---|---|---|
| `superadmin` | `admin@softskills.ai` | `Admin.Skillapp2026*` |
| `collaborator` | `colaborador@softskills.ai` | `Colab.Skillapp2026*` |

> Son cuentas de prueba. Cámbialas o elimínalas antes de cualquier despliegue real, y no
> las documentes en un repositorio público.

## Tests

El proyecto usa **PHPUnit 12**. Los tests corren sobre **SQLite en memoria** (ver `phpunit.xml`)
y usan `Http::fake()` para no salir a internet.

```bash
php artisan test --compact                  # toda la suite
php artisan test --compact --filter=EvaluationWorkflowTest
vendor/bin/phpunit tests/Feature/ApiTenantIsolationTest.php
```

Cobertura destacada:

| Test | Qué protege |
|---|---|
| `ApiTenantIsolationTest` | Un usuario no accede a datos de otra empresa |
| `EvaluationWorkflowTest` | Ciclo completo asignar → responder → completar |
| `SecurityHeadersMiddlewareTest` | Cabeceras HTTP, CSP y HSTS |
| `WebRateLimitTest` | Límite de peticiones web (429) |
| `AuthenticatedSessionControllerTest` | Login, refresh, logout y errores |
| `WorkspaceTest` / `BrowserWorkspaceTest` | Navegación por rol y contrato de datos del workspace |
| `BackendSchemaTest` | El esquema puede migrarse |

También hay un test de navegador real (Chrome) en `tests/Unit/WorkspaceBrowser.test.mjs`
(`node:test`), que no forma parte de la suite PHPUnit.

## Formato de código

Se usa **Laravel Pint**:

```bash
vendor/bin/pint --dirty --format agent
```

## Producción

Antes de publicar, ajusta como mínimo:

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://tu-dominio.com
APP_TRUSTED_HOSTS=^tu-dominio\.com$
SESSION_SECURE_COOKIE=true
SECURITY_CSP_ENABLED=true
SECURITY_HSTS_ENABLED=true
```

El servidor web debe apuntar **solo** a `public/`. En cada despliegue:

```bash
composer install --no-dev --optimize-autoloader
npm ci
npm run build
php artisan migrate --force
php artisan optimize
composer audit
```

En producción también debes correr el **scheduler** (cron cada minuto:
`* * * * * php artisan schedule:run`) y, si usas colas, un worker
(`php artisan queue:work`). Hoy la cola configurada es `sync`.

## Problemas conocidos

| Síntoma | Causa / solución |
|---|---|
| `could not translate host name "db..."` o `Network is unreachable` | Usaste la conexión directa (IPv6). Cambia al **Session Pooler**. |
| `tenant/user postgres.<ref> not found` | El host del pooler corresponde a otra región. |
| `relation "migrations" does not exist` | Falta el esquema `laravel` o `DB_SCHEMA` no coincide. |
| `Supabase Service Role no está configurado` | Falta `SUPABASE_SERVICE_ROLE_KEY` (invitar usuarios). |
| Las credenciales no inician sesión | `SUPABASE_ANON_KEY` incorrecta o el usuario existe en `users` pero no en Supabase Auth. |
| `Unable to locate file in Vite manifest` | Ejecuta `npm run build` o levanta `npm run dev`. |
| `tenant/user not found` con la contraseña correcta | Usuario del pooler incorrecto: debe ser `postgres.<PROJECT_REF>`. |
