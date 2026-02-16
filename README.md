# Finanzas Personales

Plataforma web para gestionar procesos internos de personas (RRHH), construida sobre Laravel 12 y Filament 4.  
Permite registrar gestionar tareas, encuestas, evaluaciones de desempeño, reuniones, archivos y mucho más, todo desde un único panel administrativo.

<p align="left">
  <img src="https://img.shields.io/badge/PHP-%5E8.2-777BB4?logo=php" alt="PHP 8.2">
  <img src="https://img.shields.io/badge/Laravel-12.x-FF2D20?logo=laravel" alt="Laravel 12">
  <img src="https://img.shields.io/badge/Filament-4.x-F472B6" alt="Filament 4">
  <img src="https://img.shields.io/badge/License-MIT-10B981" alt="MIT License">
</p>

---

## 🚀 Características principales

- **Tareas y productividad**
  - Gestión de tareas con estados (pendiente, en progreso, completada).
  - Indicadores de tareas del año actual e integración con el dashboard.
- **Gestión de personas (RRHH)**
  - Gestión de empleados, cargos, departamentos y compañías.
  - Módulo de nóminas con ficha PDF del empleado.
  - Registro de ausencias, licencias médicas y vacaciones.
- **Evaluaciones de desempeño**
  - Definición de ciclos de evaluación.
  - Objetivos estratégicos y objetivos por empleado.
  - Seguimientos periódicos (check-ins) y cálculo automático de desempeño y bonos.
- **Encuestas internas**
  - Creación y publicación de encuestas.
  - Landing pública para acceso a encuestas.
  - Exportación de respuestas y generación de reportes.
- **Calendarios y reuniones**
  - Sincronización con calendarios CalDAV.
  - Gestión de reuniones e integración con Zoom.
- **Gestión de archivos**
  - Explorador de archivos tipo “drive” con carpetas y permisos por usuario/rol.
  - Compartir archivos mediante enlaces públicos con expiración y/o código de acceso.
  - Soporte WebDAV para montar el repositorio como unidad de red.
  - Edición online de documentos mediante OnlyOffice.
- **Colaboración y comunicación**
  - Chat interno en tiempo real (Wirechat + Laravel Reverb).
  - Webmail integrado mediante IMAP/CPANEL.
- **Panel administrativo moderno**
  - Construido con **Filament 4**.
  - Roles y permisos con **Filament Shield**.
  - Selector de idioma (es/en) mediante **laravel-lang** y language switcher.

---

## 🧱 Tecnologías

- **Backend**
  - PHP 8.2+
  - Laravel 12.x
  - Filament 4 (panel administrativo, recursos, widgets)
  - Laravel Reverb (websockets y tiempo real)
  - PestPHP (testing)
- **Integraciones clave**
  - Zoom (videoconferencias)
  - CalDAV / SabreDAV (calendarios)
  - WebDAV + OnlyOffice (gestión de archivos y edición colaborativa)
  - Wirechat (chat interno)
- **Frontend**
  - Vite + Tailwind CSS (a través del stack por defecto de Laravel)
  - Livewire + Filament widgets para dashboards y gráficos.

---

## ✅ Requisitos

- PHP **8.2** o superior.
- Extensiones típicas de Laravel habilitadas (mbstring, openssl, pdo, etc.).
- Composer.
- Node.js y npm.
- Servidor de base de datos (MySQL/MariaDB, PostgreSQL u otro soportado por Laravel).

---

## ⚙️ Puesta en marcha en local

Clona el repositorio y entra en el directorio del proyecto:

```bash
git clone <tu-repo.git> finanzasPersonales
cd finanzasPersonales
```

Instala las dependencias de PHP:

```bash
composer install
```

Copia el archivo de entorno y genera la clave de la aplicación:

```bash
cp .env.example .env
php artisan key:generate
```

Configura en `.env` al menos:

- Conexión a base de datos (`DB_*`).
- URL de la aplicación (`APP_URL`).
- Credenciales de correo (`MAIL_*`).
- Parámetros de broadcasting/queue/websockets según tu entorno (Reverb).
- Integraciones opcionales: Zoom, WebDAV/OnlyOffice, cuentas de correo, etc.

Ejecuta las migraciones (y opcionalmente seeders de ejemplo si los habilitas):

```bash
php artisan migrate
# php artisan db:seed   # opcional
```

Instala dependencias de frontend y compila los assets:

```bash
npm install
npm run dev   # para entorno de desarrollo
```

También puedes usar los scripts definidos en `composer.json`:

```bash
composer run setup   # instala backend, genera .env, migra y build de frontend
composer run dev     # arranca servidor PHP, cola y Vite en paralelo
```

Accede a la aplicación en tu navegador (por defecto):

- `http://localhost:8000` para el frontend principal.
- Panel Filament (admin): típicamente `/admin` (ajústalo según tu configuración).

---

## 🧪 Pruebas

El proyecto utiliza **PestPHP** para las pruebas automatizadas. Puedes ejecutar el suite completo con:

```bash
composer test
```

Antes de commitear, es recomendable ejecutar:

- `composer test` para validar el backend.
- `npm run build` para asegurar que el frontend compila correctamente.

---

## 🗂 Estructura destacada del proyecto

Algunos directorios relevantes:

- `app/Filament/Pages`  
  Páginas personalizadas del panel (dashboards, chats, file manager, etc.).
- `app/Filament/Resources`  
  Recursos de Filament para ingresos, gastos, tareas, empleados, evaluaciones, encuestas, calendarios, etc.
- `app/Livewire`  
  Widgets dinámicos de dashboard (gráfico de resumen anual, gastos por categoría, contadores, marcadores, etc.).
- `app/Services`  
  Servicios de integración (CalDav, Zoom, IMAP, WebDAV, encuestas, AI de apreciación, etc.).
- `app/Http/Controllers`  
  Controladores para formularios públicos, encuestas, WebDAV, OnlyOffice, compartición de archivos, etc.
- `lang/es` y `lang/en`  
  Traducciones para panel, dashboards, evaluaciones, nóminas, encuestas y otros módulos.

---

## 🤝 Contribución

Las contribuciones son bienvenidas. Antes de abrir un PR:

- Sigue las convenciones de codificación de Laravel/Filament.
- Mantén la nomenclatura y estructura actuales de recursos y servicios.
- Asegúrate de que las pruebas pasan correctamente.

---

## 📄 Licencia

Este proyecto está disponible bajo la licencia **MIT**, en línea con el esqueleto oficial de Laravel. Puedes utilizarlo, modificarlo y redistribuirlo respetando los términos de dicha licencia.

---

Si necesitas adaptar este proyecto a un caso de uso específico (solo finanzas personales, solo RRHH, etc.), la arquitectura basada en recursos Filament y servicios desacoplados facilita habilitar o deshabilitar módulos según tus necesidades.
