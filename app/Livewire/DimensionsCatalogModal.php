<?php

namespace App\Livewire;

use App\Models\Dimension;
use Livewire\Component;
use Filament\Notifications\Notification;
use Illuminate\Validation\Rule;

class DimensionsCatalogModal extends Component
{
    public string $survey_name = '';
    public string $item = '';
    public ?string $kpi_target = '10';
    public ?string $weight = null;
    public ?int $editId = null;
    public int $page = 1;
    public int $perPage = 5;

    public function create(): void
    {
        if (trim($this->survey_name) === '' || trim($this->item) === '' || $this->kpi_target === null || $this->kpi_target === '') {
            Notification::make()->title('El nombre de la encuesta, la dimensión y la meta (KPI) son obligatorios')->danger()->send();
            return;
        }
        $this->validate([
            'survey_name' => 'required|string',
            'item' => ['required','string', Rule::unique('dimensions')->where(fn($q) => $q->where('survey_name', $this->survey_name))],
            'kpi_target' => 'required|numeric',
            'weight' => 'nullable|numeric',
        ]);
        Dimension::create([
            'survey_name' => $this->survey_name,
            'item' => $this->item,
            'kpi_target' => (float) ($this->kpi_target ?? 10),
            'weight' => $this->weight !== null ? (float) $this->weight : null,
        ]);
        $this->reset(['survey_name','item', 'kpi_target', 'weight']);
    }

    public function startEdit(int $id): void
    {
        $dim = Dimension::find($id);
        if ($dim) {
            $this->editId = $id;
            $this->survey_name = $dim->survey_name ?? '';
            $this->item = $dim->item;
            $this->kpi_target = (string) $dim->kpi_target;
            $this->weight = $dim->weight !== null ? (string) $dim->weight : null;
        }
    }

    public function saveEdit(): void
    {
        if (!$this->editId) return;
        if (trim($this->survey_name) === '' || trim($this->item) === '' || $this->kpi_target === null || $this->kpi_target === '') {
            Notification::make()->title(__('surveys.catalog.validation.error_title'))->danger()->send();
            return;
        }
        $this->validate([
            'survey_name' => 'required|string',
            'item' => ['required','string', Rule::unique('dimensions')->ignore($this->editId)->where(fn($q) => $q->where('survey_name', $this->survey_name))],
            'kpi_target' => 'required|numeric',
            'weight' => 'nullable|numeric',
        ]);
        $dim = Dimension::find($this->editId);
        if ($dim) {
            $dim->update([
                'survey_name' => $this->survey_name,
                'item' => $this->item,
                'kpi_target' => (float) ($this->kpi_target ?? 10),
                'weight' => $this->weight !== null ? (float) $this->weight : null,
            ]);
        }
        $this->reset(['editId', 'survey_name', 'item', 'kpi_target', 'weight']);
    }

    public function delete(int $id): void
    {
        Dimension::where('id', $id)->delete();
    }

    public function cancelEdit(): void
    {
        $this->reset(['editId', 'item', 'kpi_target', 'weight']);
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
        return view('livewire.dimensions-catalog-modal', [
            'dimensions' => $dimensions,
            'total' => $total,
            'maxPage' => $maxPage,
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
