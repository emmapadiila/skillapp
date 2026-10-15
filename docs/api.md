# API REST

## Convenciones

- **Base:** `/api/v1`
- **Autenticación:** cabecera `Authorization: Bearer <access_token>` (token emitido por
  Supabase Auth). El middleware `supabase.auth` lo valida en cada petición.
- **Formato:** JSON. Las respuestas exitosas van envueltas en `{"data": ...}`.
- **Listados:** paginados con Laravel (`data`, `current_page`, `last_page`, `total`).
  El parámetro `per_page` se limita a `1..100` (por defecto `20`).
- **Errores:** código HTTP + `{"message": "...", ...}`; los errores de validación usan el
  formato estándar de Laravel `{"message", "errors": {"campo": ["..."]}}`.
- **Rate limiting:** grupos `throttle:auth` y `throttle:api` (ver
  [autenticacion-seguridad.md](autenticacion-seguridad.md)).

## Autenticación

Estas rutas no requieren token (solo `throttle:auth`):

| Método | Ruta | Descripción |
|---|---|---|
| `POST` | `/api/v1/auth/sign-in` | Inicia sesión; devuelve `session` (access/refresh token) y `user` |
| `POST` | `/api/v1/auth/refresh` | Renueva la sesión con `refresh_token` |
| `POST` | `/api/v1/auth/recover` | Solicita recuperación de contraseña (respuesta uniforme `202`) |

```bash
curl -X POST http://localhost:8000/api/v1/auth/sign-in \
  -H 'Accept: application/json' -H 'Content-Type: application/json' \
  -d '{"email":"admin@softskills.ai","password":"..."}'
```

Rutas protegidas (`supabase.auth` + `throttle:api`):

| Método | Ruta | Descripción |
|---|---|---|
| `GET` | `/api/v1/me` | Usuario actual con `company` y `position` |

## Roles

El middleware `role` restringe rutas completas:

- `role:superadmin`
- `role:superadmin,human_resources`
- Sin `role` → cualquier usuario autenticado (control fino por Policy)

## Catálogos

| Método | Ruta | Rol | Controlador |
|---|---|---|---|
| `GET` | `/api/v1/catalogs` | autenticado | `CatalogController@index` |
| `POST` | `/api/v1/catalogs/{catalog}` | `superadmin` | `CatalogController@store` |
| `PUT` | `/api/v1/catalogs/{catalog}/{id}` | `superadmin` | `CatalogController@update` |

`{catalog}` da de alta ítems en los catálogos globales (ejes y categorías).

## Empresas, colaboradores y estructura

| Método | Ruta | Rol |
|---|---|---|
| `GET/POST` | `/api/v1/companies` | `superadmin` |
| `GET/PUT/DELETE` | `/api/v1/companies/{company}` | `superadmin` |
| `GET/POST` | `/api/v1/employees` | `superadmin`, `human_resources` |
| `GET/PUT/DELETE` | `/api/v1/employees/{employee}` | `superadmin`, `human_resources` |
| `POST` | `/api/v1/employees/{employee}/invite` | `superadmin`, `human_resources` |
| `GET/POST` | `/api/v1/areas` | `superadmin`, `human_resources` |
| `GET/PUT/DELETE` | `/api/v1/areas/{area}` | `superadmin`, `human_resources` |
| `GET/POST` | `/api/v1/positions` | `superadmin`, `human_resources` |
| `GET/PUT/DELETE` | `/api/v1/positions/{position}` | `superadmin`, `human_resources` |

## Catálogo de habilidades y preguntas

| Método | Ruta | Rol |
|---|---|---|
| `GET/POST` | `/api/v1/skills` | `superadmin`, `human_resources` |
| `GET/PUT/DELETE` | `/api/v1/skills/{skill}` | `superadmin`, `human_resources` |
| `GET/POST` | `/api/v1/questions` | `superadmin`, `human_resources` |
| `GET/PUT/DELETE` | `/api/v1/questions/{question}` | `superadmin`, `human_resources` |

## Evaluaciones

| Método | Ruta | Rol / permiso |
|---|---|---|
| `GET/POST` | `/api/v1/evaluations` | autenticado (Policy) |
| `GET/PUT/DELETE` | `/api/v1/evaluations/{evaluation}` | Policy |
| `POST` | `/api/v1/evaluations/{evaluation}/responses` | respondiente de la evaluación |
| `POST` | `/api/v1/evaluations/{evaluation}/complete` | respondiente de la evaluación |

Un `collaborator` solo ve sus propias evaluaciones; los roles de gestión ven las de su
empresa; `superadmin` ve todas.

## Planes de mejoramiento

| Método | Ruta |
|---|---|
| `GET/POST` | `/api/v1/improvement-plans` |
| `GET/PUT/DELETE` | `/api/v1/improvement-plans/{improvementPlan}` |
| `POST` | `/api/v1/improvement-plans/{improvementPlan}/activities/{activity}/complete` |

## Diagnóstico organizacional

| Método | Ruta | Rol |
|---|---|---|
| `GET` | `/api/v1/diagnostics` | `superadmin`, `human_resources` |
| `POST` | `/api/v1/diagnostics` | `superadmin`, `human_resources` |
| `GET` | `/api/v1/diagnostics/{diagnostic}` | `superadmin`, `human_resources` |

## Notificaciones

| Método | Ruta |
|---|---|
| `GET` | `/api/v1/notifications` |
| `PATCH` | `/api/v1/notifications/{notification}/read` |

## Validación

Toda escritura se valida con un Form Request en `app/Http/Requests/Api/V1`. Ejemplos de
reglas relevantes:

- `StoreEvaluationRequest`: exige `question_count` **o** `question_ids` (mutuamente
  excluyentes), `due_at` futuro y coherencia de reevaluación con `parent_evaluation_id`.
- `StorePositionRequest`: define `skills[]` con `weight` (0.01–100) y `required_level` (1–5).
- `StoreImprovementPlanRequest`: permite crear actividades y recursos anidados en la misma
  petición.
- `StoreCatalogRequest`: `code` con `alpha_dash:ascii`.

## Códigos de estado

| Código | Cuándo |
|---|---|
| `200` / `201` | Éxito / recurso creado |
| `202` | Recuperación de contraseña (respuesta uniforme) |
| `401` | Token ausente, inválido o vencido |
| `403` | Sin rol/permiso; cuenta no habilitada |
| `404` | Recurso inexistente o de otra empresa (no revela existencia) |
| `422` | Error de validación o regla de negocio |
| `429` | Límite de peticiones excedido |
| `503` | Supabase Auth no disponible, o falta `service_role` para invitar |
