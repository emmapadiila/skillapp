# Análisis detallado del aplicativo

Este documento describe **cómo está construido** SkillApp, **de qué trata cada módulo**,
**en qué lenguaje está hecho** y **qué se evalúa** en la plataforma.

## 1. Resumen ejecutivo

SkillApp es una aplicación web **cliente-servidor** y **multiempresa** para evaluar y
desarrollar **habilidades blandas** (soft skills) en organizaciones. El servidor usa Laravel
13 y PHP (`^8.3`), PostgreSQL en Supabase y autenticación delegada a Supabase Auth mediante
tokens JWT. El cliente combina Blade, Tailwind CSS 4 y JavaScript vanilla; el *workspace*
funciona como una *single page application* ligera que consume una API REST propia.

El flujo funcional completo es:

1. Se define un **catálogo** de habilidades agrupadas por categoría y por eje organizacional.
2. Se crean **cargos** que exigen ciertas habilidades con un **nivel requerido**.
3. Se **asignan evaluaciones** a los colaboradores (las preguntas se eligen según el cargo).
4. El colaborador **responde**; al completar se **calculan resultados** por habilidad y categoría.
5. Con las evaluaciones del período se genera un **diagnóstico organizacional** (matriz y brechas).
6. Las brechas alimentan **planes de mejoramiento** con actividades y recursos, y su seguimiento.

## 2. Cómo se construyó el aplicativo

### 2.1 Enfoque

El desarrollo siguió un enfoque **incremental por capas**, típico de Laravel:

1. **Estructura y base de datos** primero (migraciones versionadas).
2. **Backend de dominio** (modelos, enums, requests, policies, servicios y API).
3. **Frontend** después (Blade, componentes y el workspace en JavaScript).

La lógica de negocio compleja se aisló en **servicios** (`EvaluationService`,
`DiagnosticService`) para que los controladores solo coordinen, y los permisos se
centralizaron en **policies** y en un middleware de roles.

La reconstrucción de cómo se hizo se basa en el historial Git, la estructura actual y las
pruebas del repositorio: primero se consolidaron migraciones y dominio, después autenticación,
servicios y API, y finalmente el workspace. En ejecución, una pantalla Blade entrega la
cáscara; el JavaScript consulta la API; middleware y policies autorizan la petición; el
controlador valida/orquesta el trabajo; los servicios aplican reglas de dominio y Eloquent
persiste en PostgreSQL. El frontend no accede directamente a la base de datos.

### 2.2 Herramientas utilizadas

- **Laravel Boost**: reglas del proyecto y skills de dominio (Laravel, tests, Tailwind).
- **Laravel Pint**: formato automático de código PHP.
- **PHPUnit 12**: pruebas automatizadas del backend; también hay un test de navegador con
  `node:test`.
- **graphify**: análisis del grafo de código para entender acoplamientos y comunidades.
- **Vite 8**: empaquetado del frontend.

### 2.3 Historia del desarrollo (commits)

| Fecha | Commit | Qué aporta |
|---|---|---|
| 2026-08-29 | `33d1016` Estructura completa de Laravel, PHP y base de datos | Esqueleto del proyecto y migraciones base |
| 2026-08-29 | `463606f` backend: API segura multiempresa con Supabase y evaluaciones | Modelos, enums, requests, policies, servicios y API REST |
| 2026-08-31 | `388ea9f` Creacion de DB en PostgreSQL (B. Durán) | Ajuste de la conexión a PostgreSQL/Supabase |
| 2026-09-15 | `ada967a` primera parte del frontend | Primeras vistas Blade y componentes |
| 2026-09-29 | `3befae9` collaborators and organizational diagnosis dashboards | Paneles de colaboradores y diagnóstico |
| 2026-09-29 | `dbd5c35` nuevas vistas y actualiza frontend | Ampliación de pantallas |
| 2026-10-03 | `fc765a6` implementa frontend completo | Workspace SPA, estilos y navegación por rol |

Se observa una clara separación temporal: **agosto = backend y datos**, **septiembre–octubre =
frontend**.

## 3. Lenguajes y proporción del código

Recuento del código fuente del repositorio, excluyendo dependencias, artefactos de compilación,
cachés, documentación y archivos generados:

| Lenguaje | Líneas | Archivos | Proporción | Para qué se usa |
|---|---:|---:|---:|---|
| **PHP** | 6.703 | 141 | 70,5 % | Backend, rutas, configuración, migraciones y pruebas |
| **JavaScript** | 1.702 | 15 | 17,9 % | Workspace, prueba de navegador y configuración de Vite |
| **CSS** | 551 | 1 | 5,9 % | Tema y componentes de Tailwind |
| **Blade** | 548 | 13 | 5,9 % | Plantillas HTML renderizadas en el servidor |

El recuento es por líneas de archivo, no por líneas ejecutables; incluye pruebas y configuración
propias del repositorio, así que sirve como referencia de tamaño y no como medida de esfuerzo.

**Conclusión de lenguajes:** es un proyecto **PHP/Laravel dominante** (aproximadamente siete
décimas partes del código), con un frontend propio pero secundario en volumen. No hay TypeScript, ni React/Vue,
ni un framework SPA: la interactividad se resuelve con JavaScript nativo.

Desglose del PHP por capa:

| Capa | Líneas |
|---|---:|
| Controladores (`app/Http/Controllers`) | 1.065 |
| Modelos (`app/Models`) | 770 |
| Servicios (`app/Services`) | 468 |
| Form Requests (`app/Http/Requests`) | 329 |
| Policies (`app/Policies`) | 164 |
| Middleware (`app/Http/Middleware`) | 138 |
| Enums (`app/Enums`) | 92 |
| Notificaciones (`app/Notifications`) | 87 |
| Providers (`app/Providers`) | 55 |
| Contratos (`app/Contracts`) | 9 |

## 4. Análisis módulo por módulo

Cada módulo indica **qué es**, **dónde vive**, **qué hace**, **cómo se hizo** y **qué tablas**
usa.

### 4.1 Autenticación y sesión

- **Qué es:** el acceso al sistema, tanto para web como para la API.
- **Dónde:** `app/Services/AuthenticationService.php`, `app/Services/SupabaseAuthService.php`,
  `app/Http/Middleware/AuthenticateSupabase.php`,
  `app/Http/Controllers/Web/AuthenticatedSessionController.php`,
  `app/Http/Controllers/Api/V1/AuthController.php`.
- **Qué hace:** delega la verificación de credenciales a Supabase Auth, valida el token
  Bearer en cada petición de API, resuelve el usuario local y gestiona el refresh de sesión.
- **Cómo se hizo:** Supabase es el proveedor externo de identidad; `SupabaseAuthService`
  centraliza las llamadas HTTP y `AuthenticationService` enlaza la identidad externa con el
  usuario local. `AuthenticateSupabase` establece el usuario autenticado para la petición.
  Las contraseñas no se almacenan en la tabla local.
- **Tablas:** `users` (con `supabase_user_id`), `sessions`.
- **Límite relevante:** una identidad válida sin usuario local activo no recibe acceso a la
  plataforma; si el servicio de autenticación no responde, la API devuelve un error de servicio.

### 4.2 Empresas y estructura organizacional

- **Qué es:** el soporte **multiempresa** y la jerarquía interna (áreas y cargos).
- **Dónde:** `CompanyController`, `EmployeeController`, `AreaController`,
  `PositionController`, `CompanyPolicy` y solicitudes de validación asociadas.
- **Qué hace:** administra empresas, registra e invita usuarios locales, organiza áreas y
  cargos, asigna a cada colaborador un cargo y define las habilidades requeridas.
- **Cómo se hizo:** valida que colaborador, área, cargo y empresa correspondan al mismo
  tenant. El cargo relaciona habilidades mediante `position_skill`, que guarda
  `required_level` y `weight`. El peso se almacena, pero el cálculo de resultados y brechas
  observado no lo aplica.
- **Tablas:** `companies`, `areas`, `positions`, `position_skill`.

### 4.3 Catálogo de habilidades

- **Qué es:** la taxonomía de competencias que se evalúan.
- **Dónde:** `SkillController`, `CatalogController`, `app/Models/Skill.php`,
  `app/Models/SkillCategory.php`, `app/Models/OrganizationalAxis.php`.
- **Qué hace:** agrupa habilidades por **categoría** y las clasifica por **eje**
  organizacional (relación de muchos a muchos).
- **Cómo se hizo:** categorías y ejes son catálogos globales. Las habilidades pueden ser
  compartidas (`company_id` nulo) o propias de una empresa; un cargo puede usar habilidades
  de ambos tipos.
- **Tablas:** `skills`, `skill_categories`, `organizational_axes`, `skill_axis`.

### 4.4 Banco de preguntas

- **Qué es:** el instrumento de medición.
- **Dónde:** `QuestionController`, `app/Models/Question.php`, `app/Models/QuestionOption.php`.
- **Qué hace:** administra preguntas por habilidad, con tipo, dificultad y opciones de
  respuesta con puntaje.
- **Cómo se hizo:** cada pregunta pertenece a una habilidad, tiene dificultad, puede asociarse
  a un eje y se activa o desactiva. `self_report` usa Likert; `situational` y
  `situational_judgment` requieren al menos dos opciones con puntajes de 0 a 5. Las preguntas
  asociadas a evaluaciones no se pueden editar ni eliminar: deben versionarse.
- **Tablas:** `questions`, `question_options`.
- **Disponibilidad actual:** `DatabaseSeeder` no contiene preguntas; los factories y las
  pruebas crean datos ficticios para testear. Para que la asignación automática funcione,
  la empresa debe tener preguntas activas asociadas a las habilidades del cargo.

### 4.5 Evaluaciones

- **Qué es:** el núcleo del aplicativo.
- **Dónde:** `EvaluationController`, `app/Services/EvaluationService.php`,
  `StoreEvaluationRequest`, `EvaluationPolicy`.
- **Qué hace:** asigna evaluaciones, permite responderlas y las cierra calculando resultados.
- **Cómo se hizo:** `EvaluationService` asigna preguntas (aleatorias si no se indican IDs),
  valida empresa, estado y tipo de respuesta, y permite corregir respuestas mientras la
  evaluación está abierta. Exige responderlas todas para completarla; calcula promedios y
  niveles y persiste resultados inmutables. La finalización usa transacción y bloqueo para
  evitar cálculos concurrentes duplicados.
- **Privacidad de puntajes:** el controlador oculta a los colaboradores el puntaje de las
  opciones y el puntaje de sus respuestas, para no revelar la clave de corrección.
- **Selección automática:** usa habilidades del cargo y preguntas activas de esa empresa;
  admite preguntas sin eje o del eje del cargo. Si no hay suficientes preguntas para
  `question_count`, la asignación falla. Aunque las habilidades sí pueden ser globales, la
  pregunta tiene `company_id` obligatorio y la selección exige que coincida con la empresa.
- **Tablas:** `evaluations`, `evaluation_questions`, `evaluation_responses`,
  `evaluation_results`, `skill_results`, `category_results`.
- **Reevaluación:** exige una evaluación completada previa del mismo colaborador y guarda el
  vínculo en `parent_evaluation_id`.

### 4.6 Diagnóstico organizacional

- **Qué es:** la lectura agregada del estado de la organización.
- **Dónde:** `DiagnosticController`, `app/Services/DiagnosticService.php`.
- **Qué hace:** con las evaluaciones completadas de un período construye una **matriz**
  (eje × categoría) y detecta **brechas** por habilidad/área con prioridad.
- **Cómo se hizo:** usa evaluaciones completadas del rango solicitado y conserva una foto
  del período. La matriz agrupa respuestas con eje por eje y categoría. Las brechas comparan
  cada resultado de habilidad con el nivel requerido del cargo y agrupan por habilidad, eje
  del cargo y área; solo se persisten brechas positivas.
- **Límites:** las celdas dependen de que las preguntas tengan eje configurado; preguntas sin
  eje no entran a la matriz. No se permite duplicar diagnóstico para la misma empresa y período.
- **Tablas:** `organizational_diagnostics`, `diagnostic_matrix_cells`, `organizational_gaps`.

### 4.7 Planes de mejoramiento

- **Qué es:** el plan de acción individual.
- **Dónde:** `ImprovementPlanController`, `ImprovementPlanPolicy`.
- **Qué hace:** liga un colaborador con una habilidad objetivo, actividades y recursos;
  calcula el progreso al completar actividades.
- **Cómo se hizo:** el plan relaciona colaborador, habilidad objetivo, niveles, fechas,
  actividades y recursos. El colaborador propietario completa sus actividades; el porcentaje
  se recalcula como actividades terminadas sobre el total. La asignación dispara una
  notificación.
- **Tablas:** `improvement_plans`, `improvement_activities`, `recommended_resources`.

### 4.8 Notificaciones y tareas programadas

- **Qué es:** los avisos del sistema y las rutinas automáticas.
- **Dónde:** `app/Notifications/*`, `routes/console.php`.
- **Qué hace:** notifica asignaciones y recordatorios, expira evaluaciones vencidas y envía
  recordatorios de reevaluación.
- **Cómo se hizo:** notificaciones de base de datos y el *scheduler* de Laravel con tareas
  sin solapamiento.
- **Tipos actuales:** notificación de evaluación asignada, plan de mejoramiento asignado y
  reevaluación próxima.
- **Tablas:** `notifications`.

### 4.9 Frontend (workspace)

- **Qué es:** la interfaz de usuario con la que interactúan administradores y colaboradores.
- **Dónde:** `resources/views/workspace.blade.php`, `resources/js/*`, `resources/css/app.css`.
- **Qué hace:** renderiza una pantalla u otra según el rol, carga datos por API y permite
  crear, editar y filtrar registros.
- **Cómo se hizo:** `workspace.blade.php` aporta la cáscara y los metadatos de sesión.
  `workspace-app.js` resuelve pantalla, carga de recursos y navegación; `workspace-admin.js`
  y `workspace-personal.js` renderizan las vistas por rol; `workspace-forms.js` maneja
  formularios y acciones. `workspace-ui.js` y `data/platform.js` centralizan utilidades
  visuales y acceso a la API.
- **Acceso a datos:** `data/platform.js` agrega el token Bearer, limita el tiempo de espera,
  renueva la sesión ante un `401` y reintenta una vez; también pagina listas y normaliza
  mensajes de error. Los módulos no ejecutan consultas SQL desde el navegador.
- **Pantallas:** gestión de colaboradores, habilidades, preguntas, cargos, evaluaciones,
  diagnóstico, mapa de calor, brechas, recomendaciones, planes, seguimiento, reportes, áreas
  y empresas; espacio personal con inicio, evaluación, resultados, plan, progreso, perfil y
  notificaciones.
- **Código legado:** `app.js` sí importa `collaborators.js`, `skills.js` y `dashboard.js`;
  `workspace.js` y `records.js` no se importan. Las rutas actuales usan `workspace.blade.php`,
  no las vistas antiguas de colaboradores, diagnóstico y componentes `admin`.
- **Tablas:** todas (consume la API).

### 4.10 Seguridad transversal

- **Qué es:** defensa aplicada a todos los módulos.
- **Dónde:** `app/Http/Middleware/*`, `app/Policies/*`, `config/security.php`,
  `app/Providers/AppServiceProvider.php`.
- **Qué hace:** roles, autorización por recurso, aislamiento multiempresa, cabeceras HTTP,
  CSP/HSTS y límites de peticiones.
- **Cómo se hizo:** defensa en profundidad (middleware + policies + filtros de consulta).
  Los roles tipados son `superadmin`, `human_resources` y `collaborator`; hay límites de
  solicitudes separados para autenticación, API y web. El middleware agrega cabeceras de
  seguridad, CSP para HTML y HSTS en solicitudes HTTPS cuando están habilitados en config.

### 4.11 API y validación de entrada

- **Dónde:** `routes/api.php`, `app/Http/Controllers/Api/V1/*` y
  `app/Http/Requests/Api/V1/*`.
- **Qué contiene:** endpoints `api/v1` para autenticación, catálogos, empresas, colaboradores,
  áreas, cargos, habilidades, preguntas, evaluaciones, planes, diagnósticos y notificaciones.
- **Cómo se hizo:** las rutas conectan middleware, controladores, policies y servicios.
  Los Form Requests validan los datos de los flujos principales; las respuestas se serializan como
  JSON con paginación de Laravel. Varios controladores devuelven modelos Eloquent directamente;
  no hay una capa común `Http/Resources`.

| Controlador API | Función principal |
|---|---|
| `AuthController` | Inicio de sesión, renovación, recuperación y usuario actual |
| `CatalogController` | Lectura y administración superadmin de ejes y categorías |
| `CompanyController` | CRUD de empresas (solo superadmin) |
| `EmployeeController` | CRUD, aislamiento de empleados e invitación de usuarios |
| `AreaController` | CRUD de áreas ligadas a la empresa |
| `PositionController` | CRUD de cargos, eje y habilidades requeridas |
| `SkillController` | CRUD de habilidades y ejes aplicables |
| `QuestionController` | CRUD del banco; protege preguntas ya usadas |
| `EvaluationController` | Asignación, consulta, respuesta, finalización y reprogramación |
| `DiagnosticController` | Generación y lectura de diagnósticos del período |
| `ImprovementPlanController` | Planes, actividades y recursos; marcar actividad completada |
| `NotificationController` | Listado y marcado de notificaciones como leídas |

### 4.12 Persistencia y modelo de datos

- **Dónde:** `database/migrations`, `app/Models` y `database/factories`.
- **Qué contiene:** 11 migraciones del repositorio (incluidas las tablas base de Laravel) y
  21 modelos Eloquent con relaciones, casts tipados, índices y claves foráneas.
- **Cómo se hizo:** PostgreSQL en Supabase, esquema `laravel`; relaciones y tablas pivote
  representan el dominio. Las migraciones son la fuente versionada de la estructura.
- **Carga inicial:** `DatabaseSeeder` crea únicamente ejes y categorías. Los factories
  generan información de prueba; no constituyen un catálogo de negocio para producción.

### 4.13 Calidad, pruebas y operación

- **Pruebas:** `tests/Feature` cubre esquema, login, aislamiento multiempresa, cabeceras,
  límites de solicitudes, ciclo de evaluación y contrato del workspace para administrador y
  colaborador. `tests/Unit` contiene el test de ejemplo PHPUnit; existe, además, una prueba de
  navegador ejecutada con `node:test`.
- **Calidad:** PHPUnit 12 y Laravel Pint. Eloquent estricto se activa fuera de producción para
  detectar consultas o atributos inválidos.
- **Operación:** `routes/console.php` programa expiración horaria de evaluaciones vencidas y
  recordatorios diarios de reevaluación; usa zona horaria y hora configuradas y protección
  contra ejecuciones simultáneas. Requiere que el scheduler esté activo.

## 5. Qué se evalúa

### 5.1 Objeto de evaluación

La plataforma evalúa **habilidades blandas** (competencias transversales) de los
colaboradores de una empresa. No mide conocimientos técnicos ni desempeño productivo, sino
competencias conductuales organizadas en dos dimensiones:

- **Categorías de habilidad** (qué tipo de competencia es).
- **Ejes organizacionales** (el nivel del rol: operativo, misional, estratégico).

El seeder base (`DatabaseSeeder`) carga:

| Dimensión | Valores iniciales |
|---|---|
| Ejes (`organizational_axes`) | Operativo, Misional, Estratégico |
| Categorías (`skill_categories`) | Comunicativas, Colaborativas, Cognitivas, Dirección |

No carga empresas, cargos, habilidades ni preguntas. Estas entidades deben configurarse para
cada organización antes de asignar evaluaciones. Por tanto, las categorías y ejes vienen
precargados; el instrumento de evaluación no viene poblado con preguntas listas para uso.

### 5.2 El instrumento

La medición se hace con **preguntas** asociadas a una habilidad y, opcionalmente, a un eje:

| Tipo de pregunta | Cómo se responde | Cómo se puntúa |
|---|---|---|
| `self_report` (Autorreporte) | Escala Likert de 1 a 5 | El valor elegido (1–5) |
| `situational` (Situacional) | Selección de una opción | El puntaje de la opción elegida (0–5) |
| `situational_judgment` (Juicio situacional) | Selección de una opción | El puntaje de la opción elegida (0–5) |

Cada pregunta tiene una **dificultad** (`basic`, `intermediate`, `advanced`). El campo de
puntaje de una opción acepta valores de **0 a 5**; el autorreporte acepta **1 a 5**. Las
preguntas situacionales requieren al menos dos opciones y deben tener puntajes asignados.

#### Propuesta de preguntas iniciales

Las siguientes preguntas son ejemplos nuevos para poblar y probar el banco: **no están
incluidas en `DatabaseSeeder` ni se presentan como datos que ya existan en la base**. Antes de
usarlas con colaboradores, hay que vincular cada una con una habilidad concreta, asignar
empresa, eje y dificultad apropiados, y validar redacción y puntuación con responsables de
talento humano. No constituyen un instrumento psicométrico validado.

Para las preguntas de autorreporte se propone esta escala de frecuencia:

| Valor | Anclaje sugerido |
|---:|---|
| 1 | Nunca o casi nunca |
| 2 | Pocas veces |
| 3 | Algunas veces |
| 4 | Frecuentemente |
| 5 | Casi siempre |

| Categoría | Habilidad sugerida | Tipo / dificultad | Pregunta propuesta |
|---|---|---|---|
| Comunicativas | Escucha activa | `self_report` / `basic` | Cuando alguien explica una idea diferente a la mía, escucho antes de responder. |
| Colaborativas | Cooperación | `self_report` / `basic` | Comparto información y avances con las personas que necesitan colaborar conmigo. |
| Cognitivas | Resolución de problemas | `self_report` / `intermediate` | Antes de proponer una solución, identifico las causas y la información que me falta. |
| Dirección | Toma de decisiones | `self_report` / `intermediate` | Al tomar una decisión que afecta al equipo, considero sus consecuencias y explico los motivos. |

Ejemplos de **juicio situacional** con opciones y puntajes ilustrativos. En la aplicación cada
opción se guarda con su texto, etiqueta y puntaje; solo se registra la opción elegida:

**Comunicativas — escucha activa** (`situational`, `basic`): en una reunión, una persona
cuestiona una propuesta que usted hizo.

| Opción | Respuesta propuesta | Puntaje de ejemplo |
|---|---|---:|
| A | Interrumpo para defender mi propuesta antes de conocer el argumento completo. | 1 |
| B | Escucho el comentario y continúo con el siguiente punto sin aclarar la inquietud. | 2 |
| C | Pido que explique qué aspecto le preocupa y respondo a ese punto. | 4 |
| D | Parafraseo la inquietud para confirmar que la entendí y luego comparo alternativas con el grupo. | 5 |

**Colaborativas — cooperación** (`situational_judgment`, `intermediate`): un compañero
solicita apoyo urgente cuando usted tiene tareas propias con plazo cercano.

| Opción | Respuesta propuesta | Puntaje de ejemplo |
|---|---|---:|
| A | Acepto todo el trabajo adicional y dejo mis tareas sin avisar a nadie. | 1 |
| B | Rechazo la solicitud sin explicar mi disponibilidad ni explorar alternativas. | 2 |
| C | Aclaro mis prioridades y acuerdo con el compañero qué apoyo puntual puedo ofrecer. | 4 |
| D | Revisamos juntos urgencia y responsabilidades; si el impacto supera nuestra capacidad, avisamos al responsable para redistribuir. | 5 |

**Cognitivas — resolución de problemas** (`situational`, `intermediate`): un indicador
importante empeora durante dos semanas, pero el equipo aún no conoce la causa.

| Opción | Respuesta propuesta | Puntaje de ejemplo |
|---|---|---:|
| A | Cambio varias cosas de inmediato para intentar detener el empeoramiento. | 1 |
| B | Espero a que el indicador se corrija sin revisar los datos disponibles. | 1 |
| C | Comparo períodos y fuentes, formulo posibles causas y verifico cuál explica mejor el cambio. | 5 |
| D | Consulto a una persona experta y aplico su primera sugerencia sin contrastarla con la evidencia. | 3 |

**Dirección — toma de decisiones** (`situational_judgment`, `advanced`): debe decidir entre
dos alternativas con impacto en el equipo y dispone de información incompleta.

| Opción | Respuesta propuesta | Puntaje de ejemplo |
|---|---|---:|
| A | Decido de inmediato sin comunicar criterios ni incertidumbres. | 1 |
| B | Aplazo la decisión indefinidamente hasta tener certeza total. | 2 |
| C | Comparo riesgos, solicito el dato crítico que falta y comunico plazo y criterios de decisión. | 4 |
| D | Evalúo impacto y reversibilidad, consulto a las personas afectadas y tomo una decisión documentando supuestos y seguimiento. | 5 |

Los puntajes anteriores ilustran el formato compatible con el sistema; deben revisarse para
que las alternativas midan el comportamiento esperado del cargo y no una supuesta respuesta
universalmente correcta.

### 5.3 Escala y niveles

El puntaje es el **promedio aritmético de las respuestas** en una escala objetivo de 0 a 5:
el autorreporte aporta valores de 1 a 5 y las opciones situacionales de 0 a 5. El promedio se
traduce a un nivel cualitativo:

| Puntaje (0–5) | Nivel (`level`) | Etiqueta en la UI |
|---|---|---|
| ≥ 4.5 | `outstanding` | Sobresaliente |
| ≥ 3.5 | `strong` | Sólido |
| ≥ 2.5 | `developing` | En desarrollo |
| < 2.5 | `critical` | Crítico |

Se calcula el mismo nivel en tres granularidades:

| Nivel de detalle | Dónde se guarda |
|---|---|
| Resultado global de la evaluación | `evaluation_results.total_score` + `level` |
| Resultado por **habilidad** | `skill_results` (promedio de las preguntas de esa habilidad) |
| Resultado por **categoría** | `category_results` (promedio de las respuestas de las preguntas asociadas a esa categoría) |

El cálculo actual agrupa respuestas directamente por habilidad y categoría; no promedia
primero las habilidades para obtener la categoría y no aplica el campo `weight` de
`position_skill`. Por ello, una habilidad con más preguntas puede tener mayor influencia en
el promedio de su categoría.

### 5.4 Qué se compara (brechas)

Para el diagnóstico organizacional no basta el resultado absoluto: se compara con lo
**esperado** por el cargo. El `Position` declara, para cada habilidad, un `required_level`
(1–5). La brecha positiva es:

```
brecha = max(0, required_level − puntaje_obtenido)
```

Las brechas positivas se agregan por `(habilidad, eje del cargo, área)` y se priorizan:

| Brecha promedio | Prioridad |
|---|---|
| ≥ 2.0 | `critical` |
| ≥ 1.5 | `high` |
| ≥ 1.0 | `medium` |
| > 0 y < 1.0 | `low` |

Si el puntaje alcanza o supera el nivel requerido, la brecha resultante es cero y no se
persiste una fila para esa combinación. La matriz y las brechas no usan ponderación del campo
`position_skill.weight`.

Adicionalmente, la **matriz de diagnóstico** cruza eje × categoría para mostrar el promedio
de la organización en cada combinación (`diagnostic_matrix_cells.average_score`).

### 5.5 Evaluación de mejoramiento

El progreso del colaborador se evalúa en dos momentos:

- **Dentro del plan:** porcentaje de actividades completadas
  (`improvement_plans.progress`) y estado del plan.
- **Reevaluación:** se agenda una `reevaluation_date`; el sistema notifica y permite asignar
  una nueva evaluación de tipo `reevaluation` ligada a la anterior.

### 5.6 Resumen de qué se evalúa

| Se evalúa | Medición |
|---|---|
| Habilidad blanda (individual) | Promedio de sus preguntas → `skill_results` |
| Categoría de habilidad | Promedio de respuestas de sus preguntas → `category_results` |
| Evaluación completa | Promedio total → `evaluation_results.total_score` + nivel |
| Brecha frente al cargo | `required_level − score` agrupado → `organizational_gaps` |
| Estado organizacional | Matriz eje × categoría → `diagnostic_matrix_cells` |
| Cumplimiento de un plan | % de actividades completadas → `improvement_plans.progress` |

> **Importante:** el análisis **cualitativo con IA** (narrativa de fortalezas y
> oportunidades) está previsto en el modelo (`ai_analysis`, `strengths`, `opportunities`,
> `ai_recommendation`) pero **aún no se genera**; hoy el contrato `AssessmentAnalyzer` usa la
> implementación nula.

## 6. Estructura y conexiones observadas con graphify

El análisis estático de `graphify-out/graph.json` complementa la lectura funcional: representa
símbolos, documentos y relaciones entre ellos, pero no es una medición de ejecución ni una
prueba de que cada ruta se use en producción.

| Métrica del snapshot | Valor | Interpretación |
|---|---:|---|
| Archivos indexados | 222 | Incluye código, configuración, pruebas y documentación |
| Nodos | 1.073 | 986 de código, 33 conceptos y 54 documentos |
| Relaciones | 2.629 | 2.598 extraídas y 31 inferidas |
| Comunidades | 79 | Grupos de nodos relacionados; el mayor contiene 108 nodos |
| Nodos aislados | 21 | Nodos sin relaciones dentro del grafo generado |

El mayor grupo está concentrado en `resources/js` (107 de sus 108 nodos), lo que refleja la
cantidad de módulos y utilidades que componen el workspace en el cliente. Las áreas con más
nodos del grafo son:

| Área | Nodos |
|---|---:|
| `app/Http` | 193 |
| `resources/js` | 162 |
| `app/Models` | 113 |
| `database/migrations` | 66 |
| `database/factories` | 65 |
| `tests/Feature` | 48 |
| `app/Enums` | 47 |
| `app/Services` | 47 |

Estos conteos son **nodos del grafo**, no archivos, líneas de código ni métricas de calidad.

Los símbolos con mayor número de relaciones son:

| Símbolo | Relaciones | Lectura arquitectónica |
|---|---:|---|
| `User` | 85 | Entidad transversal de identidad, pertenencia y permisos |
| `escapeHtml()` | 55 | Utilidad compartida por los módulos de interfaz |
| `Evaluation` | 42 | Entidad central del ciclo de evaluación |
| `Company` | 38 | Raíz del aislamiento multiempresa |
| `ImprovementPlan` | 37 | Conecta planes, actividades y recursos |
| `Skill` | 36 | Catálogo compartido por cargos, preguntas y resultados |
| `Question` | 32 | Une el banco de preguntas con las evaluaciones |

El grado mide conexiones estáticas, no indica por sí solo un cuello de botella ni un defecto.
El snapshot también incluye documentos y reglas de agentes (`.agents/` y `.claude/`), por lo
que algunas relaciones describen conocimiento del repositorio y no dependencias ejecutables.
Las relaciones inferidas deben leerse como sugerencias del análisis, no como llamadas
confirmadas. El grafo puede explorarse en [`graphify-out/graph.html`](../graphify-out/graph.html);
`graphify-out/` contiene artefactos generados.

## 7. Trazabilidad: del requisito al código

| Necesidad funcional | Módulo en el código | Lenguaje principal |
|---|---|---|
| Iniciar sesión de forma segura | `Services/AuthenticationService`, `Middleware/AuthenticateSupabase` | PHP |
| Separar información por empresa | `Policies`, filtros `company_id` | PHP |
| Administrar catálogo de habilidades | `SkillController`, `CatalogController` | PHP |
| Definir qué exige cada cargo | `PositionController` + `position_skill` | PHP |
| Evaluar colaboradores | `Services/EvaluationService` | PHP |
| Responder evaluaciones | `EvaluationController@respond` | PHP + JS |
| Calcular resultados y niveles | `EvaluationService@complete` | PHP |
| Generar diagnóstico y brechas | `Services/DiagnosticService` | PHP |
| Gestionar planes de mejora | `ImprovementPlanController` | PHP + JS |
| Notificar y expirar | `Notifications`, `routes/console.php` | PHP |
| Interfaz de usuario | `workspace.blade.php` + `resources/js` | Blade + JS + CSS |
| Proteger la aplicación | `Middleware/SecurityHeaders`, `config/security.php` | PHP |

## 8. Conclusión

El aplicativo tiene una arquitectura por capas sobre **Laravel/PHP** que separa transporte,
presentación, orquestación, dominio y persistencia. Cuenta con un dominio modelado mediante
21 modelos, 9 enums y 12 controladores de API, aislamiento multiempresa aplicado mediante
middleware, policies y filtros de consulta, y un frontend funcional en JavaScript nativo.

El proyecto separa responsabilidades entre rutas, controladores, servicios, políticas y
modelos. Sin embargo, el análisis IA está enlazado a un stub, no hay preguntas ni habilidades
de negocio precargadas, no existe capa central de API Resources y persiste JavaScript/vistas
heredados. El detalle de la deuda técnica y la hoja de ruta están en
[estado-y-pendientes.md](estado-y-pendientes.md).
