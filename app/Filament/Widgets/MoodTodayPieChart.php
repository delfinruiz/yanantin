<?php

namespace App\Filament\Widgets;

use App\Models\Mood;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class MoodTodayPieChart extends ChartWidget
{
    protected ?string $heading = 'Estado de Ánimo Hoy';
    protected ?string $maxHeight = '220px';

    public int $tick = 0;

    #[\Livewire\Attributes\On('daily-mood-updated')]
    public function bump(): void
    {
        $this->tick++;
    }

    protected function getData(): array
    {
        $date = Carbon::parse(request()->query('date') ?: now()->toDateString())->startOfDay();

        $counts = Mood::query()
            ->whereDate('date', $date)
            ->selectRaw('mood, COUNT(*) as c')
            ->groupBy('mood')
            ->pluck('c', 'mood')
            ->toArray();

        $labels = ['Triste','Med Triste','Neutral','Med Feliz','Feliz'];
        $order = ['sad','med_sad','neutral','med_happy','happy'];
        $colors = ['#ef4444','#f59e0b','#facc15','#84cc16','#22c55e'];

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

    protected function getOptions(): array
    {
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

    protected function getType(): string
    {
        return 'pie';
    }
}

