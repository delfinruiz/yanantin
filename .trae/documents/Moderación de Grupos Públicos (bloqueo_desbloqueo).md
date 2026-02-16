## Contexto Actual
- Wirechat ya soporta moderación vía acciones `REMOVED_BY_ADMIN` en `Participant`, con métodos `isRemovedByAdmin()` y `removeByAdmin()`.
- El `Participant` tiene `WithoutRemovedActionScope`, por lo que los usuarios bloqueados desaparecen de consultas normales y dejan de pertenecer al grupo (no pasan los checks de pertenencia).
- `Conversation::addParticipant()` impide re-agregar bloqueados salvo `undoAdminRemovalAction=true` (sirve como “desbloqueo”).

## Objetivo
- Permitir a administradores bloquear/desbloquear usuarios en grupos públicos, reflejar estado en UI y evitar auto-joins de bloqueados.

## Cambios Propuestos
### Acciones de Moderación en UI (Filament)
- En `GroupResource` (gestión del grupo):
  - Agregar acción “Bloquear usuario” sobre cada miembro: usar `$participant->removeByAdmin(Auth::user())`.
  - Agregar acción “Desbloquear usuario”: re-agregar con `$conversation->addParticipant($user, ParticipantRole::PARTICIPANT, undoAdminRemovalAction: true)`.
  - Mostrar insignia/estado “Bloqueado” (vista especial) listando miembros bloqueados: cargar participantes deshabilitando `WithoutRemovedActionScope`.

### Evitar Auto-Join para Bloqueados
- Ajustar el listener `JoinGlobalChat` y el alta en `UserObserver`:
  - Antes de agregar, consultar `Participant` y si `isRemovedByAdmin()` es true, no agregar.
  - Para otros grupos públicos (además de “General”), respetar el mismo criterio.

### Opcional: Aprobación de Miembros
- Exponer `admins_must_approve_new_members` del modelo `Group` en el formulario:
  - Si está activo, deshabilitar auto-join en login y ofrecer flujo de solicitudes de unión (pendiente de aprobación). 
  - Crear acciones “Aprobar/Rechazar” en la UI para solicitudes.

### Auditoría
- Mostrar en UI de grupo el historial de acciones (quién bloqueó/desbloqueó), reutilizando `actions` con tipo `REMOVED_BY_ADMIN`.

### Validaciones y Tests
- Pruebas:
  - Bloquear: usuario queda fuera de consultas, no puede enviar ni ser re-agregado automáticamente.
  - Desbloquear: vuelve a ser participante y visible.
  - Auto-join de login respeta bloqueos.

## Entregables
- Acciones Bloquear/Desbloquear y vistas en Filament.
- Ajustes de listeners para evitar re-ingreso automático de bloqueados.
- Opcional: bandera de aprobación y flujo de solicitudes.

¿Confirmas que implementemos primero bloqueo/desbloqueo y prevención de auto-join, y dejamos la aprobación de miembros como fase siguiente?