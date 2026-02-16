## Elección de autenticación
- Server-to-Server OAuth (recomendado): acceso a nivel de cuenta sin consentimiento de usuario; ideal para crear/gestionar reuniones desde tu backend.
- OAuth con consentimiento de usuario: útil si cada usuario de Zoom debe autorizar y actuar como host propio. Tokens requieren refresh.

## Crear la app en Zoom Marketplace
- Accede a marketplace.zoom.us y crea una app:
  - Opción 1: Server-to-Server OAuth → obtén Client ID, Client Secret y Account ID.
  - Opción 2: OAuth → define Redirect URL(s) y obtén Client ID/Secret.
- Habilita permisos relacionados con reuniones y usuarios (lectura/escritura) según lo que necesites.
- Publica/activa la app dentro de tu cuenta.

## Configurar credenciales en Laravel (.env)
- Añade variables de entorno:
  - ZOOM_CLIENT_KEY=... 
  - ZOOM_CLIENT_SECRET=...
  - ZOOM_ACCOUNT_ID=... (solo S2S)
- No comprometas secretos; usa .env y (opcional) .env.example sin valores.

## Instalar y configurar la librería Composer
- Instala: composer require jubaer/zoom-laravel
- Publica config y registra provider/alias si aplica:
  - php artisan vendor:publish --provider="Jubaer\Zoom\ZoomServiceProvider"
  - Verifica config/zoom.php con client_id, client_secret, account_id y base_url.
- (Opcional) Soporte por usuario: el paquete permite definir credenciales por usuario si lo necesitas.

## Flujo de token y renovaciones
- Base Zoom API: https://api.zoom.us/v2/
- Todos los requests requieren token válido (~1 hora).
- Server-to-Server OAuth: solicita un token nuevo cuando caduque; la librería gestiona el flujo con tus credenciales.
- OAuth con consentimiento: usa refresh token para renovar automáticamente; asegura persistencia segura.

## Prueba rápida de conectividad
- Lista tu usuario (o “me”) para validar token:
```php
$user = Zoom::getUser('me');
```
- Crear una reunión básica:
```php
$meeting = Zoom::createMeeting([
  'topic' => 'Reunión de prueba',
  'type' => 2,
  'duration' => 30,
  'timezone' => 'America/Mexico_City',
  'start_time' => '2026-01-12T15:00:00Z',
  'password' => 'secure123',
  'settings' => [
    'waiting_room' => true,
    'join_before_host' => false,
    'auto_recording' => 'cloud'
  ],
]);
```

## Endpoints clave (referencia)
- Users: GET /users/me 
- Meetings: POST /users/{userId}/meetings, GET/PATCH/DELETE /meetings/{meetingId}
- Recordings/Reports según tu roadmap.

## Webhooks (post-meeting, auditoría)
- Configura suscripciones de eventos en tu app y define un endpoint HTTPS que acepte POST JSON.
- Responde 200/204 en ≤3s y verifica autenticidad del webhook.
- Útil para asistencia, duración, grabaciones disponibles y estados finales.

## Seguridad y buenas prácticas
- Almacena secretos solo en .env; rota credenciales periódicamente.
- Usa HTTPS para callbacks/webhooks y valida firmas.
- Define límites y reglas de negocio (duración máxima, horarios, aprobación) desde tu módulo.

## Próximos pasos en el módulo Filament
- Fase 1: crear/editar/eliminar reuniones, compartir interno, dashboard básico.
- Fase 2: webhooks, asistencia, grabaciones.
- Fase 3: automatizaciones, métricas, integración con tareas/calendario.

¿Confirmas este enfoque (preferencia por Server-to-Server OAuth) para avanzar con la implementación y wiring en tu proyecto Laravel?