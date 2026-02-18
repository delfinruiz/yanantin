<?php

namespace App\Filament\Widgets;

use App\Models\Mood;
use Filament\Widgets\ChartWidget;

class MoodFilteredPieChart extends ChartWidget
{
    protected ?string $heading = 'Estado de Ánimo Según Filtro';
    protected ?string $maxHeight = '220px';

    public array $pageFilters = [];
    public int $tick = 0;

    #[\Livewire\Attributes\On('daily-mood-updated')]
    public function bump(): void
    {
        $this->tick++;
    }

    #[\Livewire\Attributes\On('filters-changed')]
    public function onFiltersChanged(array $filters): void
    {
        $this->pageFilters = [
            'year' => isset($filters['year']) ? (int) $filters['year'] : now()->year,
            'month' => $filters['month'] ?? null,
        ];
        $this->tick++;
        $this->dispatch('$refresh');
    }

    protected function getData(): array
    {
        $year = (int) ($this->pageFilters['year'] ?? now()->year);
        $month = $this->pageFilters['month'] ?? null;

        $query = Mood::query()->whereYear('date', $year);
        if (!empty($month)) {
            $query->whereMonth('date', (int) $month);
        }

        $counts = $query
            ->selectRaw('mood, COUNT(*) as c')
            ->groupBy('mood')
            ->pluck('c', 'mood')
            ->toArray();

        if (array_sum($counts) === 0) {
            return [
                'datasets' => [
                    [
                        'data' => [0],
                        'backgroundColor' => ['#334155'],
                    ],
                ],
                'labels' => ['Sin registros'],
            ];
        }

        $labels = ['Triste', 'Med Triste', 'Neutral', 'Med Feliz', 'Feliz'];
        $order = ['sad', 'med_sad', 'neutral', 'med_happy', 'happy'];
        $colors = ['#ef4444', '#f59e0b', '#facc15', '#84cc16', '#22c55e'];

        $data = [];
        foreach ($order as $key) {
            $data[] = (int) ($counts[$key] ?? 0);
        }

        return [
            'datasets' => [
                [
                    'data' => $data,
                    'backgroundColor' => $colors,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'pie';
    }

    protected function getOptions(): array
    {
        $year = (int) ($this->pageFilters['year'] ?? now()->year);
        $month = $this->pageFilters['month'] ?? null;
        $q = Mood::query()->whereYear('date', $year);
        if (! empty($month)) {
            $q->whereMonth('date', (int) $month);
        }
        $hasData = $q->exists();

        if (! $hasData) {
            return [
                'maintainAspectRatio' => false,
                'plugins' => [
                    'legend' => [
                        'display' => false,
                    ],
                    'tooltip' => [
                        'enabled' => false,
                    ],
                    'title' => [
                        'display' => true,
                        'text' => 'Sin registros',
                        'font' => ['weight' => 'normal'],
                    ],
                ],
                'layout' => [
                    'padding' => ['top' => 8, 'right' => 8, 'bottom' => 8, 'left' => 8],
                ],
            ];
        }

        return [
            'maintainAspectRatio' => false,
            'plugins' => [
                'legend' => [
                    'position' => 'bottom',
                    'labels' => ['boxWidth' => 12],
                ],
            ],
            'layout' => [
                'padding' => ['top' => 8, 'right' => 8, 'bottom' => 8, 'left' => 8],
            ],
        ];
    }
}
