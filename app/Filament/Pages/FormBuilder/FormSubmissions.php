<?php

namespace App\Filament\Pages\FormBuilder;

use App\Services\FormBuilder\FormStorage;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Pages\Page;
use Filament\Actions\DeleteAction;
use Filament\Actions\Action;
use App\Exports\FormSubmissionsExport;
use Maatwebsite\Excel\Facades\Excel;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use App\Models\FormSubmission;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Support\Enums\Width;
use Illuminate\Support\HtmlString;
use Illuminate\Database\Eloquent\Collection;
use Filament\Actions\BulkAction;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;


class FormSubmissions extends Page implements HasActions, HasTable
{
    use InteractsWithActions;
    use InteractsWithTable;
    use HasPageShield;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-document-text';
    protected static string | \UnitEnum | null $navigationGroup = 'Form Builder';
    protected static ?string $slug = 'form-builder/submissions/{formId}';
    protected static bool $shouldRegisterNavigation = false;

    protected string $view = 'filament.pages.formbuilder.form-submissions';

    public string $formId;
    public array $def;

    public function getTitle(): string | \Illuminate\Contracts\Support\Htmlable
    {
        $id = $this->formId ?? request()->route('formId');
        return __('formbuilder.submissions_for_form') . " {$id}";
    }

    public function getMaxContentWidth(): Width
    {
        return Width::Full;
    }

    public function getBreadcrumbs(): array
    {
        return [
            ManageForms::getUrl() => __('formbuilder.manage_forms'),
            '#' => $this->getTitle(),
        ];
    }

    public function mount(string $formId): void
    {
        $storage = app(FormStorage::class);
        $this->formId = $formId;
        $def = $storage->getForm($formId);
        $this->def = $def ? $def->toArray() : ['elements' => []];
        
        // Set the current form ID for the Sushi model
        FormSubmission::$currentFormId = $formId;
    }

    public function booted(): void
    {
        if (isset($this->formId)) {
            FormSubmission::$currentFormId = $this->formId;
        }
    }

    public function table(Table $table): Table
    {
        // Define columns for the table (limited to date + first 3 custom fields)
        $columns = [
            TextColumn::make('submitted_at')->label(__('formbuilder.date'))->dateTime()->sortable(),
        ];

        if (isset($this->def['elements'])) {
            $count = 0;
            foreach ($this->def['elements'] as $el) {
                if ($count >= 3) break;
                if (!empty($el['name'])) {
                    // Use a safe column name (hash of field name) to avoid dot notation issues
                    $safeColName = 'col_' . md5($el['name']);
                    
                    $col = TextColumn::make($safeColName)
                        ->label($el['label'] ?? $el['name'])
                        ->state(fn (FormSubmission $record) => $record->data[$el['name']] ?? null)
                        ->default('-')
                        ->searchable(false); // Cannot search easily on JSON columns without dot notation in DB query

                    if (($el['type'] ?? '') === 'file') {
                        $col->formatStateUsing(function ($state, $record) use ($el) {
                            // Extract data from record if state is just a placeholder or filename string
                            $fileData = $record->data[$el['name']] ?? null;

                            // Intentar decodificar JSON si es string
                            if (is_string($fileData) && str_starts_with(trim($fileData), '{')) {
                                $decoded = json_decode($fileData, true);
                                if (json_last_error() === JSON_ERROR_NONE) {
                                    $fileData = $decoded;
                                }
                            }

                            // Determinar nombre del archivo y validez
                            $fileName = '-';
                            $isValid = false;

                            if (is_array($fileData) && isset($fileData['original'])) {
                                $fileName = $fileData['original'];
                                $isValid = true;
                            } elseif (is_string($fileData) && !empty($fileData) && !str_starts_with($fileData, '{')) {
                                // Caso fallback: si es solo string (path), mostrar basename
                                $fileName = basename($fileData);
                                $isValid = true; 
                            }

                            if (!$isValid) {
                                return '';
                            }

                            $url = route('formbuilder.download', [
                                'formId' => $this->formId,
                                'submissionId' => $record->submission_id,
                                'field' => $el['name']
                            ]);
                            return '<a href="'.$url.'" target="_blank" style="color: #288cfa; display: flex; align-items: center; gap: 0.5rem; text-decoration: none;" onmouseover="this.style.textDecoration=\'underline\'" onmouseout="this.style.textDecoration=\'none\'">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width: 1rem; height: 1rem;">
                                  <path stroke-linecap="round" stroke-linejoin="round" d="m18.375 12.739-7.693 7.693a4.5 4.5 0 0 1-6.364-6.364l10.94-10.94A3 3 0 1 1 19.5 7.372L8.552 18.32m.009-.01-.01.01m5.699-9.941-7.81 7.81a1.5 1.5 0 0 0 2.112 2.13" />
                                </svg>
                                ' . e($fileName) . '
                            </a>';
                        })
                        ->html()
                        ->limit(null);
                    }

                    $columns[] = $col;
                    $count++;
                }
            }
        }

        return $table
            ->query(FormSubmission::query())
            ->columns($columns)
            ->selectable()
            ->recordActions([
                ViewAction::make()
                    ->modalHeading(__('formbuilder.submission_details'))
                    ->schema([
                        Section::make(__('formbuilder.submission_info'))
                            ->schema([
                                TextEntry::make('submission_id')->label(__('formbuilder.submission_id')),
                                TextEntry::make('submitted_at')->label(__('formbuilder.date'))->dateTime(),
                            ])->columns(2),
                        Section::make(__('formbuilder.responses'))
                            ->schema(function () {
                                $entries = [];
                                if (isset($this->def['elements'])) {
                                    foreach ($this->def['elements'] as $el) {
                                        if (empty($el['name'])) continue;
                                        
                                        if (($el['type'] ?? '') === 'file') {
                                            $entries[] = TextEntry::make('file_' . md5($el['name']))
                                                ->label($el['label'] ?? $el['name'])
                                                ->state(function (FormSubmission $record) use ($el) {
                                                    $data = $record->data[$el['name']] ?? null;
                                                    
                                                    // Handle JSON string
                                                    if (is_string($data) && str_starts_with(trim($data), '{')) {
                                                        $decoded = json_decode($data, true);
                                                        if (json_last_error() === JSON_ERROR_NONE) {
                                                            $data = $decoded;
                                                        }
                                                    }

                                                    // Return just the filename string to prevent array listing
                                                    if (is_array($data) && isset($data['original'])) {
                                                        return $data['original'];
                                                    }
                                                    if (is_string($data) && !empty($data)) {
                                                        return basename($data);
                                                    }
                                                    return '-';
                                                })
                                                ->formatStateUsing(function ($state, $record) use ($el) {
                                                    // State is now guaranteed to be the filename string (or '-')
                                                    $fileName = $state;
                                                    
                                                    if ($fileName === '-' || empty($fileName)) {
                                                        return '-';
                                                    }

                                                    $url = route('formbuilder.download', [
                                                        'formId' => $this->formId,
                                                        'submissionId' => $record->submission_id,
                                                        'field' => $el['name']
                                                    ]);
                                                    return '<a href="'.$url.'" target="_blank" style="color: #288cfa; display: flex; align-items: center; gap: 0.5rem; text-decoration: none;" onmouseover="this.style.textDecoration=\'underline\'" onmouseout="this.style.textDecoration=\'none\'">
                                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width: 1rem; height: 1rem;">
                                                          <path stroke-linecap="round" stroke-linejoin="round" d="m18.375 12.739-7.693 7.693a4.5 4.5 0 0 1-6.364-6.364l10.94-10.94A3 3 0 1 1 19.5 7.372L8.552 18.32m.009-.01-.01.01m5.699-9.941-7.81 7.81a1.5 1.5 0 0 0 2.112 2.13" />
                                                        </svg>
                                                        ' . e($fileName) . '
                                                    </a>';
                                                })
                                                ->html();
                                        } else {
                                            $entries[] = TextEntry::make('field_' . md5($el['name']))
                                                ->label($el['label'] ?? $el['name'])
                                                ->state(fn (FormSubmission $record) => $record->data[$el['name']] ?? null)
                                                ->default('-');
                                        }
                                    }
                                }
                                return $entries;
                            })->columns(1)
                    ]),
                DeleteAction::make()
                    ->requiresConfirmation()
                    ->action(function (FormSubmission $record) {
                        app(FormStorage::class)->deleteSubmission($this->formId, $record->submission_id);
                        $record->delete();
                    }),
            ])
            ->headerActions([
                Action::make('view_form')
                    ->label(__('formbuilder.view_form'))
                    ->icon('heroicon-o-eye')
                    ->url(route('forms.show', $this->formId))
                    ->openUrlInNewTab(),
                Action::make('export_table')
                    ->label(__('formbuilder.export_excel'))
                    ->icon('heroicon-o-arrow-down-tray')
                    ->action(function () {
                        $headers = [__('formbuilder.submission_id'), __('formbuilder.date')];
                        foreach ($this->def['elements'] as $el) {
                            if (!empty($el['name'])) {
                                $headers[] = $el['label'] ?? $el['name'];
                            }
                        }

                        $records = FormSubmission::all();
                        $exportRows = [];
                        
                        foreach ($records as $record) {
                            $row = [
                                $record->submission_id,
                                $record->submitted_at,
                            ];
                            $data = $record->data ?? [];
                            
                            foreach ($this->def['elements'] as $el) {
                                if (!empty($el['name'])) {
                                    $val = $data[$el['name']] ?? '';
                                    
                                    // Handle file array or JSON string
                                    if (is_string($val) && str_starts_with(trim($val), '{')) {
                                         $decoded = json_decode($val, true);
                                         if (is_array($decoded) && isset($decoded['original'])) {
                                             $val = $decoded['original'];
                                         }
                                    } elseif (is_array($val) && isset($val['original'])) {
                                        $val = $val['original'];
                                    }
                                    
                                    $row[] = is_array($val) ? json_encode($val) : $val;
                                }
                            }
                            $exportRows[] = $row;
                        }

                        return Excel::download(
                            new FormSubmissionsExport($exportRows, $headers),
                            'form-' . $this->formId . '-' . date('Y-m-d') . '.xlsx'
                        );
                    }),
            ])
            ->toolbarActions([
                BulkAction::make('bulk_delete')
                    ->label('Borrar seleccionados')
                    ->icon('heroicon-o-trash')
                    ->requiresConfirmation()
                    ->action(function (Collection $selectedRecords) {
                        $storage = app(FormStorage::class);
                        $selectedRecords->each(function (FormSubmission $record) use ($storage) {
                            $storage->deleteSubmission($this->formId, $record->submission_id);
                            $record->delete();
                        });
                    }),
            ]);
    }
}
