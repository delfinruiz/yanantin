<?php

namespace App\Livewire;

use App\Models\Dimension;
use Livewire\Component;
use Filament\Notifications\Notification;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Schemas\Schema;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Actions\Contracts\HasActions;
use Filament\Actions\Concerns\InteractsWithActions;

class DimensionsCatalogModal extends Component implements HasForms, HasActions
{
    use InteractsWithForms;
    use InteractsWithActions;

    public ?array $data = [];

    public string $survey_name = ''; // Keeping for compatibility or migrate to $data
    public string $item = '';
    public ?string $kpi_target = '10';
    public ?string $weight = null;
    
    public ?int $editId = null;
    public int $page = 1;
    public int $perPage = 5;

    public function mount(): void
    {
        $this->form->fill([
            'survey_name' => '',
            'item' => '',
            'kpi_target' => '10',
            'weight' => null,
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(2)
                    ->schema([
                        Select::make('existing_survey')
                            ->label('Encuestas Existentes')
                            ->placeholder(__('surveys.catalog.fields.select_survey'))
                            ->options(fn() => Dimension::distinct()->pluck('survey_name', 'survey_name')->toArray())
                            ->searchable()
                            ->searchDebounce(500)
                            ->live()
                            ->afterStateUpdated(function ($state, $set) {
                                if ($state) {
                                    $set('survey_name', $state);
                                    $this->survey_name = (string) $state;
                                }
                            })
                            ->columnSpan(2),

                        TextInput::make('survey_name')
                            ->label('Nombre Encuesta')
                            ->placeholder(__('surveys.catalog.fields.new_survey'))
                            ->required()
                            ->columnSpan(2)
                            ->live(debounce: 500)
                            ->afterStateUpdated(function ($state) {
                                $this->survey_name = (string) $state; // Sync for weight calculation
                            }),

                        TextInput::make('item')
                            ->label(__('surveys.catalog.fields.dimension'))
                            ->placeholder(__('surveys.catalog.fields.dimension'))
                            ->required()
                            ->columnSpan(2),

                        TextInput::make('kpi_target')
                            ->label(__('surveys.catalog.fields.target'))
                            ->placeholder(__('surveys.catalog.fields.target'))
                            ->numeric()
                            ->default(10)
                            ->helperText(__('Valor objetivo (KPI) esperado para esta dimensión.'))
                            ->required(),

                        TextInput::make('weight')
                            ->label(__('surveys.catalog.fields.weight'))
                            ->placeholder(__('surveys.catalog.fields.weight'))
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(100)
                            ->live()
                            ->hint(function ($state) {
                                $currentTotal = $this->getCurrentTotalWeight();
                                $newWeight = (float) ($state ?? 0);
                                $projectedTotal = $currentTotal + $newWeight;
                                $isOver = $projectedTotal > 100;
                                $color = $isOver ? 'text-danger-600 font-bold' : 'text-gray-500';
                                return new \Illuminate\Support\HtmlString(
                                    "<span class='{$color}'>Total: " . number_format($projectedTotal, 2) . "% / 100%</span>"
                                );
                            }),
                    ]),
            ])
            ->statePath('data');
    }

    public function getCurrentTotalWeight(): float
    {
        // Get survey_name from form state if available, fallback to property
        $name = $this->data['survey_name'] ?? $this->survey_name;
        
        if (trim($name) === '') {
            return 0.0;
        }

        $query = Dimension::where('survey_name', $name);
        
        if ($this->editId) {
            $query->where('id', '!=', $this->editId);
        }

        return (float) $query->sum('weight');
    }

    public function create(): void
    {
        $data = $this->form->getState();
        
        $this->survey_name = trim($data['survey_name']);
        $this->item = trim($data['item']);
        $this->kpi_target = $data['kpi_target'];
        $this->weight = $data['weight'];

        // Check for duplicates manually to send notification
        $exists = Dimension::where('survey_name', $this->survey_name)
            ->where('item', $this->item)
            ->exists();

        if ($exists) {
            Notification::make()
                ->title("La dimensión '{$this->item}' ya existe en la encuesta '{$this->survey_name}'")
                ->danger()
                ->send();
            return;
        }

        $currentTotal = $this->getCurrentTotalWeight();
        $newWeight = (float) ($this->weight ?? 0);

        if ($newWeight <= 0) {
            Notification::make()
                ->title('El peso debe ser un número entre 1 y 100.')
                ->danger()
                ->send();
            return;
        }
        
        if ($newWeight > 100) {
            Notification::make()
                ->title('El peso debe ser un número entre 1 y 100.')
                ->danger()
                ->send();
            return;
        }
        
        if ($currentTotal + $newWeight > 100) {
            Notification::make()
                ->title('El peso total no puede superar el 100%. Actual acumulado: ' . $currentTotal . '%')
                ->danger()
                ->send();
            return;
        }

        Dimension::create([
            'survey_name' => $this->survey_name,
            'item' => $this->item,
            'kpi_target' => (float) ($this->kpi_target ?? 10),
            'weight' => $this->weight !== null ? (float) $this->weight : null,
        ]);
        
        // Reset form but keep survey_name
        $this->form->fill([
            'survey_name' => $this->survey_name,
            'item' => '',
            'kpi_target' => '10',
            'weight' => null,
        ]);
        
        Notification::make()->title('Dimensión creada correctamente')->success()->send();
    }

    public function startEdit(int $id): void
    {
        $dim = Dimension::find($id);
        if ($dim) {
            // Check if survey has responses
            if ($this->hasResponses($dim->survey_name)) {
                Notification::make()
                    ->title("No se puede editar esta dimensión porque la encuesta '{$dim->survey_name}' tiene respuestas asociadas. Debe limpiar las respuestas primero.")
                    ->danger()
                    ->send();
                return;
            }

            $this->editId = $id;
            $this->survey_name = $dim->survey_name ?? '';
            
            $this->form->fill([
                'survey_name' => $dim->survey_name,
                'item' => $dim->item,
                'kpi_target' => (string) $dim->kpi_target,
                'weight' => $dim->weight !== null ? (string) $dim->weight : null,
            ]);
        }
    }

    public function saveEdit(): void
    {
        if (!$this->editId) return;

        $data = $this->form->getState();
        $newSurveyName = trim($data['survey_name']);
        $newItem = trim($data['item']);
        $newTarget = $data['kpi_target'];
        $newWeight = $data['weight'];

        $dim = Dimension::find($this->editId);
        if (! $dim) return;

        $oldSurveyName = $dim->survey_name;

        // Check for duplicates manually
        $exists = Dimension::where('survey_name', $newSurveyName)
            ->where('item', $newItem)
            ->where('id', '!=', $this->editId)
            ->exists();

        if ($exists) {
            Notification::make()
                ->title("La dimensión '{$newItem}' ya existe en la encuesta '{$newSurveyName}'")
                ->danger()
                ->send();
            return;
        }

        // Validate weight limit (simplified for this context)
        // Note: Logic should ideally sum all dimensions of the *new* survey name
        $currentTotal = Dimension::where('survey_name', $newSurveyName)->where('id', '!=', $this->editId)->sum('weight');
        $valWeight = (float) ($newWeight ?? 0);

        if ($valWeight <= 0) {
            Notification::make()
                ->title('El peso debe ser un número entre 1 y 100.')
                ->danger()
                ->send();
            return;
        }
        
        if ($valWeight > 100) {
            Notification::make()
                ->title('El peso debe ser un número entre 1 y 100.')
                ->danger()
                ->send();
            return;
        }

        if ($currentTotal + $valWeight > 100) {
             Notification::make()
                ->title('El peso total no puede superar el 100%. Actual acumulado: ' . $currentTotal . '%')
                ->danger()
                ->send();
            return;
        }

        // If survey name changed, update ALL dimensions of the old survey name to the new name
        // AND update the actual Survey record if it exists
        if ($oldSurveyName !== $newSurveyName) {
            // 1. Update all dimensions belonging to the old survey name
            Dimension::where('survey_name', $oldSurveyName)->update(['survey_name' => $newSurveyName]);
            
            // 2. Update the Survey record title if it exists
            \App\Models\Survey::where('title', $oldSurveyName)->update(['title' => $newSurveyName]);

            Notification::make()
                ->title("Nombre de encuesta actualizado en todas las dimensiones y registros asociados.")
                ->success()
                ->send();
        }

        // Update the specific dimension item being edited
        $dim->refresh(); // Refresh to get updated survey_name if it was mass-updated above
        $dim->update([
            'survey_name' => $newSurveyName, // Redundant but safe
            'item' => $newItem,
            'kpi_target' => (float) ($newTarget ?? 10),
            'weight' => $newWeight !== null ? (float) $newWeight : null,
        ]);
        
        $this->reset(['editId']);
        $this->form->fill([
            'survey_name' => $newSurveyName,
            'item' => '',
            'kpi_target' => '10',
            'weight' => null,
        ]);
        
        Notification::make()->title('Dimensión actualizada correctamente')->success()->send();
    }

    public function delete(int $id): void
    {
        $dim = Dimension::find($id);
        if ($dim) {
            if ($this->hasResponses($dim->survey_name)) {
                Notification::make()
                    ->title("No se puede eliminar esta dimensión porque la encuesta '{$dim->survey_name}' tiene respuestas asociadas. Debe limpiar las respuestas primero.")
                    ->danger()
                    ->send();
                return;
            }
            $dim->delete();
            Notification::make()->title('Dimensión eliminada correctamente')->success()->send();
        }
    }

    protected function hasResponses(string $surveyName): bool
    {
        $survey = \App\Models\Survey::where('title', $surveyName)->first();
        if (!$survey) return false;
        
        return $survey->questions()->whereHas('responses')->exists();
    }

    public function cancelEdit(): void
    {
        $this->reset(['editId']);
        $this->form->fill([
            'survey_name' => '',
            'item' => '',
            'kpi_target' => '10',
            'weight' => null,
        ]);
    }

    public function render()
    {
        $total = Dimension::count();
        $maxPage = max(1, (int) ceil($total / $this->perPage));
        if ($this->page > $maxPage) {
            $this->page = $maxPage;
        }
        $dimensions = Dimension::query()
            ->orderByDesc('created_at')
            ->orderBy('survey_name')
            ->skip(($this->page - 1) * $this->perPage)
            ->take($this->perPage)
            ->get();
        
        $existingSurveys = Dimension::distinct('survey_name')->pluck('survey_name');

        return view('livewire.dimensions-catalog-modal', [
            'dimensions' => $dimensions,
            'total' => $total,
            'maxPage' => $maxPage,
            'currentTotalWeight' => $this->getCurrentTotalWeight(),
            'existingSurveys' => $existingSurveys,
        ]);
    }

    public function nextPage(): void
    {
        $maxPage = (int) ceil(Dimension::count() / $this->perPage);
        if ($this->page < $maxPage) {
            $this->page++;
        }
    }

    public function prevPage(): void
    {
        if ($this->page > 1) {
            $this->page--;
        }
    }
}
