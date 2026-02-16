## Objetivo
Agregar la opción "Toma de conocimiento" para compartidos internos (usuario→usuario) en el FileManager, exigiendo un código único enviado por email al destinatario antes del primer acceso. Reutilizar los modales existentes y mantener Filament v4.

## Cambios de UI (Filament v4)
- Modal Compartir: añadir por destinatario el checkbox "Toma de conocimiento requerida" y mostrar estado (Pendiente/Completada), última fecha de envío y botón "Reenviar código".
- Modal Compartido con: listar destinatarios con badge del estado de toma de conocimiento y acción de reenvío.
- Lista "Compartidos conmigo": mostrar un indicador/badge cuando el archivo requiera toma de conocimiento pendiente.
- Al abrir un archivo compartido pendiente: mostrar modal Filament con campo "Código de confirmación" y acción "Validar". Si valida, cerrar modal y abrir el archivo.

## Flujo de verificación
1. Origen activa "Toma de conocimiento requerida" al crear/editar el compartido.
2. Se genera un código único (6–8 dígitos), se almacena en hash, se envía por email al destinatario.
3. Destinatario hace clic en el archivo; si pendiente, aparece el modal para ingresar el código.
4. Backend valida el código (hash), registra el éxito y marca acknowledged_at.
5. Tras validación, se permite el acceso inmediato y futuros accesos sin pedir código.

## Integraciones en backend
- Control de acceso: extender FilePreviewController para consultar el pivot de compartidos y bloquear si ack_required=true y acknowledged_at=NULL.
- Endpoint previo (AJAX): nueva ruta GET /file/ack-status/{fileItem} que devuelve JSON {ack_required, acknowledged}. El cliente lo consulta antes de abrir para decidir si mostrar el modal.
- Acción de verificación: método en FileManager Livewire/Filament verifyAcknowledgment(fileItemId, code) que valida, actualiza acknowledged_at y retorna éxito.
- Reenvío de código: acción resendAcknowledgmentCode(fileItemId, userId) con rate-limit.

## Modelo y esquema de datos
- Tabla file_item_shares (pivot): añadir columnas
  - ack_required (boolean, default false)
  - ack_code_hash (string, nullable)
  - ack_code_expires_at (timestamp, nullable)
  - ack_code_sent_at (timestamp, nullable)
  - ack_attempts (unsigned smallint, default 0)
  - ack_last_attempt_at (timestamp, nullable)
  - acknowledged_at (timestamp, nullable)
- Logs: nueva tabla file_item_share_ack_logs
  - file_item_id, origin_user_id, target_user_id, success(bool), ip, user_agent, attempted_at.

## Generación y envío de códigos
- Generación: random_int y formato numérico (6–8 dígitos), almacenar solo el hash (bcrypt/argon2id), TTL configurable (p.ej., 24h).
- Envío: Notification/Mailable "AcknowledgmentCode" con cola (jobs). Plantilla clara, incluye nombre del archivo y caducidad.
- Reenvío: invalida el código previo, genera uno nuevo y actualiza timestamps.

## Seguridad
- Validar permisos antes de cualquier acción (origen y destino).
- Rate limit en verificación y reenvío (throttle por IP/usuario y ventana temporal).
- No almacenar el código en texto plano; comparar con hash.
- Expirar código y bloquear intentos tras N fallos; registrar en logs.
- Auditar todos los eventos de envío y validación.

## Experiencia de usuario
- Feedback inmediato: badges de estado, toasts de éxito/error, contador de intentos.
- Acceso transparente tras validación: no volver a pedir código.
- Reutilización: los modales existentes se extienden con campos/acciones adicionales.

## Extensión opcional (enlaces públicos)
- Añadir ack_required y campos equivalentes a file_share_links para exigir código en acceso por token (PublicShareController). Mostrar pantalla de código antes de renderizar el recurso público.

## Testing
- Unit: generación de código (formato/entropía), hash/validación, expiración y límites.
- Feature: flujo completo de origen→destino (set, envío, verificación, acceso), reenvío, bloqueo por intentos.
- UI: pruebas Livewire de acciones y modales, estados y transiciones.

## Tareas técnicas
- Migración pivot y creación de tabla de logs.
- Notificación/Mailable y Job de envío.
- Métodos en FileManager (set/reenvío/verificación) y vista/acciones.
- Endpoint ack-status y lógica en FilePreviewController.
- Mensajería y badges en UI, reutilizando componentes Filament.

¿Confirmas que procedamos con esta implementación?