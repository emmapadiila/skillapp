# Diseño de interfaz de SkillDiagnóstico IA

Este documento recoge las decisiones de diseño visual y de experiencia de usuario del aplicativo y su justificación. Sirve como insumo para la monografía *Diseño de un aplicativo web basado en IA para el diagnóstico, medición y mejoramiento de habilidades blandas en los ejes operativo, misional y estratégico* (caso de estudio: Gracol S.A.S.).

El contexto del producto (usuarios, propósito y principios) está en [`PRODUCT.md`](../PRODUCT.md). Las capturas comparativas están en [`docs/capturas/antes/`](capturas/antes/) y [`docs/capturas/despues/`](capturas/despues/), con los mismos nombres y a 1440, 768 y 360 px.

## 1. Principios que guiaron el diseño

1. **Primero la persona que responde.** Un operario de planta debe poder completar la prueba desde el celular, sin ayuda.
2. **Explicar siempre.** Ningún puntaje aparece sin una frase sencilla de qué lo determinó; el sistema no debe percibirse como una «caja negra».
3. **Fidelidad al instrumento.** Los textos de preguntas, opciones, instrucciones y descripciones de los ejes vienen de la base de datos y la interfaz no los altera.
4. **Herramienta de trabajo, no página de marketing.** La aplicación está en modo *Operate*: la calidad se mide por el esfuerzo que ahorra, no por lo llamativa que es.
5. **Accesible por defecto.** WCAG 2.1 AA, foco visible, teclado completo y uso cómodo a 360 px.

## 2. Dirección visual: «Planta clara»

Se propusieron dos direcciones (capturas en [`docs/capturas/propuestas/`](capturas/propuestas/)) y se eligió **«Planta clara»**: evolución sobria de la paleta existente, con un azul petróleo que transmite confianza sin estridencia. La alternativa «Señalética industrial» (azul de seguridad ISO 3864, Fira Sans, esquinas rectas) se descartó por ser más austera para los tableros.

Prohibiciones explícitas: neón, degradados llamativos, glassmorphism, emojis como íconos y etiquetas en mayúsculas con espaciado sobre los títulos.

## 3. Tokens

Todos los tokens viven en `@theme` dentro de [`resources/css/app.css`](../resources/css/app.css) (Tailwind CSS 4). Las vistas no usan colores sueltos: usan estas utilidades (`bg-brand-600`, `text-success-700`…) o las clases de componente `ws-*`.

| Grupo | Tokens | Uso |
|---|---|---|
| Primario | `--color-brand-50` … `--color-brand-950` (azul petróleo; `brand-600` = `#1f5b70`) | Acciones principales, enlaces, foco, selección |
| Neutros | Escala `slate` de Tailwind | Texto, bordes, fondos |
| Estados / niveles | `success-*`, `warning-*`, `danger-*` | Fortaleza, En desarrollo, Por desarrollar |
| Ejes | `--color-axis-operational`, `-mission`, `-strategic` | Color categórico de los ejes |
| Tipografía | `--font-sans` (Inter), `--font-display` (Plus Jakarta Sans) | Texto y títulos (ambas fuentes van dentro del proyecto) |
| Radios | `--radius-control` 8 px, `--radius-button` 10 px, `--radius-card` 16 px | Campos, botones, tarjetas |
| Sombra | `--shadow-card` (suave, con desplazamiento) | Tarjetas |
| Movimiento | `--ease-strong-out` = `cubic-bezier(0.23, 1, 0.32, 1)` | Entradas y feedback |

Los colores de nivel nunca van solos: siempre acompañan al nombre del nivel en texto.

## 4. Componentes

Una sola fuente de estilo, las clases `ws-*` de `app.css`, compartida por los componentes Blade y por los generadores de HTML en JavaScript:

| Componente | Blade (`resources/views/components/ui/`) | JS (`resources/js/workspace-ui.js`) | Clase base |
|---|---|---|---|
| Botón | `x-ui.button` | `button()`, `link()` | `ws-primary`, `ws-button` (44 px de alto) |
| Campo | `x-ui.input` | `input()`, `select()` | `ws-field` (44 px, texto de 16 px) |
| Tarjeta | `x-ui.card` | `panel()` | `ws-panel` |
| Alerta | `x-ui.alert` | — | `ws-alert--info/success/warning/danger` |
| Barra de progreso | `x-ui.progress` | `progress()`, `scoreProgress()` | `ws-progress` |
| Badge de nivel | `x-ui.level-badge` | `statusBadge()` | `ws-badge--*` |
| Opción de la prueba | — | dentro de `workspace-assessment.js` | `ws-choice` |

## 5. Decisiones de experiencia y su justificación

### 5.1 Entrada: login, menú y consentimiento
- **Texto factual en el login.** Se eliminó «impulsada por inteligencia artificial» y los planes «personalizados», porque la IA todavía no está conectada: prometer funciones que no existen erosiona la confianza.
- **Menú agrupado** (*Tableros*, *Personas y pruebas*, *Seguimiento*) en lugar de 15 enlaces sueltos; «Recomendaciones IA» se marca como «No disponible aún».
- **Menú móvil accesible.** Cerrado queda `inert` (el teclado no entra); abierto, la página detrás queda `inert`, el foco pasa al menú y Escape lo devuelve al botón.
- **Consentimiento (Ley 1581 de 2012)** como lista breve (qué datos, para qué, quién los ve, tus derechos) y obligatorio también en el servidor. Al aceptar aparece «Autorización registrada.» y recibe el foco, para que el lector de pantalla lo anuncie.

### 5.2 La prueba (prioridad máxima)
- **Una pregunta por pantalla** con cabecera compacta («Pregunta 7 de 15 · Afirmación») y barra de progreso.
- **Barra de acciones fija abajo en el celular**: «Siguiente» queda siempre al alcance del pulgar (antes había que desplazarse en cada una de las 15 preguntas).
- **Escala Likert del anexo** (de «Totalmente en desacuerdo» a «Totalmente de acuerdo»), con el número en un círculo: filas en el celular, cinco columnas en escritorio.
- **Situaciones** con el escenario destacado y las cuatro opciones como tarjetas, en orden aleatorio fijo por persona (no cambia al volver atrás).
- **Guardado al marcar.** Cada elección se guarda en el momento, con los estados «Guardando…», «Respuesta guardada» o un error con reintento. Tras varias revisiones adversariales se garantizó que lo mostrado, lo guardado y lo enviado coincidan: cola por pregunta (la última elección gana), estado que sobrevive a «Salir», revisión que no se abre con cambios pendientes y bloqueo total durante el envío definitivo.
- **Revisión real**: lista de las 15 respuestas con «Cambiar», confirmación antes de enviar y mensaje final con «Ver mi resultado».

### 5.3 Resultados del colaborador
- **El nivel en palabras primero** («Tu nivel general: En desarrollo») y el puntaje como dato secundario: un número sobre 100 se lee como la nota de un examen; el nivel se lee como un diagnóstico.
- **Dos grupos**: «Tus fortalezas» y «Para seguir desarrollando», con la explicación de cada habilidad una sola vez.
- **Puntajes con dos decimales** (formato es-CO, unidad «puntos»): así ningún número visible contradice el nivel calculado (un 74,99 nunca aparece como «75» junto a «En desarrollo»).
- **«Cómo se calculó»** plegado y **«Qué sigue»** al final. El radar solo aparece desde tabletas.
- **«Mi progreso»** usa la misma precisión que los resultados; con una sola medición explica cuándo podrá verse la evolución, en lugar de mostrar un gráfico de un punto.

### 5.4 Tableros del admin (operativo, misional y estratégico)
- Se implementan sobre el workspace propio de la aplicación; **no se usa Filament** (decisión confirmada). Los datos se calculan en vivo con `GET /api/v1/dashboards/axes` y filtros por área, cargo y periodo.
- **Datos primero**: la descripción del eje y sus indicadores quedan plegados en «Sobre este eje».
- **Resumen sobrio** en una franja de cifras (personas, promedio con nivel, cuántas en cada nivel) en lugar de tarjetas de métrica con íconos de colores.
- **«Cómo leer este resultado»**: qué sostiene y qué baja el promedio, y un aviso de que con menos de 5 personas el resultado es orientativo.
- **Mapa de calor 4 categorías × 3 ejes** en el tablero estratégico, con texto en cada celda y distinción entre «No se mide en este eje» y «Sin datos». En el celular se apila por categoría para no perder la comparación. Las categorías siguen el orden del instrumento.
- **Lectura vertical** de habilidades análogas entre ejes, también apilada en el celular.

## 6. Accesibilidad

- **Contraste AA** en todo el texto, incluidos los textos de ejemplo de los campos.
- **Foco visible**: contorno sólido de 2 px en `brand-600` (≈ 7:1 sobre blanco) en botones, enlaces, campos y opciones de la prueba. Antes los campos usaban un anillo al 30 % que no llegaba al 3:1 que exige WCAG 2.2.
- **Teclado completo**: la prueba se responde con Tab, flechas y Enter; el foco pasa al enunciado de cada pregunta.
- **Etiquetas** en todos los campos, nombres accesibles en todos los controles y SVG decorativos con `aria-hidden`.
- **Movimiento reducido**: con `prefers-reduced-motion` solo queda el fundido de entrada.

Auditoría automática final: 34 combinaciones de pantalla y ancho, **0 hallazgos**.

## 7. Movimiento

Siguiendo la filosofía de animación de Emil Kowalski, solo se anima lo que confirma una acción:

| Elemento | Propósito | Ingredientes |
|---|---|---|
| Elegir una opción | Feedback | Color en 150 ms; presión `scale(0.98)` en 120 ms |
| Barra de progreso | Indicar estado | Ancho en 250 ms al guardar una respuesta |
| Cambio de pregunta | Evitar un cambio brusco | Fundido + 8 px en 200 ms, sobre el contenido (no sobre la barra fija) |

Todas usan `--ease-strong-out`; ninguna supera 300 ms.

## 8. Antes y después

Evaluación con las heurísticas de Nielsen (crítica de impeccable): **25/40 (aceptable) → 29/40 (bueno)**.

| Hallazgo de la auditoría inicial | Estado |
|---|---|
| «Siguiente» fuera de la pantalla en el celular | Resuelto (barra fija) |
| La revisión no dejaba revisar | Resuelto (lista con «Cambiar») |
| Resultados leídos como nota de examen | Resuelto (nivel en palabras primero) |
| Mapa de calor recortado en el celular | Resuelto (vista apilada) |
| Estados vacíos genéricos y descripción del eje ocupando la pantalla | Resuelto en tableros; parcial en otras pantallas |
| Aviso flotante tapando contenido y `aria-live` global | Resuelto |
| Niveles solo por color y unidades mezcladas | Resuelto («puntos» y nivel en texto) |
| Login y menú que prometían IA inexistente | Resuelto (texto factual) |
| Contraste de textos de ejemplo | Resuelto |
| Letra pequeña (10 px) y campos de 14 px | Resuelto |
| Categorías en orden alfabético | Resuelto (orden del instrumento) |
| «Salir» perdía la respuesta marcada | Resuelto |
| Pendientes menores | Frases de explicación repetidas por habilidad (las genera el servidor); jerarquía tipográfica compacta propia de una herramienta densa |

## 9. Verificación

- `php artisan test --compact`: 63 pruebas de PHP.
- Pruebas JS con `node:test` (`tests/Unit/*.test.mjs`): flujo de la prueba (orden de guardados y consultas), «Mi progreso» y el recorrido en Chromium (`WorkspaceBrowser.test.mjs`).
- Pruebas de guardado contra MySQL: marcar sin pulsar «Siguiente», cambios rápidos, salir y volver, sin conexión y reintento, revisión con cambios pendientes, bloqueo durante el envío.
- Prueba de punta a punta con los dos usuarios demo y capturas finales en [`docs/capturas/despues/`](capturas/despues/).
- Revisiones de Codex en cada etapa (`/codex:review` y `/codex:adversarial-review`), con sus hallazgos clasificados y corregidos.

Comandos para reproducir:

```bash
php artisan migrate:fresh --seed
npm run build
php artisan serve        # http://127.0.0.1:8000
php artisan test --compact
WORKSPACE_BROWSER_FIXTURES=1 php artisan test --compact tests/Feature/BrowserWorkspaceTest.php
CHROME_PATH=/usr/bin/chromium node --test tests/Unit/*.test.mjs
```
