# Servicio IMAP

## Requisitos del servidor
- Puerto IMAP seguro: 993
- SSL/TLS habilitado
- Acceso IMAP disponible para las cuentas de correo

## Configuración necesaria
- PHP con extensión `imap` habilitada
- El modelo `EmailAccount` debe contener:
  - `email` (formato usuario@dominio)
  - `domain` (dominio del correo)
  - `encrypted_password` (cifrada, desencriptable vía `decrypted_password`)
  - `username` opcional; por defecto se usa la parte previa a `@`

## Protocolos y seguridad
- Conexión segura: `imap/ssl` en `:993`
- Fallback de host: `mail.{dominio}` y `{dominio}`
- `novalidate-cert` habilitado para entornos sin CA configurada

## Uso
1. El servicio `ImapService` expone:
   - `unreadCount(EmailAccount $account): int`
2. La página `Webmail` muestra badge en la navegación:
   - `getNavigationBadge()` devuelve el número de no leídos
   - Color del badge: `danger`

## Manejo de errores
- Conexiones fallidas: excepción con mensaje del servidor (`imap_last_error`)
- Credenciales inválidas: error al abrir IMAP
- Servidor no disponible: fallback de host y excepción final
