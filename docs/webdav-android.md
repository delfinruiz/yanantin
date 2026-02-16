# Configuración WebDAV en Android y OnlyOffice

## Servidor
- URL: https://laravel.micode.cl/dav/
- Autenticación: Basic (usuario = correo o nombre, contraseña = de la cuenta)
- Requisitos: Certificado HTTPS válido, sin proxy que filtre Authorization

## Clientes Android
- OnlyOffice: Agregar almacenamiento WebDAV con la URL y credenciales
- Solid Explorer / CX File Explorer: Añadir cuenta WebDAV con los mismos datos

## Pruebas
- Preflight: `curl -I https://laravel.micode.cl/dav/`
- Challenge: `curl -i -X PROPFIND https://laravel.micode.cl/dav/ -H "Depth: 0"`
- Autenticado: `curl -i -X PROPFIND https://laravel.micode.cl/dav/ -H "Depth: 0" -u "usuario:clave"`

## Windows (conectividad)
- Controladores: `devmgmt.msc` → Adaptadores de red → Actualizar
- TCP/IP: `ipconfig /all`, `Get-NetIPConfiguration`
- Firewall: `wf.msc` y reglas para 80/443
- DNS: `nslookup laravel.micode.cl`, `Resolve-DnsName laravel.micode.cl`
- Pruebas: `Test-NetConnection laravel.micode.cl -Port 443`, `tracert laravel.micode.cl`

