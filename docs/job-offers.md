# Módulo de Ofertas Laborales

## Instalación
- Ejecutar migraciones:
  - php artisan migrate
- Sembrar rol público:
  - php artisan db:seed --class=Database\\Seeders\\PublicRoleSeeder

## Panel RR.HH. (Filament)
- Recurso: Ofertas Laborales en el grupo "Recursos Humanos".
- Permisos: Gestionados por Filament Shield. Asignar a quienes pertenezcan a RR.HH.
- Acciones:
  - Crear, editar, eliminar.
  - Publicar/Despublicar.

## Página Pública
- URL: /trabaja-con-nosotros
- Búsqueda y filtros: `?q=&location=&contract_type=`
- Paginación: 10 por página.
- Cache: 30 min (invalida por timestamp de actualización).
- SEO: Meta description + canonical.

## Dashboard Público
- URL: /mi-panel (auth + rol `public`, no internos)
- Muestra ofertas recientes e información básica del candidato.

## Registro y Roles
- Al crear usuarios externos (is_internal=false), se asigna rol `public` automáticamente.
- Seeder crea permisos `job-offers.view` y `job-offers.apply`.

## Notificaciones (estructura)
- Evento `JobOfferPublished` + listener `SendNewJobOfferNotification` que notifica a usuarios con rol `public`.

## Pruebas
- Pest habilitado con base en memoria SQLite.
- Ejecutar: `composer test`

## Seguridad
- Acceso a gestión de ofertas restringido por permisos de Shield.
- Páginas públicas no requieren autenticación.

