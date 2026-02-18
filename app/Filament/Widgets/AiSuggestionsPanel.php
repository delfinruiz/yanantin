<?php

namespace App\Filament\Widgets;

use App\Models\HappinessSuggestion;
use Filament\Widgets\Widget;

class AiSuggestionsPanel extends Widget
{
    protected string $view = 'filament.widgets.ai-suggestions-panel';
    protected int|string|array $columnSpan = 'full';

    protected function getViewData(): array
    {
        $year = now()->year;
        $latest = HappinessSuggestion::whereYear('date', $year)
            ->orderByDesc('date')
            ->first();

        $counts = \App\Models\Mood::query()
            ->whereYear('date', $year)
            ->selectRaw('mood, COUNT(*) as c')
            ->groupBy('mood')
            ->pluck('c', 'mood')
            ->toArray();

        $total = array_sum($counts);
        $weights = [
            'happy' => 1.0, 'med_happy' => 0.75, 'neutral' => 0.5, 'med_sad' => 0.25, 'sad' => 0.0,
        ];
        $score = 0;
        foreach ($weights as $mood => $w) {
            $pct = $total > 0 ? ($counts[$mood] ?? 0) * 100 / $total : 0;
            $score += $pct * $w;
        }
        $level = $total > 0 ? max(1, min(5, (int)ceil(($score / 100) * 5))) : 3;

        $order = ['happy','med_happy','neutral','med_sad','sad'];
        $labels = [
            'happy' => 'Feliz',
            'med_happy' => 'Med Feliz',
            'neutral' => 'Neutral',
            'med_sad' => 'Med Triste',
            'sad' => 'Triste',
        ];
        $dominantKey = null;
        $dominantVal = -1;
        foreach ($order as $k) {
            $v = (int) ($counts[$k] ?? 0);
            if ($v > $dominantVal) {
                $dominantVal = $v;
                $dominantKey = $k;
            }
        }
        $dominantPct = $total > 0 ? round($dominantVal * 100 / $total) : 0;
        $dominantLabel = $labels[$dominantKey ?? 'neutral'] ?? 'Neutral';

        $impactText = match (true) {
            $level >= 4 => 'Se proyecta impacto positivo en productividad por mayor compromiso, colaboración y menor ausentismo.',
            $level === 3 => 'Productividad estable. Vigilar cargas y fatiga para evitar descensos si aumenta la proporción Neutral/Med Triste.',
            default => 'Riesgo de caída de productividad: menor foco, más rotación y ausentismo. Requiere intervención prioritaria.',
        };

        $impact = $impactText;

        return [
            'impact' => $impact,
            'actions' => $latest?->suggestion ?? 'Sin sugerencias disponibles.',
            'date' => $latest?->date?->format('Y-m-d'),
        ];
    }
}
