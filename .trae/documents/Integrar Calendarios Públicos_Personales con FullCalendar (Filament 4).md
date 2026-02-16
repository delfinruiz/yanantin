## Objetivo
- Integrar un calendario en Filament 4 usando Saade FullCalendar, donde:
  - El administrador crea calendarios públicos y asigna un encargado (editor).
  - Cada usuario autenticado posee un calendario personal por defecto.
  - El usuario puede alternar entre su calendario personal y los públicos disponibles.
  - Si es encargado de un calendario público, puede editar eventos de ese calendario y también los de su calendario personal.

## Modelado de Datos
- Calendar
  - Campos: id, name, description, color, is_public (bool), is_personal (bool), user_id (nullable para públicos, requerido para personales), manager_user_id (nullable), created_by.
  - Índices: unique(user_id) donde is_personal = true; FK a users para user_id y manager_user_id.
  - Relaciones: user (propietario del personal), manager (User), events (hasMany Event).
- Event
  - Campos: id (uuid opcional), calendar_id (FK), title, description, starts_at (datetime), ends_at (datetime), all_day (bool), color (opcional), created_by (user_id), metadata opcional.
  - Relaciones: calendar (belongsTo), creator (User).

## Autorización
- CalendarPolicy
  - view: públicos o personales del usuario; admin todo.
  - update: admin; o manager_user_id == usuario; personales sólo su propietario.
- EventPolicy
  - view: si puede ver el calendario asociado.
  - create/update/delete: admin; o (calendar.manager_user_id == usuario) para calendarios públicos; o (calendar.is_personal && calendar.user_id == usuario) para personales; siempre permitir al creador si coincide con los casos anteriores.
- Integración con Spatie Permission/Filament Shield (ya presentes) para permisos globales; usar Policies para reglas por dueño/encargado.

## Integración de FullCalendar (Saade)
- Instalar paquete: composer require saade/filament-fullcalendar:^3.0.
- Tema Filament
  - Importar CSS y views del plugin en resources/css/filament/admin/theme.css.
  - Confirmar panel y tema existentes: ver [AdminPanelProvider.php](file:///c:/laragon/www/finanzasPersonales/app/Providers/Filament/AdminPanelProvider.php) y [theme.css](file:///c:/laragon/www/finanzasPersonales/resources/css/filament/admin/theme.css).
- Widget CalendarWidget (extiende Saade\FilamentFullCalendar\Widgets\FullCalendarWidget)
  - Propiedades/config:
    - locale('es'), timezone(config('app.timezone')).
    - selectable(true), editable(true) condicionado por permisos del calendario seleccionado.
    - config([...]) para tooltips y comportamiento de drag & drop.
  - Selector de calendario:
    - Agregar un Select reactivo de "calendario" accesible para el usuario (personales + públicos visibles + donde sea encargado).
    - Al cambiar selección, recargar eventos.
  - fetchEvents($fetchInfo):
    - Consultar Event por rango $fetchInfo['start']–$fetchInfo['end'] filtrando por calendario seleccionado.
    - Devolver objetos EventData con id, title, start, end, url (a EventResource view) y color.
  - Acciones:
    - Crear al seleccionar día (on select): abrir modal con formulario (título, fechas, all_day). Preasignar calendar_id según selección.
    - Editar al arrastrar (drop/resize): actualizar starts_at/ends_at si EventPolicy::update autoriza; revertir si no.
    - Ver/Eliminar mediante acciones Filament condicionadas por policy.

## Recursos y Páginas en Filament
- CalendarResource (CRUD para admin)
  - Form: name, description, color, is_public, manager_user_id (User select), bloqueado para cambiar is_personal.
  - Table: listado con filtros por públicos/personales.
  - Policies: restringir creación/edición a admin; permitir ver personales.
- EventResource (CRUD opcional)
  - Form: calendar_id (limitado a calendarios autorizados), title, fechas, all_day, color, description.
  - Table: filtrar por calendario y rango de fechas.
  - Policies: usar EventPolicy.
- Página/Sección "Calendarios"
  - Página Filament que embeba CalendarWidget y un Select de calendario arriba (si la UI del widget no lo cubre).

## Creación Automática de Calendarios Personales
- UserObserver (created): crear Calendar is_personal=true, user_id=usuario, name="Calendario de {nombre}".
- Comando Artisan de backfill: para usuarios existentes sin calendario personal (único por usuario).

## Migraciones y Seeds
- Migraciones para calendars y events con FKs e índices.
- Seed opcional: crear ejemplo de calendario público y asignar un manager.

## Pruebas
- Unit/Feature
  - CalendarPolicy: visualizar públicos, personales; actualizar según manager/admin.
  - EventPolicy: crear/editar/borrar según reglas de encargado y propietario personal.
  - Comando backfill: garantiza un calendario personal por usuario.
- Widget
  - fetchEvents devuelve sólo eventos del calendario seleccionado y dentro de rango.

## Consideraciones de Rendimiento
- Índices sobre starts_at, ends_at y calendar_id.
- Paginación/limitación en EventResource; en widget limitar a rango visible.
- Cargar sólo calendarios autorizados y cachear lista por usuario.

## Compatibilidad con Modelos Existentes
- Existe Meeting como "evento" de reuniones: [Meeting.php](file:///c:/laragon/www/finanzasPersonales/app/Models/Meeting.php).
  - Mantener Meeting separado inicialmente.
  - Futuro: crear vista unificada que muestre Events y Meetings en el calendario con colores distintos, o migrar Meetings a Events si aplica.

## Entregables
- Nuevos modelos: Calendar, Event, Policies y Observer.
- Migraciones y comando backfill.
- CalendarResource y EventResource.
- CalendarWidget integrado en una página del panel.
- Import del CSS del plugin en el tema Filament.
- Pruebas de autorización y funcionalidad básica.

¿Confirmo este plan e inicio la implementación?