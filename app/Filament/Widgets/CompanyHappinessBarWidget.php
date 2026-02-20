<?php

namespace App\Filament\Widgets;

use App\Models\Mood;
use Filament\Widgets\Widget;
use Illuminate\Support\Carbon;
use Livewire\Attributes\On;

class CompanyHappinessBarWidget extends Widget
{
    protected string $view = 'filament.widgets.company-happiness-bar-widget';
    protected static ?int $sort = 1;

    public int $level = 3;
    public array $distribution = [];
    public int $responses = 0;
    public int $triangleLeft = 50;

    protected static bool $isLazy = false;
    protected int|string|array $columnSpan = 6;

    public function mount(): void
    {
        $this->refreshData();
    }

    #[On('daily-mood-updated')]
    public function refreshData(): void
    {
        $today = Carbon::today();
        $counts = Mood::query()
            ->whereDate('date', $today)
            ->selectRaw('mood, COUNT(*) as c')
            ->groupBy('mood')
            ->pluck('c', 'mood')
            ->toArray();

        $total = array_sum($counts);
        $this->responses = (int) $total;
        $weights = [
            'happy' => 1.0,
            'med_happy' => 0.75,
            'neutral' => 0.5,
            'med_sad' => 0.25,
            'sad' => 0.0,
        ];

        $score = 0;
        foreach ($weights as $mood => $w) {
            $pct = $total > 0 ? ($counts[$mood] ?? 0) * 100 / $total : 0;
            $score += $pct * $w;
            $this->distribution[$mood] = round($pct, 2);
        }

        $this->level = $total > 0 ? max(1, min(5, (int) ceil(($score / 100) * 5))) : 3;
        $this->triangleLeft = max(0, min(100, (int) round(($this->level - 0.5) * 20)));
    }
}

