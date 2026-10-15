# Estado del proyecto y pendientes

## Estado por módulo

| Módulo | Estado | Notas |
|---|---|---|
| Autenticación (Supabase) | ✅ Completo | Login web + API, refresh, recuperación, invitaciones |
| Multiempresa / roles | ✅ Completo | Middleware, policies y filtros por `company_id` con tests |
| Catálogo (categorías, ejes, habilidades) | ✅ Completo | CRUD + relación N:M con ejes |
| Cargos y áreas | ✅ Completo | Peso y nivel requerido por competencia |
| Banco de preguntas | ✅ Completo | 3 tipos de pregunta, opciones con puntaje |
| Evaluaciones | ✅ Completo | Asignación automática, respuestas, resultados por habilidad/categoría |
| Diagnóstico organizacional | ✅ Completo | Matriz eje×categoría y cálculo de brechas con prioridad |
| Planes de mejoramiento | ✅ Completo | Actividades, recursos, progreso y estado |
| Notificaciones | ✅ Completo | Base de datos + scheduler |
| Frontend workspace | ✅ Funcional | SPA en JS vanilla; ver deuda abajo |
| **Análisis con IA** | ⚠️ **Stub** | `NullAssessmentAnalyzer` devuelve vacíos |
| Recursos de API (`Http/Resources`) | ⚠️ Pendiente | Los controladores devuelven modelos |
| Reportes/exportaciones | ⚠️ Parcial | Pantalla de reportes agregada en el frontend |

## Análisis con graphify

El grafo del proyecto (`graphify-out/graph.json`) resume el tamaño y la forma del código:

| Métrica | Valor |
|---|---|
| Nodos | 1.073 |
| Aristas | 2.629 (2.598 `EXTRACTED`, 31 `INFERRED`) |
| Comunidades | 79 (cohesión media 0.171) |
| Nodos aislados | 21 |

**Hubs arquitectónicos** (más conectados): `User` (85), `Evaluation` (42), `Company` (38),
`ImprovementPlan` (37), `Skill` (36), `Question` (32), `SupabaseAuthService` (19),
`EvaluationService` (14).

Distribución por área: `app/Http` 193 · `resources/js` 162 · `app/Models` 113 ·
`database/migrations` 66 · `database/factories` 65 · `app/Enums` 47 · `app/Services` 47 ·
`tests/Feature` 48.

Lectura del grafo:

- La **comunidad más grande** (108 nodos) es `resources/js`: el workspace es el módulo con
  más código y el de menor cohesión → ahí se concentra la complejidad real.
- Las capas están **poco acopladas entre sí** (cada comunidad agrupa una capa), lo que es
  sano: controladores, servicios y modelos se referencian de forma ordenada.

> Para regenerar el grafo: `graphify update .` y ábrelo con `graphify export html`.
> El visor queda en `graphify-out/graph.html` (no versionado).

## Deuda técnica

### 1. El análisis con IA es un stub

`AssessmentAnalyzer` está inyectado en `EvaluationService`, pero el único binding es
`NullAssessmentAnalyzer`, que devuelve `null`/vacíos. La funcionalidad se presenta en la UI
("Recomendaciones IA") pero **no produce contenido real**.

**Acción:** implementar un analizador real y cambiar el binding en `AppServiceProvider`.

### 2. Falta la capa de API Resources

Los controladores devuelven modelos Eloquent directamente (`response()->json(['data' => $model])`).
Esto acopla el contrato público al esquema y expone todos los atributos no ocultos.

**Acción:** crear `app/Http/Resources` para los modelos de salida principales.

### 3. Vistas y JS legacy

`resources/views/collaborators/index.blade.php`, `resources/views/diagnostics/organizational.blade.php`,
`resources/views/components/admin/*`, y los módulos `workspace.js`, `records.js`,
`collaborators.js`, `skills.js`, `dashboard.js` pertenecen a la primera versión del panel.
Las rutas ya usan `workspace.blade.php`, así que parte de ese código **no se ejecuta**
(`app.js` no importa `workspace.js` ni `records.js`).

**Acción:** eliminar lo no usado o marcarlo explícitamente como referencia.

### 4. Frontend sin tipado ni tests unitarios

≈1.500 líneas de JavaScript vanilla, sin tipos y con un único test de navegador
(`WorkspaceBrowser.test.mjs`). Es la zona de mayor riesgo de regresión.

**Acción:** añadir pruebas de los módulos (`platform.js`, `workspace-ui.js`) y considerar
tipado ligero (JSDoc/TypeScript) si el proyecto crece.

### 5. Credenciales de demo

Existen cuentas y contraseñas de prueba documentadas (ver
[desarrollo-despliegue.md](desarrollo-despliegue.md)). Deben eliminarse antes de un
despliegue real.

## Roadmap sugerido

1. Implementar `AssessmentAnalyzer` real (IA) y conectar `ai_recommendation`.
2. Añadir API Resources y contrato de errores uniforme.
3. Limpiar vistas/JS legacy.
4. Añadir cobertura al frontend y, opcionalmente, tipado.
5. Configurar scheduler y worker de colas en el entorno de despliegue.
