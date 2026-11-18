# Product

<!-- impeccable:product-schema 1 -->

## Platform

web

## Users

- **Colaboradores de Gracol S.A.S.** (Galapa, Atlántico), sobre todo operarios de producción y apoyo logístico. Responden la prueba de habilidades blandas de su eje, muchas veces desde el celular y en planta, con poco tiempo y sin experiencia con este tipo de herramientas.
- **Responsables de procesos centrales** (calidad, relación con clientes) y **alta dirección**, que presentan las pruebas de los ejes misional y estratégico desde el computador.
- **Gestor de Talento Humano** (rol administrador): asigna o da seguimiento a las pruebas y lee los tableros por eje para tomar decisiones de desarrollo.

## Product Purpose

Diagnosticar, medir y orientar el mejoramiento de las habilidades blandas del personal en tres ejes organizacionales (operativo, misional y estratégico), con resultados ponderados y explicados en lenguaje sencillo. Gracol lo usará de forma continua después del proyecto de grado, así que cada flujo debe funcionar con personas reales, no solo en una demostración.

Éxito: que cada colaborador complete su prueba sin ayuda y entienda su resultado, y que Talento Humano identifique en qué categoría y en qué eje se concentran las brechas.

## Positioning

Un diagnóstico organizado en 3 ejes × 4 categorías (Comunicativas y relacionales, Trabajo colaborativo, Cognitivas y de autogestión, Dirección y desarrollo) con pesos por habilidad guardados en la base de datos, pruebas distintas para cada eje y explicabilidad obligatoria: cada resultado indica qué lo subió o lo bajó, para que nadie lo perciba como una "caja negra". El análisis con IA para planes de mejora es un punto de integración previsto, todavía no conectado.

## Operating Context

- El colaborador ve solo la prueba del eje de su cargo, debe aceptar el consentimiento informado antes de iniciarla y puede cerrarla y retomarla.
- Cada prueba tiene ítems Likert de acuerdo (1–5) y situaciones con 4 opciones; el contenido viene literal del anexo de la monografía y no se modifica.
- Talento Humano consulta tres tableros (para líderes de primera línea, responsables de procesos centrales y alta dirección) con filtros por área, cargo y periodo, y un mapa de calor categoría × eje en el estratégico.

## Capabilities and Constraints

- Laravel 13 + Blade + JavaScript sin framework (workspace de una sola página); Tailwind CSS 4 con tokens en `@theme`; sin Filament ni Livewire (decisión confirmada).
- Base de datos MySQL/MariaDB; inicio de sesión local o con Supabase.
- Niveles: < 50 "Por desarrollar", 50–74 "En desarrollo", ≥ 75 "Fortaleza" (escala 0–100).
- Las opciones situacionales se muestran en orden aleatorio fijo por persona y su puntaje nunca se muestra al colaborador.
- Pendiente: evaluación por pares o por el superior inmediato; integración real de la IA.

## Brand Commitments

- Nombre del producto: **SkillDiagnóstico IA**. Solo la marca propia; no se usan logo ni colores de Gracol.
- Todo el contenido visible en español y tuteando ("tú"), en coherencia con las instrucciones del anexo.
- Tono profesional, sobrio y claro; lenguaje sencillo apto para personal operativo.

## Evidence on Hand

- Contenido de las tres pruebas, descripciones de los ejes e indicadores organizacionales: anexo de la monografía, cargado por los seeders (`database/seeders/Prueba*Seeder.php`).
- Usuarios demo de los seeders: un administrador y un operario de producción. No hay datos reales de Gracol en el repositorio: no se deben inventar resultados, métricas ni testimonios en la interfaz.

## Product Principles

1. Primero la persona que responde: la prueba debe poder completarla un operario desde el celular sin ayuda.
2. Explicar siempre: ningún puntaje sin una frase sencilla de qué lo determinó.
3. Fidelidad al instrumento: el texto de preguntas, opciones e instrucciones no se altera en la interfaz.
4. Privacidad visible: consentimiento claro (Ley 1581 de 2012) y resultados individuales solo para la persona y Talento Humano.

## Accessibility & Inclusion

- WCAG 2.1 AA: contraste, foco visible, navegación completa por teclado y `label` en todos los campos.
- Uso en celular desde 360 px de ancho con controles grandes y táctiles.
- Los niveles y el mapa de calor no pueden depender solo del color: siempre llevan texto.
