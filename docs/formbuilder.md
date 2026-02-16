# Constructor de Formularios Dinámico

## Esquema JSON
- FormDefinition
  - id: ULID
  - name: string
  - slug: string|null
  - version: int
  - themeId: string|null
  - elements: array de elementos
- Element
  - id?: string
  - type: text|number|email|url|textarea|select|radio|checkbox|date|datetime|file
  - label: string
  - name: string
  - props: { placeholder?: string, options?: {label: value}[] }
  - validations: { required?: bool, min?: int, max?: int, regex?: string, file?: { mimes?: [], max?: int } }
- Theme
  - id: ULID
  - name: string
  - tokens: { colors, fonts, spacing, radius }

## Almacenamiento
- storage/app/formbuilder/forms/{formId}.json
- storage/app/formbuilder/themes/{themeId}.json
- storage/app/formbuilder/submissions/{formId}.ndjson
- uploads en storage/app/formbuilder/uploads/{formId}/{fecha}/{submissionId}/

## API
- GET /forms/{id}: render del formulario (página completa)
- GET /forms/{id}/embed: render minimal para iframe
- GET /forms/{id}/definition.json: definición JSON del formulario
- POST /forms/{id}/submit: envío de datos y persistencia en NDJSON

## Código embebido
```html
<iframe
  src="https://dominio/forms/{id}/embed"
  width="100%"
  style="border:0;overflow:hidden;"
  scrolling="no"
></iframe>
```
```html
<div id="form-{id}"></div>
<script>
(function () {
  var d = document;
  var container = d.getElementById('form-{id}');
  if (!container) return;
  var f = d.createElement('iframe');
  f.src = 'https://dominio/forms/{id}/embed';
  f.style.border = '0';
  f.style.width = '100%';
  f.style.overflow = 'hidden';
  f.setAttribute('scrolling', 'no');
  container.appendChild(f);
  function receive(e) {
    if (!e.data || e.data.type !== 'formbuilder:resize' || e.data.id !== '{id}') return;
    f.style.height = e.data.height + 'px';
  }
  window.addEventListener('message', receive, false);
})();
</script>
```

## Exportación a Excel
- Vista administrativa “FormSubmissions” muestra envíos como tabla.
- Acción ExportAction (pxlrbt/filament-excel) exporta a XLSX usando columnas derivadas de la definición del formulario.

## Seguridad
- Throttling en rutas públicas.
- CSRF en envíos dentro del iframe.
- Validación estricta de archivos (mimes, tamaño).

## Demo
- Crear formulario en “Form Builder” (Filament).
- Previsualizar /forms/{id}.
- Insertar iframe en una página externa y enviar datos.
- Consultar “FormSubmissions” y exportar XLSX.
