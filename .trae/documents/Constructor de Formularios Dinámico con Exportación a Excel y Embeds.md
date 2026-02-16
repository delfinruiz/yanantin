## Visión General
- Implementar un constructor de formularios modular con UI moderna y drag-and-drop sobre Filament v4 + Livewire + Tailwind.
- Definir formularios y temas en JSON, almacenados en el sistema de archivos.
- Renderizar formularios por ID vía endpoints públicos y generar código embebido (iframe/script) autocontenido.
- Capturar envíos en archivos JSON/NDJSON por formulario y ofrecer exportación a XLSX con pxlrbt/filament-excel.

## Stack Técnico
- Backend: Laravel (10/11), Filament v4 (admin), Livewire, Tailwind CSS.
- Drag-and-drop: SortableJS en el canvas del builder.
- Almacenamiento: storage/app/formbuilder/{forms|themes|submissions}.
- IDs: ULID por formulario/tema.
- Exportación: pxlrbt/filament-excel 3.x + Laravel-Excel.

## Modelo de Datos (JSON)
- FormDefinition JSON
  - id, name, slug, version
  - layout: rows/columns, order
  - elements: [{ id, type, label, name, props, validations }]
  - themeId
- Theme JSON
  - id, name
  - tokens: { colors, fonts, spacing, radius }
- Submission NDJSON por formulario
  - Cada línea: { submission_id, submitted_at, data: { field -> value }, meta }

## Almacenamiento en FS
- storage/app/formbuilder/forms/{formId}.json
- storage/app/formbuilder/themes/{themeId}.json
- storage/app/formbuilder/submissions/{formId}.ndjson
- Uploads: storage/app/formbuilder/uploads/{formId}/

## Constructor (UI)
- Página Filament “Form Builder” con 3 zonas:
  - Paleta: tipos de campo (texto, número, select, fecha, datetime, checkbox, radio, textarea, file, email, url).
  - Canvas: grid editable con drag-and-drop (SortableJS) para secciones/filas/columnas y elementos.
  - Panel de propiedades: edición en tiempo real (etiqueta, name, tipo, opciones, validaciones, estilos).
- Acciones: crear/duplicar/eliminar secciones y campos, previsualizar, guardar como JSON.

## Validaciones
- Generación dinámica de reglas Laravel desde FormDefinition:
  - required, min/max, regex, email, url, date, file: {mimes, max}.
- Mensajes configurables en el panel de propiedades.

## Motor de Renderizado
- Endpoint GET /forms/{id}
  - Resuelve FormDefinition + Theme
  - Renderiza Blade/Livewire con estilos vía CSS variables del tema.
- Endpoint POST /forms/{id}/submit
  - Valida input según definiciones
  - Persiste en NDJSON y maneja uploads
  - Rate-limit y sanitización

## Código Embebido
- Iframe
  - <iframe src="https://dominio/forms/{id}/embed" width="100%" height="auto" style="border:0"></iframe>
- Script embebible
  - <script src="https://dominio/embed.js" data-form="{id}"></script>
  - Inserta contenedor y carga el formulario vía GET /forms/{id}/embed
- CORS y Content-Security-Policy ajustadas para orígenes permitidos.

## Temas y Diseño
- Theme tokens en JSON; aplicación como CSS variables en el contenedor del formulario.
- Selector de tema en el builder y guardado reutilizable.
- Soporte de tipografías, colores primarios/secundarios, spacing, radios y estados (hover/focus/error).

## Administración (Filament)
- Listado de Formularios: crear/editar/duplicar/borrar, vista previa.
- Submissions Page por formulario:
  - Tabla con columnas dinámicas derivadas de elements
  - Filtros por fecha y campos
  - Exportación a Excel

## Exportación a Excel (pxlrbt/filament-excel)
- Integrar ExportAction en la vista de envíos.
- Exporter personalizado que convierte NDJSON -> Collection/array y define columnas desde FormDefinition.
- Configuración: filename dinámico, writer XLSX, headings amigables; soporta sólo columnas visibles/seleccionadas.

## Seguridad y Cumplimiento
- CSRF exento sólo en /forms/{id}/submit para embed;
- Verificación de origen y HMAC simple con formId para evitar abuso.
- Rate limiting por IP/formulario; sanitización XSS de labels/placeholders; validación de mimes y tamaño en uploads.

## Endpoints y Rutas
- GET /forms/{id}
- GET /forms/{id}/embed
- GET /forms/{id}/definition.json (opcional)
- POST /forms/{id}/submit
- GET /forms/{id}/submissions (admin)
- GET /forms/{id}/submissions/export (accionada por Filament Excel)

## Entregables
- UI administrativa completa: builder, editor, preview, gestión de temas.
- Generador de código embebido por formulario.
- Página de demo con formulario embebido.
- Exportación XLSX desde vista de envíos con pxlrbt/filament-excel.
- Documentación técnica: esquema JSON, endpoints, uso de embed, seguridad.
- Responsivo y probado en Chrome/Firefox/Safari/Edge.

## Plan de Implementación por Fases
1) Fundaciones: estructuras JSON, servicios de almacenamiento, ULIDs, endpoints base.
2) Builder UI: paleta, canvas drag-and-drop, panel propiedades, guardado.
3) Render y embed: motor de render, iframe/script, estilos por tema.
4) Submissions: validación, NDJSON, uploads, administración de envíos.
5) Export: exporter pxlrbt/filament-excel ajustado a NDJSON, acciones en Filament.
6) Seguridad, documentación y demo.

## Pruebas
- Unit: validación dinámica, conversión NDJSON->Excel, almacenamiento FS.
- Feature: render/submit/embed; rate-limit; export XLSX.
- E2E: constructor->render->submit->export.
