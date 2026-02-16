## Objetivo
Agregar un botón "Duplicar" por pregunta dentro del Repeater de "Preguntas" para clonar la pregunta completa (dimensión/item, enunciado, tipo, requerida, opciones y orden), tanto en creación como edición de encuestas.

## Enfoque técnico
- Usar el Repeater existente `Forms\Components\Repeater::make('questions')` en [SurveyResource.php](file:///c:/laragon/www/finanzasPersonales/app/Filament/Resources/Surveys/SurveyResource.php).
- Añadir acciones dentro del Repeater con `Filament\Schemas\Components\Actions` (alias `SchemaActions`) y `Filament\Actions\Action`.
- En la acción `duplicate_question`:
  - Leer el estado del ítem actual con `Get $get`.
  - Construir un nuevo array con los campos: `item`, `content`, `type`, `required`, `options` (clon profundo) y `order` (incrementado: último + 1).
  - Insertar en el estado del Repeater con `Set $set` usando ruta relativa (p.ej. `$get('../../questions')`) para obtener el array completo y hacer `array_merge` + `Set('../../questions', $nuevo)`.
  - Mostrar notificación de éxito.

## Cambios propuestos (ubicación y detalles)
- En el schema del Repeater `questions`, al final del `schema([ ... ])` del ítem:
  - Agregar `SchemaActions::make([\Filament\Actions\Action::make('duplicate_question') ->label('Duplicar') ->icon(Heroicon::OutlinedSquare2Stack) ->color('gray') ->action(fn (Get $get, Set $set) => {/* clonado */})])`.
  - Lógica de clonado:
    - `$current = [ 'item' => $get('item'), 'content' => $get('content'), 'type' => $get('type'), 'required' => (bool)$get('required'), 'options' => $get('options') ? json_decode(json_encode($get('options')), true) : [], 'order' => (int)($get('order') ?? 0) ];`
    - `$questions = $get('../../questions') ?? []; $maxOrder = collect($questions)->pluck('order')->filter()->max() ?? 0; $current['order'] = $maxOrder + 1; $set('../../questions', array_merge($questions, [$current]));`

## UX
- Botón visible dentro de cada bloque de pregunta, junto al resto de campos.
- Ícono de duplicado y color neutro.
- Notificación breve "Pregunta duplicada".

## Validaciones y consistencia
- Para tipos `likert` y `multi`, clonar `options` completo.
- No se requiere cambio de BD; el duplicado se persiste al guardar como preguntas nuevas.
- Mantener el filtrado de dimensiones por encuesta seleccionada (ya implementado).

## Pruebas
- Crear encuesta: duplicar una pregunta con opciones (Likert) y verificar que las opciones y tipo se mantienen.
- Editar encuesta: duplicar preguntas existentes y confirmar orden incrementado.
- Guardar y abrir nuevamente: los clones aparecen correctamente.

## Entregables
- Edición de [SurveyResource.php](file:///c:/laragon/www/finanzasPersonales/app/Filament/Resources/Surveys/SurveyResource.php) para añadir la acción de duplicado en el Repeater.
- Notificación de éxito al duplicar.

¿Confirmo y procedo a implementar estos cambios?