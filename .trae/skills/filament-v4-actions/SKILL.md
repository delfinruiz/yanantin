---
name: "filament-v4-actions"
description: "Guía sobre Acciones en Filament v4 (namespace unificado). Invocar al trabajar con acciones en tablas, formularios o infolists en Filament v4."
---

# Filament v4 Actions Guide

En Filament v4, las acciones se han unificado en un solo namespace. Ya no existen namespaces separados como `Filament\Tables\Actions` para acciones de tabla.

## Namespace Unificado

Todas las acciones deben importarse desde `Filament\Actions`.

**Incorrecto (v3):**
```php
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\Action;
```

**Correcto (v4):**
```php
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
```

## Uso en Tablas

Las acciones de fila se definen en `recordActions()` (anteriormente `actions()` en algunos contextos, pero `recordActions` es específico para filas en v4 table builder si se usa standalone, aunque en Resources `actions()` sigue siendo común, el namespace de la CLASE de la acción es lo que importa).

```php
public function table(Table $table): Table
{
    return $table
        ->recordActions([ // O actions() dependiendo del contexto
            \Filament\Actions\EditAction::make(),
            \Filament\Actions\DeleteAction::make(),
            \Filament\Actions\Action::make('custom')
                ->action(fn () => ...),
        ])
        ->headerActions([
            \Filament\Actions\CreateAction::make(),
        ]);
}
```

## Puntos Clave
1. **Unificación**: `Filament\Actions\*` es el lugar para todas las acciones.
2. **Contexto**: Las acciones funcionan igual en Tablas, Formularios (Header Actions), e Infolists.
3. **Documentación**: Referencia oficial en `https://filamentphp.com/docs/4.x/actions/overview` y `https://filamentphp.com/docs/4.x/tables/actions`.
