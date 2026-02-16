---
name: "filament4-textentry"
description: "En Filament 4 usa Infolists TextEntry en modales y detalles. Invocar cuando se implementen/migren modales de tablas o al resolver deprecaciones de Placeholder."
---

# Filament 4: Usar TextEntry (no Placeholder)

Esta skill establece la regla de uso en Filament 4: no utilizar `Placeholder` para contenido de visualización en modales de acciones y detalles; debe emplearse `Infolists\Components\TextEntry`. En formularios (Schemas), usar componentes de Forms como `ViewField` para incrustar vistas.

## Cuándo invocar
- Al crear o modificar modales de acciones en Tablas (ViewAction, RecordActions).
- Al mostrar detalles con Infolists en Filament 4.
- Al migrar desde versiones anteriores donde se usaba `Placeholder`.
- Cuando aparezcan diagnósticos que marquen `Placeholder` como deprecado.

## Reglas
- Infolists/Acciones de Tablas: usar `TextEntry` para mostrar valores.
- Enlaces/HTML: usar `->html()` y `->formatStateUsing()` para generar contenido enriquecido.
- Formularios (Schemas): para vistas de solo lectura, usar `Forms\Components\ViewField` en lugar de `Placeholder`.

## Ejemplos

Mostrar archivo descargable en modal de detalle:

```php
use Filament\Infolists\Components\TextEntry;

TextEntry::make('data.curriculum')
    ->label('Curriculum')
    ->formatStateUsing(function ($state, $record) {
        if (is_string($state)) {
            $decoded = json_decode($state, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $state = $decoded;
            }
        }
        if (!is_array($state) || !isset($state['original'])) {
            return '-';
        }
        $url = route('formbuilder.download', [
            'formId' => $record->form_id ?? 'form',
            'submissionId' => $record->submission_id,
            'field' => 'curriculum',
        ]);
        return '<a href="'.$url.'" target="_blank" style="color:#288cfa;text-decoration:none">'
            . e($state['original']) . '</a>';
    })
    ->html();
```

Incrustar una vista en un formulario (constructor de temas / vista previa):

```php
use Filament\Forms\Components\ViewField;

ViewField::make('preview')
    ->view('filament.pages.formbuilder.preview', [
        'elements' => fn ($get) => $get('elements'),
        'name' => fn ($get) => $get('name'),
        'themeId' => fn ($get) => $get('themeId'),
        'button' => fn ($get) => $get('button'),
        'themes' => $this->themes,
    ]);
```

## Beneficio
- Evita errores y deprecaciones.
- Mantiene compatibilidad con Filament 4 en Infolists y Forms.
