<?php

namespace App\Filament\Widgets;

use App\Models\Mood;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class MoodMonthlyDistributionChart extends ChartWidget
{
    protected ?string $heading = 'Distribución Mensual de Estados de Ánimo';
    protected ?string $maxHeight = '260px';

    public int $tick = 0;

    #[\Livewire\Attributes\On('daily-mood-updated')]
    public function bump(): void
    {
        $this->tick++;
    }

    protected function getData(): array
    {
        $year = now()->year;

        $start = Carbon::create($year, 1, 1);
        $end = Carbon::create($year, 12, 31);

        $rows = Mood::query()
            ->whereBetween('date', [$start, $end])
            ->selectRaw('MONTH(date) as m, mood, COUNT(*) as c')
            ->groupBy('m', 'mood')
            ->get();

        $labels = collect(range(1, 12))->map(fn($m) => __(date('M', mktime(0,0,0,$m,1))));
        $moods = ['sad','med_sad','neutral','med_happy','happy'];
        $colors = [
            'sad' => '#ef4444',
            'med_sad' => '#f59e0b',
            'neutral' => '#facc15',
            'med_happy' => '#84cc16',
            'happy' => '#22c55e',
        ];

        $counts = [];
        foreach ($rows as $row) {
            $counts[(int) $row->m][$row->mood] = (int) $row->c;
        }

        $datasets = [];
        foreach ($moods as $mood) {
            $data = [];
            for ($m = 1; $m <= 12; $m++) {
                $count = $counts[$m][$mood] ?? 0;
                $data[] = $count;
            }
            $datasets[] = [
                'label' => $this->labelForMood($mood),
                'data' => $data,
                'borderColor' => $colors[$mood],
                'backgroundColor' => $colors[$mood],
                'tension' => 0.3,
            ];
        }

        return [
            'datasets' => $datasets,
            'labels' => $labels->toArray(),
        ];
    }

    protected function getOptions(): array
    {
        return [
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'min' => 0,
                ],
            ],
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function labelForMood(string $mood): string
    {
        return match ($mood) {
            'happy' => 'Feliz',
            'med_happy' => 'Med Feliz',
            'neutral' => 'Neutral',
            'med_sad' => 'Med Triste',
            default => 'Triste',
        };
    }
}

