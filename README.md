# SkillApp

Base organizada para desarrollar SkillApp con Laravel 13, PHP 8.5, Blade, Tailwind CSS y Vite.

## Estructura del proyecto

```text
skillapp/
├── app/
│   ├── Http/
│   │   └── Controllers/    # Reciben solicitudes y devuelven respuestas
│   ├── Models/             # Modelos y relaciones de Eloquent
│   └── Providers/          # Registro y arranque de servicios
├── bootstrap/              # Inicio y caché del framework
├── config/                 # Configuración de la aplicación
├── database/
│   ├── factories/          # Datos de prueba para modelos
│   ├── migrations/         # Versionado de la base de datos
│   └── seeders/            # Datos iniciales
├── public/                 # Punto de entrada y archivos públicos
├── resources/
│   ├── css/                # Estilos fuente
│   ├── js/                 # JavaScript fuente
│   └── views/              # Plantillas Blade
├── routes/
│   ├── console.php         # Comandos y tareas programadas
│   └── web.php             # Rutas web con sesión y CSRF
├── storage/                # Logs, caché y archivos generados
└── tests/
    ├── Feature/            # Flujos HTTP e integración
    └── Unit/               # Lógica aislada
```

La aplicación parte de la estructura estándar de Laravel. Las carpetas `Actions`, `Services`, `Policies`, `Jobs`, `Http/Requests` o `Http/Resources` se crearán con Artisan únicamente cuando una funcionalidad realmente las necesite. Así se evita comenzar con abstracciones vacías.

## Responsabilidades

- `routes`: conecta una URL con un controlador; no contiene lógica de negocio.
- `Controllers`: coordina la solicitud, autorización, validación y respuesta.
- `Requests`: valida y autoriza entradas complejas.
- `Models`: representa datos, relaciones, conversiones y consultas reutilizables.
- `Actions` o `Services`: encapsula operaciones de negocio reutilizables o complejas.
- `Policies`: centraliza permisos sobre recursos.
- `resources/views`: contiene presentación; no consulta directamente la base de datos.
- `tests/Feature`: protege el comportamiento que usa una persona desde la web.

## Base de seguridad

- Hosts permitidos para evitar ataques mediante el encabezado `Host`.
- Encabezados HTTP contra MIME sniffing, clickjacking y permisos innecesarios del navegador.
- Content Security Policy y HSTS activados automáticamente en producción.
- Límite general configurable de solicitudes web por usuario o dirección IP.
- Sesiones cifradas, cookies `HttpOnly` y política `SameSite`.
- Eloquent estricto fuera de producción para detectar N+1, atributos descartados y accesos inválidos durante el desarrollo.
- Bloqueo de comandos destructivos de base de datos en producción.
- Pruebas sin conexiones HTTP externas accidentales, esperas reales ni compilación innecesaria de Vite.

Estas defensas son una base. Cada futura operación deberá añadir validación con Form Requests, autorización con Policies, protección CSRF en formularios y límites específicos para inicio de sesión, recuperación de contraseña o endpoints costosos.

## Preparación para producción

Antes de publicar, configura como mínimo estas variables en el entorno del servidor:

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://tu-dominio.com
APP_TRUSTED_HOSTS=^tu-dominio\.com$
SESSION_SECURE_COOKIE=true
SECURITY_CSP_ENABLED=true
SECURITY_HSTS_ENABLED=true
```

El servidor web debe apuntar únicamente a `public/`. Durante cada despliegue ejecuta:

```powershell
composer install --no-dev --optimize-autoloader
npm ci
npm run build
php artisan migrate --force
php artisan optimize
php artisan reload
composer audit
```

## Puesta en marcha

Después de reiniciar la terminal para cargar PHP y Composer en el `PATH`:

```powershell
composer setup
composer run dev
```

La aplicación estará disponible normalmente en `http://localhost:8000`.

## PostgreSQL con Supabase

La conexión está preparada para PostgreSQL mediante el driver `pgsql`, SSL obligatorio y el esquema privado `laravel`. No se guardan claves ni contraseñas en el repositorio.

Antes de conectar la aplicación:

1. Crea un proyecto en Supabase.
2. Crea el esquema desde el SQL Editor de Supabase:

```sql
create schema if not exists laravel;
```

3. En el panel `Connect`, copia la cadena del **Session Pooler** que usa el puerto `5432`.
4. Completa solamente tu archivo local `.env`:

```dotenv
DB_CONNECTION=pgsql
DB_URL=postgres://postgres.PROJECT_REF:CONTRASENA@HOST_DEL_POOLER:5432/postgres
DB_SCHEMA=laravel
DB_SSLMODE=require
```

La contraseña debe codificarse para URL si contiene caracteres reservados. Nunca debe escribirse en `.env.example`, archivos PHP, JavaScript o Blade.

Cuando PHP tenga habilitado `pdo_pgsql` y la conexión esté configurada, ejecuta:

```powershell
php artisan config:clear
php artisan migrate:status
php artisan migrate
```

Las futuras tablas se definirán únicamente mediante migraciones en `database/migrations`. No se ha creado todavía ninguna tabla propia de la aplicación.

## Comandos habituales

```powershell
php artisan make:model Habilidad -mfsc
php artisan make:request StoreHabilidadRequest
php artisan make:policy HabilidadPolicy --model=Habilidad
php artisan make:test --phpunit HabilidadControllerTest
php artisan test --compact
vendor\bin\pint --format agent
```

Antes de crear una nueva capa, debe existir una necesidad concreta: reutilización, una frontera externa, lógica compleja o una mejora clara de pruebas.
