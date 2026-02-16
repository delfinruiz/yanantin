## Diagnóstico WebDAV
- Revisar ruta y middleware en [routes/web.php](file:///c:/laragon/www/finanzasPersonales/routes/web.php#L40-L46) y alias en [bootstrap/app.php](file:///c:/laragon/www/finanzasPersonales/bootstrap/app.php#L15-L31).
- Inspeccionar autenticación Basic en [WebDavAuthenticator](file:///c:/laragon/www/finanzasPersonales/app/WebDav/WebDavAuthenticator.php) y challenge actual en [WebDavController](file:///c:/laragon/www/finanzasPersonales/app/Http/Controllers/WebDavController.php#L30-L35).
- Confirmar reenvío de Authorization en [.htaccess](file:///c:/laragon/www/finanzasPersonales/public/.htaccess#L8-L15) y compatibilidad con Nginx/FPM si aplica.
- Validar BaseUri y manejo de métodos DAV en [AllowWebDavMethods](file:///c:/laragon/www/finanzasPersonales/app/Http/Middleware/AllowWebDavMethods.php).

## Causa Raíz (esperada)
- El cliente Android/OnlyOffice envía PROPFIND sin credenciales; el servidor responde 500 en vez de 401 por challenge incompleto (faltan cabeceras DAV) y/o por baseUri sin slash final.

## Cambios a Implementar (Servidor WebDAV)
- Responder 401 con cabeceras: WWW-Authenticate: Basic realm="FileManager", DAV: 1,2, MS-Author-Via: DAV, Allow: métodos DAV.
- Usar respuesta Laravel (response('', 401)->withHeaders(...)) en lugar de header()+exit.
- Ajustar setBaseUri a "/dav/".
- Añadir logs: Authorization presente, PHP_AUTH_USER/PHP_AUTH_PW, Depth, Translate y cuerpo.
- Registrar excepciones SabreDAV con server->on('exception', ...) para capturar stack que produce 500.
- (Opcional) Permitir login por username además de email en WebDavAuthenticator.

## Pruebas WebDAV
- curl: PROPFIND con y sin -u, verificar 401→207.
- Apps Android: OnlyOffice WebDAV, Solid Explorer, CX File Explorer.
- Validar que no aparezca 500; revisar logs.

## Documentación para Android/OnlyOffice
- URL: https://laravel.micode.cl/dav/
- Auth: Basic (usuario = correo, contraseña = cuenta)
- Requisitos: HTTPS válido; sin proxy que filtre Authorization.

## Validación de Conectividad en Windows
- Controladores:
  - Comprobar adaptadores: `devmgmt.msc` → Adaptadores de red → actualizar.
  - `Get-NetAdapter` y `Get-NetAdapterAdvancedProperty` (PowerShell) para estado y propiedades.
- TCP/IP:
  - Verificar IPv4/IPv6, DHCP/estático: `ncpa.cpl` → Propiedades → Protocolo TCP/IPv4.
  - `ipconfig /all` y `Get-NetIPConfiguration` para confirmar configuración.
- Firewall:
  - Asegurar salida/entrada a 80/443 para apps WebDAV/OnlyOffice: `wf.msc` → reglas salientes/entrantes.
  - `Get-NetFirewallRule` y crear regla si es necesario.
- DNS:
  - `nslookup laravel.micode.cl` y `Resolve-DnsName laravel.micode.cl`.
  - Confirmar DNS del adaptador (preferido/alternativo).
- Cableado/Wi-Fi:
  - Validar ambos: desconectar/alternar, `ping` al host, `tracert` para ruta.
  - Comprobar potencia Wi-Fi y roaming.
- Pruebas de conectividad:
  - `Test-NetConnection laravel.micode.cl -Port 443`.
  - `curl -I https://laravel.micode.cl` y `curl -i -X PROPFIND https://laravel.micode.cl/dav/ -H "Depth: 0"`.

## Entregables
- Diagnóstico de causa raíz (autenticación/challenge y baseUri).
- Cambios aplicados en servidor WebDAV (challenge, baseUri, logs).
- Guía de configuración para clientes Android.
- Pruebas validadas con OnlyOffice y otros clientes.
- Checklist de conectividad Windows con comandos y verificaciones.

¿Apruebas este plan para proceder con la implementación y las pruebas? 