## Alcance
- Añadir indicación visual de apreciación IA en la tabla de Surveys.
- Agregar acción "Apreciación IA" en record actions con validaciones de fecha y existencia.
- Generar apreciaciones usando Prism y la API de OpenAI, con credenciales desde ManageSettings.
- Manejo de errores, estado de carga, y pruebas automatizadas.

## Arquitectura y Almacenamiento
- Crear tabla `survey_ai_appreciations` con: id, survey_id (unique), content (longText), provider ("openai"), model ("gpt-5-medium"), usage_tokens, reasoning_tokens, error_message nullable, created_at/updated_at.
- Relación Eloquent: Survey hasOne AiAppreciation. Consultar/editar aquí: [Survey.php](file:///c:/laragon/www/finanzasPersonales/app/Models/Survey.php).
- Criterio de regeneración: generar si no existe apreciación o si `survey.updated_at > appreciation.updated_at`.

## Configuración de Prism y Credenciales
- Añadir dependencia `prismphp/prism` y publicar config `config/prism.php` con provider `openai`.
- Resolver API Key dinámicamente: obtener `token_ai` con [SettingService::get](file:///c:/laragon/www/finanzasPersonales/app/Services/SettingService.php#L27-L31) y asignar en runtime `config(['prism.providers.openai.api_key' => $token])` antes de cada llamada.
- No almacenar el token en `.env` ni logs; solo en memoria del proceso.

## Generación de Apreciaciones (Servicio + Job)
- Servicio `SurveyAiAppreciationService`:
  - Construye el "reporte completo" usando [SurveyStatsService](file:///c:/laragon/www/finanzasPersonales/app/Services/SurveyStatsService.php) y datos de participantes/respuestas como en [SurveyReportController](file:///c:/laragon/www/finanzasPersonales/app/Http/Controllers/SurveyReportController.php#L1-L237).
  - Prompt: resumen ejecutivo + métricas clave + dimensiones + síntesis por tipo de pregunta + áreas de mejora y sugerencias accionables.
  - Llamada Prism:
    - `Prism::text()->using('openai', 'gpt-5-medium')->withProviderOptions(['reasoning' => ['effort' => 'medium']])->withPrompt($prompt)->asText()`.
    - Manejar errores: try/catch, mapear excepciones a `error_message` y notificaciones.
- Job `GenerateSurveyAiAppreciationJob` encola la generación para no bloquear UI. Retries/backoff (p. ej. tries=3, backoff=[10,30,60]). Guarda/upserta apreciación.

## UI en SurveyResource
- Tabla: nueva `IconColumn` que muestre un icono estilo Filament cuando exista apreciación.
  - Estado: `state(fn (Survey $record) => (bool) $record->aiAppreciation)`.
  - Icono: usar `heroicon-o-cpu-chip` (fallback consistente). Si el proyecto expone `heroicon-o-robot`, se usará ese.
  - Tooltip: "Apreciación generada por IA" y color `success`.
  - Ubicación: [SurveyResource::table](file:///c:/laragon/www/finanzasPersonales/app/Filament/Resources/Surveys/SurveyResource.php#L217-L256).
- Record Action: `Action::make('ai_appreciation')` en [recordActions](file:///c:/laragon/www/finanzasPersonales/app/Filament/Resources/Surveys/SurveyResource.php#L257-L301).
  - Visible si: hay `token_ai` y la encuesta tiene respuestas (cálculo ya disponible en la columna "Respondieron").
  - Lógica:
    - Si existe apreciación y `survey.updated_at <= appreciation.updated_at`, notificar "Ya está al día" y no regenerar.
    - En otro caso, despachar `GenerateSurveyAiAppreciationJob`.
  - UX: mostrar `->progressIndicator()`/spinner de acción mientras se despacha y notificación "Generando apreciación..."; notificación de éxito/error al finalizar.

## Manejo de Errores y Seguridad
- Errores de API: capturar y persistir `error_message`; notificar al usuario con detalles genéricos (sin exponer credenciales ni payloads).
- Credenciales: leer `token_ai` desde [ManageSettings](file:///c:/laragon/www/finanzasPersonales/app/Filament/Pages/ManageSettings.php#L134-L156); nunca loggear ni cachear fuera de proceso.
- Rendimiento: toda generación se realiza en Job; UI no se bloquea; uso de SettingService cacheado ya existente.

## Validaciones y Pruebas
- Icono: verificar que aparece cuando existe apreciación y se oculta en caso contrario.
- Acción: visible solo si `token_ai` y existen respuestas; ocultar si no.
- Reglas de fecha: probar casos de regeneración y no-regeneración.
- API Prism:
  - Token ausente: error y notificación.
  - Falla HTTP/timeout: reintentos, notificación, persistencia de `error_message`.
  - Éxito: guarda contenido y actualiza columna de icono.
- Tests:
  - Feature: `tests/Feature/Filament/SurveyAiAppreciationTest.php` (visibilidad y flujo de acción con cola sync).
  - Unit: `tests/Unit/SurveyAiAppreciationServiceTest.php` (con stub/mocks para Prism y generación de prompt). Usa `QUEUE_CONNECTION=sync` del [phpunit.xml](file:///c:/laragon/www/finanzasPersonales/phpunit.xml).

## Entregables
- Migración, modelo y relación de apreciaciones.
- Servicio + Job con Prism.
- Columnas/acciones en SurveyResource con icono y estado de carga.
- Pruebas unitarias y de integración.
- Documentación breve en README interno sobre configuración de `token_ai`.
