## Alcance
- Implementar chat con rooms públicas/privadas y privados 1:1 dentro de Filament v4.
- Tiempo real usando exclusivamente Laravel Reverb (driver `broadcasting=reverb`) + Wirechat (UI Livewire).
- Sin servicios de pago externos ni librerías adicionales.

## Reverb Nativo: Configuración y Canales
- `config/broadcasting.php`: setear `default => 'reverb'`.
- `config/reverb.php`: habilitar servidor WS nativo; `keepalive_interval`, `max_connections`, `disconnect_on_idle`.
- `routes/channels.php`:
  - Canal privado conversación: `conversation.{id}` con callback que autoriza si el usuario pertenece a `conversation_user`.
  - Canal presencia room: `room.{id}` para presencia y moderación.
  - Canal privado invitaciones: `user.invites.{userId}` para notificar nuevas invitaciones.
- Eventos de broadcast (sin paquetes): `MessageCreated`, `RoomInviteCreated`, `RoomMembershipChanged` usando `implements ShouldBroadcast` con `broadcastOn()` a canales anteriores.

## Optimización de Recursos (Cero Costos adicionales)
- Sin Pusher ni servicios de terceros.
- Limpieza automática:
  - Reverb: timeouts de desconexión y cierre de sockets inactivos.
  - Jobs nocturnos para purgar `room_invites` expirados y `messages` soft-deleted (si aplica).
- O(1) operaciones básicas:
  - Autorización de canal: lookups por índices (FK + unique) y cacheada per-request con `Cache::remember` (array driver en testing, file/redis en prod).
  - Emisión de eventos: constante por conversación (un canal por id).
- Limitar almacenamiento persistente:
  - Mensajes paginados; sin logs de socket persistentes.
  - Auditoría compacta (JSON pequeño en `admin_audits`).

## Esquema de Base de Datos
- `rooms(id, name, description, visibility['public','private'], created_by, conversation_id, timestamps)`
- `conversations(id, type['room','private'], room_id nullable, created_by, timestamps)`
- `messages(id, conversation_id, user_id, body, attachment_path nullable, read_at nullable, timestamps)`
- `room_user(room_id, user_id, role['member','moderator'], timestamps, unique(room_id,user_id))`
- `conversation_user(conversation_id, user_id, timestamps, unique(conversation_id,user_id))`
- `room_invites(id, room_id, invited_user_id nullable, token unique, expires_at, created_by, accepted_at nullable, timestamps)`
- `admin_audits(id, admin_id, action, target_type, target_id, metadata json, created_at)`

## Modelos y Relaciones
- Room: hasOne Conversation; belongsToMany Users (pivot role); hasMany Invites; belongsTo creator.
- Conversation: belongsTo Room; belongsToMany Users; hasMany Messages; scope `forUser($id)`.
- Message: belongsTo Conversation y User.
- RoomInvite: belongsTo Room y opcional User; helpers para token y aceptación.
- AdminAudit: morphTo target; belongsTo admin.

## Policies / Gates
- Gate `isAdmin`.
- RoomPolicy: create/update/delete solo admin; view públicas o privadas si miembro; joinPublic cualquiera autenticado; moderate admin/moderator.
- ConversationPolicy: view/sendMessage solo participantes.
- MessagePolicy: delete admin/moderator.

## Servicios
- ConversationService: `firstOrCreatePrivate($a,$b)`; `attachRoomConversation(Room,$userIds)`.
- RoomService: `createPublicRoom`, `createPrivateRoom`, `inviteByLink`, `acceptInvite`.
- AuditService: `log(admin, action, target, metadata)`.
- ReverbService (opcional): helpers para nombres de canales y emisión de eventos.

## Filament: RoomResource (solo admin)
- Form: name, description, visibility, users (Select::multiple con búsqueda y rol).
- Acciones: Generar enlace de invitación, Gestionar miembros, Moderación (ban/mute opcional).
- Tabla: columns (name, visibility badge, members_count, created_at), filtros por visibility y búsqueda por nombre.
- Hooks: sincronizar `room_user` y `conversation_user`; crear/adjuntar conversación automáticamente.

## Filament: Página ChatPage
- Sidebar: rooms públicas (join/leave), privadas (miembro) y privados 1:1; búsqueda.
- Área principal: render Wirechat `livewire:wirechat.chat` (alias ajustable), pasando `conversationId`.
- Botón "Nuevo chat" 1:1: select usuario → `ConversationService::firstOrCreatePrivate` → autorizar y abrir conversación.
- Validación: antes de montar, `authorize('view', $conversation)`.

## Notificaciones en Tiempo Real (Reverb)
- Invitaciones privadas: `RoomInviteCreated` a `user.invites.{userId}`.
- Mensajes: `MessageCreated` a `conversation.{id}`.
- Cambios de membresía: `RoomMembershipChanged` a `room.{id}`.

## Pruebas y Monitoreo
- Unit tests
  - Autorización O(1): medir tiempo medio (microtime) en N autorizaciones y confirmar estabilidad (varianza mínima).
  - Memoria: `memory_get_usage()` antes/después de emitir y autorizar; asegurar crecimiento ∼0 y límites fijos.
  - Invitación y aceptación: flujo completo sin dependencias externas.
- Monitoreo en tiempo real
  - Livewire panel en Filament para mostrar conexiones activas, uso de memoria (`memory_get_usage()`), mensajes por segundo.
  - Métricas expuestas vía endpoint interno `/metrics/reverb` (sin almacenar, solo cálculo on-demand).

## Documentación (breve en código)
- Limitaciones: sin persistencia de estado WS; métricas estimadas in-memory; O(1) aproximado bajo índices.
- Contextos recomendados: rooms de tamaño medio; paginación de mensajes; timeouts agresivos para desconexión.

## Orden de Implementación
1) Configurar Reverb + canales.
2) Migraciones y modelos.
3) Policies/Gates.
4) Servicios (Room/Conversation/Audit).
5) RoomResource.
6) ChatPage + Wirechat con autorización.
7) Eventos de broadcast.
8) Pruebas unitarias y panel de monitoreo.

¿Apruebas este plan con Reverb nativo para continuar con la implementación? 