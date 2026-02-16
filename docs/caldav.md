# Sincronización CalDAV (Calendarios personales)

- Endpoint: `https://{dominio}:2080/calendars/{usuario@dominio}/calendar/`
- Autenticación: Basic (email completo + contraseña desencriptada de EmailAccount.encrypted_password)
- Librerías: sabre/dav (cliente DAV), sabre/vobject (iCalendar)

## Metadatos persistidos
- Event: `caldav_uid`, `caldav_etag`, `caldav_last_sync_at`
- Calendar: `caldav_sync_token`

## Flujo
- CRUD local invoca CalDavService para reflejar cambios remotos (solo calendarios personales).
- Job `CalDavSyncJob` y comando `caldav:sync` realizan syncDown y syncUp.

## Pruebas
- `tests/Unit/CalDavParserTest.php`: parseo de RRULE y VALARM.

