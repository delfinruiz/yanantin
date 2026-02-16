## Contexto del proyecto
- Filament 4 y Livewire v3 ya están integrados con panel admin y múltiples Resources. Referencias: [AdminPanelProvider.php](file:///c:/laragon/www/finanzasPersonales/app/Providers/Filament/AdminPanelProvider.php#L68-L103), [User.php](file:///c:/laragon/www/finanzasPersonales/app/Models/User.php).
- Paquetes disponibles: Spatie Roles/Permissions, Filament Shield, pxlrbt/filament-excel para exportación a Excel. No hay módulo de encuestas ni departamentos existentes.

## Objetivo
Crear un módulo completo para crear, distribuir y gestionar encuestas, con agrupación por "dimensiones" (ítems/temas), asignación por departamentos y exportación de resultados.

## Datos y Modelado
- Department: id, name, description, timestamps.
- Pivot department_user: department_id, user_id (índice único compuesto).
- Survey: id, title, description, active (bool), deadline (nullable datetime), creator_id.
- Question: id, survey_id, content (text), type (enum: text|bool|scale_5|scale_10|likert|multi), item (string para dimensión), required (bool), options (json nullable para multi/likert), order.
- Response: id, question_id, user_id, value (string), created_at; para escala/likert guardar valor numérico 0–5/0–10/1–5, para V/F guardar "1/0"; text y multi en string/JSON.
- Asignación: pivot survey_user (survey_id, user_id, assigned_at) y pivot survey_department (survey_id, department_id, assigned_at) para trazabilidad.

## Migraciones
- Crear tablas y pivots arriba con índices: (survey_id), (question_id), (user_id), únicos en pivots.
- Añadir constraints ON DELETE CASCADE para consistencia.

## Relaciones Eloquent
- User: belongsToMany Departments; belongsToMany Surveys vía survey_user; hasMany Responses.
- Department: belongsToMany Users; belongsToMany Surveys vía survey_department.
- Survey: hasMany Questions; belongsToMany Users/Departments; scopes: active(), dueSoon().
- Question: belongsTo Survey; hasMany Responses.
- Response: belongsTo Question y User.

## Recursos Filament
- DepartmentResource: CRUD completo (name, description), relación Many‑to‑Many con Users mediante MultiSelect, tabla con conteo de usuarios.
- SurveyResource:
  - Formulario: title, description, active, deadline.
  - Constructor de preguntas con Repeater (relación questions):
    - Campos por pregunta: item (dimensión), content, type (Select), required (Toggle), options (Repeater/KeyValue visible según type), order.
    - Tipos soportados: Texto libre, Verdadero/Falso, Escala 0–5, Escala 0–10, Likert (Nunca…Siempre mapeado a 1–5), Selección múltiple.
  - Pestaña "Distribución": toggle "Para todos"; MultiSelect de Departments; vista de usuarios asignados calculada.
  - Acciones: Publicar/Despublicar, Duplicar encuesta, Exportar resultados (Excel y PDF).
- SurveyResponsePage (Filament Page pública dentro del panel para el usuario logueado): muestra las preguntas agrupadas por item con componentes adecuados y valida requerido.

## Agrupación por Dimensiones
- Usar el campo item en cada pregunta para la dimensión.
- En la UI: agrupar por item en Table/List y en el formulario de respuesta (secciones por item).
- Opcional (si se desea): una Repeater anidada de "Dimensiones" (nombre) con sub‑Repeater de "Preguntas", que a la hora de guardar crea las Question con item = nombre de la dimensión.

## Distribución y Notificaciones
- Lógica de asignación:
  - Si "Para todos" → asignar survey_user a todos los Users activos.
  - Si hay Departments seleccionados → asignar a todos los usuarios miembros de esos departamentos.
  - Evitar duplicados; recalcular asignaciones al actualizar.
- Notificaciones: NewSurveyAssigned a users asignados usando Notifiable en User; canales Email y Database.
- Recordatorio automático próximo a deadline (Job diario).

## Políticas y Permisos
- Crear DepartmentPolicy y SurveyPolicy con el patrón existente: viewAny/view/create/update/delete/etc. Basados en Spatie ($authUser->can('Acción:Modelo')). Referencia: [Policies](file:///c:/laragon/www/finanzasPersonales/app/Policies).
- Registrar en AppServiceProvider ($policies) y con Filament Shield generar permisos para Resources y Pages.
- Reglas: sólo usuarios con permisos pueden crear/editar encuestas; sólo usuarios asignados pueden responder.

## Exportación
- Excel: usar pxlrbt/filament-excel en SurveyResource (TableAction) para exportar respuestas agregadas por usuario y por dimensión.
- PDF: añadir barryvdh/laravel-dompdf para exportar un reporte por encuesta (resumen y promedios por dimensión), con plantilla Blade accesible.

## Cálculo de Resultados
- Métricas por dimensión: promedio, mediana y distribución; mapear Likert a 1–5.
- Métricas globales: participación (% respondido), promedio general.
- Servicios: SurveyStatsService para agregados eficientes con consultas sum/avg grouped.

## Validación y Pruebas (Pest)
- Tests de asignación: casos "todos", por departamentos, actualización y exclusión de duplicados.
- Tests de validación de respuestas: requerido, tipos (bool, escala, likert, multi, text) y mapeos.
- Tests de resultados: agregados por dimensión y global.
- Tests de Policies: acceso a CRUD y responder según permisos y asignación.

## Rendimiento
- Índices en pivots y claves foráneas; consultas eager para questions/responses.
- Notificaciones en colas y chunking.
- Paginación en listados y Table lazy.

## Semilla de "Clima Laboral"
- Survey seed con dimensiones estándar: Satisfacción general, Relaciones con compañeros, Comunicación interna, Condiciones de trabajo, Reconocimiento profesional.
- Preguntas predefinidas tipo Likert (Nunca…Siempre) según la imagen proporcionada, agrupadas por item.

## Accesibilidad y UI
- Componentes Filament con labels claros, aria y descripciones.
- Diseño responsive del formulario de respuesta (2 columnas en desktop, 1 en móvil). Columns en Repeater según tipo.

## Integración y Entregables
- Nuevos modelos, migraciones y Resources Filament.
- Policies registradas y permisos con Shield.
- Servicio de asignación y notificaciones.
- Exportaciones Excel/PDF.
- Semilla de encuesta de clima laboral.
- Suite de tests Pest con cobertura de lógica clave.

¿Confirmas este plan para proceder con la implementación paso a paso?