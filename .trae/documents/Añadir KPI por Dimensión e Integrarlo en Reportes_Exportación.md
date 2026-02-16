## Alcance
- Definir una meta/KPI por cada dimensión (item) de la encuesta y calcular el % de cumplimiento y su calificación.
- Permitir configurar el KPI donde se define el nombre de la dimensión.
- Reflejar KPI y cumplimiento en Reporte PDF y en Exportación Excel.

## Modelo de Datos
- Crear tabla `survey_dimensions` (id, survey_id, item, kpi_target decimal(5,2), weight decimal(5,2) nullable, timestamps).
- Añadir modelo `SurveyDimension` con `belongsTo(Survey)` y `Survey->hasMany(SurveyDimension)`.
- Migración de datos: para encuestas existentes, poblar `survey_dimensions` con los items únicos de `questions` y `kpi_target` por defecto (ej.: 5.0 si escala 0–5, 10.0 si 0–10; configurable).

## UI de Configuración (Filament)
- En `SurveyResource` agregar sección "Dimensiones" con Repeater (item + kpi_target + opcional weight).
- En Repeater de Preguntas: cambiar `TextInput::make('item')` a `Select` que use las dimensiones definidas (permite crear nueva con inline create si no existe).
- Validación: `kpi_target > 0`. Sincronizar automáticamente cuando se crean/renombran items.

## Cálculo y Clasificación
- Actualizar `SurveyStatsService`:
  - Incluir KPI por dimensión, calcular `%cumplimiento = (avg / kpi_target) * 100` (clamp 0–100 si aplica).
  - Devolver por dimensión: preguntas, respuestas, avg, kpi_target, compliance_pct, rating_band.
- Bandas propuestas (configurables):
  - [0,55): Deficiente → Acción: Atención inmediata
  - [55,70): Regular → Minimizarlo
  - [70,85): Bueno → Controlarlo
  - [85,100]: Excelente → Mantenerlo

## Reporte PDF
- Añadir columnas "Meta" y "%Cumplimiento" a cada dimensión.
- Mostrar calificación con color de banda.
- En la sección final (visualizaciones): incluir gráfico de barras comparando promedio vs meta por dimensión (robusto para Dompdf) y opcional donut de participación.

## Exportación Excel
- Cambiar el exportador para incluir columnas: Dimensión, Pregunta, Promedio (numérico donde aplica), Meta (de dimensión), %Cumplimiento y Calificación; respetar privacidad (nombre/email/departamento solo si pública).

## Configuración & Defaults
- Config parámetro `survey.default_kpi_scale_5` y `survey.default_kpi_scale_10` en `config/survey.php`.
- Job/command para backfill KPIs en encuestas existentes.

## Verificación
- Tests unitarios para cálculo de stats y clasificación.
- Validar render PDF (sin errores), verificar Excel con y sin encuesta pública.

## Entregables
- Migraciones y modelos.
- Actualización de `SurveyResource` (UI Filament).
- `SurveyStatsService` extendido.
- Reporte PDF actualizado y Exportador Excel con KPI.

¿Te parece bien esta estructura? Si confirmas, implemento en pasos: datos → UI → cálculos → reportes/exportación → verificación.