# Pruebas de habilidades blandas

Este documento describe las **tres pruebas exploratorias** incluidas en SkillApp, cómo se
instalan, cómo se asignan, cómo se responden y cómo se verifican.

> **Advertencia académica.** Estas pruebas son **instrumentos de demostración** creados para
> un prototipo de investigación. **No** son pruebas psicométricas validadas ni deben usarse
> como indicador único para decisiones laborales. Los resultados tienen carácter **formativo**
> y de desarrollo.

## 1. Qué son

Son tres instrumentos diferenciados por **eje organizacional**, pensados para perfiles
distintos:

| Código | Eje | Nombre | Dirigida a |
|---|---|---|---|
| `OPERATIVE_SKILLS_V1` | Operativo | Prueba de habilidades operativas | Producción, logística y procesos de apoyo |
| `MISSIONAL_SKILLS_V1` | Misional | Prueba de habilidades misionales | Procesos centrales de valor y relación con clientes |
| `STRATEGIC_SKILLS_V1` | Estratégico | Prueba de habilidades estratégicas | Dirección, planificación y toma de decisiones |

Cada prueba tiene **9 preguntas** distribuidas en **3 habilidades**, y cada habilidad aporta
una pregunta de cada tipo: **autorreporte**, **situacional** y **juicio situacional**. En
total son **27 preguntas** y **72 opciones** de respuesta.

## 2. Estructura de cada prueba

| Elemento | Detalle |
|---|---|
| Preguntas por prueba | 9 |
| Habilidades por prueba | 3 |
| Preguntas por habilidad | 3 (una de cada tipo) |
| Tipos | `self_report` (Likert 1–5), `situational` (4 opciones), `situational_judgment` (4 opciones) |
| Dificultad | `basic`, `intermediate` y `advanced` presentes en cada prueba |
| Puntaje de opciones | 1 a 5 (el motor acepta 0–5, pero se usan 1–5 para no romper la escala global) |

Habilidades por prueba:

| Prueba | Habilidades | Categorías |
|---|---|---|
| Operativa | Comunicación operativa · Trabajo en equipo · Gestión del tiempo | Comunicativas · Colaborativas · Cognitivas |
| Misional | Comunicación asertiva · Empatía y orientación al cliente · Colaboración interfuncional | Comunicativas · Colaborativas |
| Estratégica | Liderazgo · Toma de decisiones basada en información · Gestión del cambio | Dirección · Cognitivas |

## 3. Modelo de datos

Se reutilizó el modelo existente y se añadió la ampliación mínima (migración
`2026_10_15_174843_create_assessment_instruments_tables`, aditiva y reversible):

| Elemento | Para qué |
|---|---|
| Tabla `assessments` | Identifica cada prueba: `code`, `name`, `description`, `organizational_axis_id`, `is_active` |
| `questions.assessment_id` | A qué prueba pertenece la pregunta |
| `questions.code` | Identificador estable (por ejemplo `OPERATIVE_SKILLS_V1-S1Q2`) que hace idempotente el seeder |
| `questions.display_order` | Orden de presentación dentro de la prueba |
| `questions.company_id` (**nullable**) | Permite preguntas de plataforma (globales) además de las de una empresa |
| `evaluations.assessment_id` | Deja registrado con qué prueba se evaluó al colaborador |

Las **habilidades** de las pruebas se guardan como `skills` globales (`company_id = null`) y
se asocian a su eje mediante `skill_axis`. Por eso están disponibles para **todas** las
empresas sin duplicar contenido.

## 4. Instalación

Requisitos: la base migrada y los catálogos globales cargados.

```bash
php artisan migrate           # crea las tablas de pruebas
php artisan db:seed           # cargos base + las tres pruebas (idempotente)
```

También puedes cargar **solo** los instrumentos:

```bash
php artisan db:seed --class=AssessmentInstrumentSeeder
```

El seeder es **idempotente**: si lo ejecutas de nuevo, actualiza la misma información y no
duplica habilidades, preguntas ni opciones (se apoya en `assessments.code`,
`skills.name` global y `questions.code`).

## 5. Cómo asignar una prueba

### Desde la interfaz

1. Entra como `human_resources` o `superadmin`.
2. Ve a **Evaluaciones → + Asignar evaluación**.
3. Elige el colaborador (su **cargo** debe pertenecer al eje de la prueba).
4. En **"Prueba de habilidades (opcional)"** selecciona una de las tres.
5. Define la fecha límite y guarda. El campo "Número de preguntas" se desactiva porque la
   prueba ya define sus 9 preguntas.

Si dejas la prueba vacía, se conserva el comportamiento anterior: las preguntas se eligen
según las habilidades del cargo.

### Desde la API

`GET /api/v1/assessments` lista las pruebas disponibles (nombre, eje y preguntas).

```bash
curl -X POST http://localhost:8000/api/v1/evaluations \
  -H "Authorization: Bearer <token>" -H 'Content-Type: application/json' \
  -d '{"user_id":2,"type":"initial","assessment_id":1,"due_at":"2026-12-01T12:00:00-05:00"}'
```

Reglas de validación:

- Debe indicarse **una sola** fuente de preguntas: `assessment_id`, `question_count` **o**
  `question_ids`.
- El **cargo** del colaborador debe pertenecer al **mismo eje** que la prueba; si no,
  responde `422` con `assessment_id`.

## 6. Cómo responder

El colaborador entra a **Mi evaluación**, lee el nombre y la descripción de la prueba (las
instrucciones) y comienza. Cada respuesta se guarda con `POST /api/v1/evaluations/{id}/responses`
y la evaluación se cierra con `POST /api/v1/evaluations/{id}/complete`.

- Autorreporte → `likert_value` (1–5).
- Situacional y juicio situacional → `question_option_id`.
- Al responder se ocultan al colaborador los **puntajes internos** de las opciones.
- No se puede responder una evaluación ajena ni modificar una ya completada.

## 7. Cómo se califica

Se reutilizan las reglas vigentes de `EvaluationService`, sin cambios:

- **Resultado global:** promedio de las respuestas (escala 1–5).
- **Resultado por habilidad:** promedio de las preguntas de esa habilidad (`skill_results`).
- **Resultado por categoría:** promedio de las habilidades de esa categoría (`category_results`).
- **Nivel:** ≥ 4.5 `outstanding`, ≥ 3.5 `strong`, ≥ 2.5 `developing`, < 2.5 `critical`.
- **Brecha por cargo:** `max(0, required_level − puntuación)` (en el diagnóstico organizacional).

## 8. Diagnóstico y planes

- El **diagnóstico organizacional** consume las evaluaciones completadas y genera la matriz
  eje × categoría y las brechas por habilidad/área con su prioridad.
- Los **planes de mejoramiento** se crean sobre una habilidad; el progreso se calcula con las
  actividades completadas.
- Las recomendaciones del eje se registran como texto (`ai_recommendation`). Hoy son
  **estáticas/regladas**: el análisis con IA sigue pendiente (`AssessmentAnalyzer` usa la
  implementación nula). No se debe afirmar que hay IA integrada.

## 9. Verificación

```bash
php artisan test --compact --filter=SoftSkillsAssessmentTest
php artisan test --compact
vendor/bin/pint --dirty --format agent
```

La batería cubre: creación de las tres pruebas, 9 preguntas por prueba, asociación con
habilidades y ejes, idempotencia del seeder, asignación según cargo, registro y validación de
respuestas, cálculo de resultados, persistencia por habilidad/categoría, diagnóstico,
aislamiento entre empresas y bloqueo de evaluaciones ajenas.

## 10. Limitaciones y pendientes

- **Validación psicométrica:** no existe (fiabilidad, validez de constructo, baremos). Es un
  prototipo académico.
- **Análisis con IA:** pendiente (`NullAssessmentAnalyzer`). Las recomendaciones actuales son
  regladas, no generadas por un modelo.
- **Consentimiento informado:** no implementado. No debe usarse con personas reales en un
  piloto sin resolverlo.
- **Puntajes:** se mantiene la escala 1–5 existente; el motor admite 0–5 pero los nuevos
  ítems usan 1–5 para ser coherentes con los niveles.
- **Aislamiento:** las pruebas son globales, pero las evaluaciones, respuestas y resultados
  siguen aislados por empresa.

## Anexo: contenido de las 27 preguntas

Convenciones: **SR** = autorreporte (Likert 1–5), **ST** = situacional, **SJ** = juicio
situacional. Los puntajes se leen como `A1 · B5` = la opción A vale 1 punto y la B, 5.

### Prueba 1 — `OPERATIVE_SKILLS_V1` (Operativo)

| # | Habilidad | Tipo | Dificultad | Enunciado | Puntajes |
|---|---|---|---|---|---|
| 1 | Comunicación operativa | SR | basic | Cuando identifico una novedad que puede afectar a otros compañeros, la comunico de forma oportuna y clara. | Likert 1–5 |
| 2 | Comunicación operativa | ST | intermediate | A pocos minutos de terminar tu turno detectas una incidencia que podría detener el proceso. ¿Cuál es la forma más adecuada de comunicarla? | A1 · B5 · C3 · D2 |
| 3 | Comunicación operativa | SJ | advanced | Debes informar al equipo un cambio en la secuencia de producción que reduce el tiempo disponible de una tarea. ¿Qué haces primero? | A5 · B3 · C2 · D1 |
| 4 | Trabajo en equipo | SR | basic | Colaboro con mis compañeros cuando una tarea del área se retrasa, aunque no sea mi responsabilidad directa. | Likert 1–5 |
| 5 | Trabajo en equipo | ST | intermediate | Un compañero no alcanza a terminar su parte y tu turno está por cerrar con la meta cumplida. ¿Cómo actúas? | A1 · B5 · C3 · D2 |
| 6 | Trabajo en equipo | SJ | intermediate | Dos compañeros discuten frente al equipo por la asignación de una tarea. ¿Qué haces? | A5 · B3 · C2 · D1 |
| 7 | Gestión del tiempo | SR | intermediate | Organizo mis tareas por prioridad antes de comenzar la jornada. | Likert 1–5 |
| 8 | Gestión del tiempo | ST | advanced | Al iniciar el turno tienes tres pendientes y tiempo para completar solo dos. ¿Qué criterio aplicas? | A2 · B5 · C1 · D3 |
| 9 | Gestión del tiempo | SJ | advanced | Un imprevisto consume la mitad de tu turno y aún tienes tareas comprometidas. ¿Qué haces? | A5 · B1 · C2 · D3 |

**Qué observa cada opción (ejemplo, ítem 2).** Mide la oportunidad y la responsabilidad al
comunicar un incidente: reportar de inmediato y dejar registro protege el proceso (5);
intentar resolverlo en silencio sin escalar es parcialmente responsable (3); delegar la
decisión en otro (2) o posponer el aviso (1) rompen la coordinación entre turnos.

### Prueba 2 — `MISSIONAL_SKILLS_V1` (Misional)

| # | Habilidad | Tipo | Dificultad | Enunciado | Puntajes |
|---|---|---|---|---|---|
| 1 | Comunicación asertiva | SR | basic | Expreso mis desacuerdos con respeto y sin dejar de decir lo que pienso. | Likert 1–5 |
| 2 | Comunicación asertiva | ST | intermediate | Un cliente insiste en un cambio que no es posible cumplir en el plazo que pide. ¿Cómo se lo comunicas? | A1 · B5 · C2 · D3 |
| 3 | Comunicación asertiva | SJ | advanced | En una reunión entre áreas tu propuesta es criticada duramente frente a todos. ¿Qué haces? | A5 · B2 · C1 · D3 |
| 4 | Empatía y orientación al cliente | SR | basic | Escucho completo el reclamo de un cliente antes de proponer una solución. | Likert 1–5 |
| 5 | Empatía y orientación al cliente | ST | intermediate | Un cliente reporta un retraso y está molesto. Necesitas coordinar la respuesta con otra área. ¿Qué haces primero? | A5 · B2 · C3 · D1 |
| 6 | Empatía y orientación al cliente | SJ | advanced | Varios clientes reportan el mismo problema y el área técnica todavía no encuentra la causa. ¿Cómo procedes? | A5 · B2 · C1 · D3 |
| 7 | Colaboración interfuncional | SR | intermediate | Cuando una tarea depende de otra área, mantengo informados a quienes la necesitan. | Likert 1–5 |
| 8 | Colaboración interfuncional | ST | intermediate | Tu área y otra dependencia manejan información distinta sobre un compromiso con un cliente. ¿Qué haces? | A5 · B2 · C3 · D1 |
| 9 | Colaboración interfuncional | SJ | advanced | Otra área incumple un acuerdo y eso afecta la calidad del servicio. ¿Qué haces? | A5 · B2 · C3 · D1 |

**Qué observa cada opción (ejemplo, ítem 5).** Valora la empatía y la honestidad:
reconocer la molestia, verificar el estado real y coordinar con el área responsable es la
respuesta adecuada (5); ofrecer una compensación sin autoridad (3) genera compromisos
irreales; culpar a otra área (2) o postergar al cliente (1) evitan la responsabilidad.

### Prueba 3 — `STRATEGIC_SKILLS_V1` (Estratégico)

| # | Habilidad | Tipo | Dificultad | Enunciado | Puntajes |
|---|---|---|---|---|---|
| 1 | Liderazgo | SR | basic | Acompaño a mi equipo cuando enfrenta dificultades, en lugar de limitarme a señalar errores. | Likert 1–5 |
| 2 | Liderazgo | ST | intermediate | Dos integrantes del equipo mantienen un conflicto que afecta los resultados. ¿Cómo lo abordas? | A5 · B2 · C1 · D3 |
| 3 | Liderazgo | SJ | advanced | Debes asignar un proyecto clave a alguien del equipo. ¿En qué te basas? | A5 · B2 · C3 · D1 |
| 4 | Toma de decisiones basada en información | SR | basic | Antes de decidir reviso los datos disponibles en lugar de guiarme solo por la intuición. | Likert 1–5 |
| 5 | Toma de decisiones basada en información | ST | intermediate | Debes elegir entre dos proveedores y solo tienes la recomendación verbal de un colega. ¿Qué haces? | A5 · B2 · C3 · D1 |
| 6 | Toma de decisiones basada en información | SJ | advanced | Los indicadores muestran una caída, pero la causa no es evidente. ¿Cómo procedes? | A5 · B2 · C3 · D1 |
| 7 | Gestión del cambio | SR | intermediate | Cuando un cambio afecta mi trabajo, busco entender su propósito antes de oponerme. | Likert 1–5 |
| 8 | Gestión del cambio | ST | intermediate | La organización implementará un nuevo proceso y parte del equipo se resiste. ¿Cómo abordas la situación? | A5 · B2 · C1 · D3 |
| 9 | Gestión del cambio | SJ | advanced | A mitad de la implementación de un cambio detectas efectos no previstos en un área. ¿Qué haces? | A5 · B2 · C1 · D3 |

**Qué observa cada opción (ejemplo, ítem 6).** Evalúa la decisión basada en evidencia:
formular hipótesis y contrastarlas con datos (5) es el criterio esperado; actuar con la
primera explicación (2) o cambiar todo el proceso (1) son reacciones sin base; esperar al
siguiente informe (3) posterga sin analizar.

> Los ítems son **originales** y están inspirados en las necesidades de la investigación; no
> reproducen instrumentos psicométricos protegidos.
