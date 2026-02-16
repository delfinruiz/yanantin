## Enfoque Refinado
- Mantener el CRUD ya implementado para calendarios personales (crear, actualizar, eliminar).
- Añadir sincronización CalDAV exclusivamente como capa de integración en las acciones existentes.
- No modificar la lógica de calendarios públicos; garantizar aislamiento.

## Puntos de Integración
- Controlador de eventos: enganchar CalDAV en [CalendarEventController.php](file:///c:/laragon/www/finanzasPersonales/app/Http/Controllers/CalendarEventController.php) en `store`, `update`, `destroy` para calendarios personales.
- Derivar credenciales y endpoint CalDAV desde [EmailAccount.php](file:///c:/laragon/www/finanzasPersonales/app/Models/EmailAccount.php): `https://{dominio}:2080/calendars/{usuario@dominio}/calendar`.

## Servicio CalDAV (Sabre/DAV)
- `CalDavService` (nuevo):
  - `connect(emailAccount)` → Sabre\DAV\Client con HTTPS y auth básica.
  - `create(localEvent)` → PUT `{uid}.ics` (VCALENDAR/VEVENT vía Sabre\VObject), retorna ETag/UID.
  - `update(localEvent)` → PUT con manejo de ETag/412.
  - `delete(uid)` → DELETE del recurso remoto.
  - `syncDown(syncToken)` → REPORT incremental y mapeo ICS→Event.
  - `syncUp()` → subir pendientes locales.

## Metadatos Mínimos
- En Event: `caldav_uid`, `caldav_etag`, `caldav_last_sync_at`.
- En calendario personal del usuario: `caldav_sync_token`.
- Solo crear migraciones si estas columnas no existen.

## Flujo de Acción (CRUD → CalDAV)
- `store` (personal): crear local → llamar `CalDavService.create` → guardar `uid/etag` → confirmar.
- `update` (personal): actualizar local → `CalDavService.update` con ETag → refrescar `etag`.
- `destroy` (personal): eliminar local → `CalDavService.delete(uid)` → marcar borrado remoto.
- Manejar errores con reintentos exponenciales y códigos HTTP (401/403/404/5xx).

## Sincronización Bidireccional
- Comando `caldav:sync` y Job `CalDavSyncJob` por usuario:
  - `syncDown` usando `sync-token` para nuevos/cambiados/eliminados.
  - `syncUp` para pendientes locales.
- Programar de forma periódica; ejecutar también tras cambios locales (en cola) para baja latencia.

## Seguridad
- HTTPS obligatorio; nunca loggear credenciales.
- Validar que el calendario es personal antes de invocar CalDAV.

## Pruebas
- Unitarias: construcción VEVENT con RRULE/VALARM, manejo ETag, reintentos.
- Integración simulada: mocks de Sabre\DAV\Client para `store/update/destroy` y REPORT.
- Verificaciones: simple, recurrente, con alarmas; credenciales inválidas; no tocar calendarios públicos.

## Entregables
- Servicio `CalDavService` con métodos CRUD y sync.
- Integración en `CalendarEventController` para calendarios personales.
- Job/Comando de sincronización.
- Pruebas y documentación breve de configuración.

¿Confirmas este plan de integración para proceder?