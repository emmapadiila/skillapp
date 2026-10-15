# SkillApp — SoftSkills AI

Aplicación web **multiempresa** para gestionar el ciclo completo de **habilidades blandas**
en una organización: catálogo de competencias, evaluaciones a colaboradores, diagnóstico
organizacional, brechas por área y planes de mejoramiento con seguimiento.

Construida con **Laravel 13**, **PostgreSQL (Supabase)**, **Supabase Auth**, **Blade**,
**Tailwind CSS 4** y **Vite**.

## Stack

| Componente | Tecnología |
|---|---|
| Backend | Laravel 13 · PHP `^8.3` |
| Base de datos | PostgreSQL en Supabase (esquema `laravel`) |
| Autenticación | Supabase Auth (JWT) + middleware propio |
| Frontend | Blade + Tailwind CSS 4 + Vite 8 + JS vanilla |
| Tests | PHPUnit 12 (+ test de navegador en `node:test`) |
| Formato | Laravel Pint |

## Documentación

La documentación completa está en [`docs/`](docs/README.md):

| Documento | Contenido |
|---|---|
| [Análisis detallado](docs/analisis-detallado.md) | Módulos, lenguajes y qué se evalúa |
| [Arquitectura](docs/arquitectura.md) | Capas, estructura y ciclo de una petición |
| [Base de datos](docs/base-de-datos.md) | Tablas, migraciones y modelo de datos |
| [API](docs/api.md) | Endpoints `api/v1`, roles, validación y errores |
| [Autenticación y seguridad](docs/autenticacion-seguridad.md) | Login, roles, policies, cabeceras |
| [Frontend](docs/frontend.md) | Blade, módulos JS y capa de datos |
| [Dominio y flujos](docs/dominio-flujos.md) | Evaluaciones, diagnóstico, planes, notificaciones |
| [Pruebas de habilidades blandas](docs/pruebas-habilidades-blandas.md) | Las tres pruebas exploratorias: contenido, uso y verificación |
| [Desarrollo y despliegue](docs/desarrollo-despliegue.md) | Configuración, Supabase, tests, producción |
| [Estado y pendientes](docs/estado-y-pendientes.md) | Cobertura, deuda técnica y roadmap |

## Estructura del proyecto

```text
skillapp/
├── app/
│   ├── Contracts/          Interfaces (AssessmentAnalyzer)
│   ├── Enums/              Enums de dominio
│   ├── Http/
│   │   ├── Controllers/    Api/V1 y Web
│   │   ├── Middleware/     Autenticación, roles y cabeceras
│   │   └── Requests/       Validación de entrada
│   ├── Models/             Modelos y relaciones de Eloquent
│   ├── Notifications/      Notificaciones de base de datos
│   ├── Policies/           Autorización por recurso
│   ├── Providers/          Binding de IA, rate limiters, Eloquent estricto
│   └── Services/           Lógica de negocio (evaluación, diagnóstico, auth)
├── database/
│   ├── factories/          Datos de prueba para modelos
│   ├── migrations/         Versionado de la base de datos
│   └── seeders/            Catálogos base
├── docs/                   Documentación del proyecto
├── resources/
│   ├── css/                Tailwind + componentes
│   ├── js/                 Workspace (JS vanilla)
│   └── views/              Plantillas Blade
├── routes/                 web.php, api.php, console.php
└── tests/                  Feature y Unit
```

## Responsabilidades

- `routes`: conecta URLs con controladores; no contiene lógica de negocio.
- `Controllers`: coordina autorización, validación y respuesta.
- `Requests`: valida entradas complejas.
- `Services`: encapsula operaciones de negocio reutilizables y transaccionales.
- `Policies` y middleware `role`: centralizan permisos y aislamiento por empresa.
- `Models`: representa datos, relaciones y conversiones.
- `resources/views`: presentación; no consulta directamente la base de datos.

## Puesta en marcha

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate
npm run build
composer run dev
```

La aplicación queda disponible en `http://localhost:8000`.

## PostgreSQL con Supabase

1. Crea un proyecto en Supabase.
2. Crea el esquema desde el SQL Editor:

```sql
create schema if not exists laravel;
```

3. En **Connect**, copia la cadena del **Session Pooler** (puerto `5432`), no la conexión
   directa (es solo IPv6).
4. Configura en `.env`:

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

5. La autenticación necesita además las API keys (en **Project Settings → API Keys**):

```dotenv
SUPABASE_URL=https://<PROJECT_REF>.supabase.co
SUPABASE_ANON_KEY=eyJ...
SUPABASE_SERVICE_ROLE_KEY=eyJ...
```

Las claves y contraseñas solo viven en `.env` (ignorado por git). Detalle completo y
solución de problemas en [docs/desarrollo-despliegue.md](docs/desarrollo-despliegue.md).

## Base de seguridad

- Autenticación delegada a Supabase Auth; el usuario local se resuelve por `supabase_user_id`
  o por correo, y debe estar activo.
- Aislamiento multiempresa con policies y filtros por `company_id` (recursos ajenos → `404`).
- Cabeceras HTTP de seguridad, Content Security Policy y HSTS activables por entorno.
- Límites de peticiones diferenciados para web, API y autenticación.
- Sesiones cifradas, cookies `HttpOnly` y `SameSite`.
- Eloquent estricto fuera de producción y bloqueo de comandos destructivos en producción.
- Pruebas sin conexiones HTTP externas accidentales.

## Comandos habituales

```bash
php artisan test --compact
php artisan migrate:status
php artisan db:seed
php artisan schedule:work
vendor/bin/pint --dirty --format agent
```

## Preparación para producción

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://tu-dominio.com
APP_TRUSTED_HOSTS=^tu-dominio\.com$
SESSION_SECURE_COOKIE=true
SECURITY_CSP_ENABLED=true
SECURITY_HSTS_ENABLED=true
```

El servidor web debe apuntar únicamente a `public/`. En cada despliegue:

```bash
composer install --no-dev --optimize-autoloader
npm ci
npm run build
php artisan migrate --force
php artisan optimize
composer audit
```
