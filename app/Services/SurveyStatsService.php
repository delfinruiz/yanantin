<?php

namespace App\Services;

use App\Models\Response;
use App\Models\Survey;
use App\Models\Dimension;

class SurveyStatsService
{
    public function dimensionStats(Survey $survey): array
    {
        $dimensions = $survey->questions()->select('item')->distinct()->pluck('item');
        $result = [];
        foreach ($dimensions as $dim) {
            $questions = $survey->questions()->where('item', $dim)->get(['id','type']);
            $qMap = $questions->keyBy('id');
            $qIds = $questions->pluck('id');
            $responses = Response::whereIn('question_id', $qIds)->get(['question_id','value']);

            $normalized = [];
            $yesCount = 0;
            $boolCount = 0;
            $textCount = 0;
            $multiCount = 0;

            foreach ($responses as $r) {
                $qid = $r->question_id;
                $type = $qMap->get($qid)?->type;
                $val = $r->value;
                if ($type === 'scale_10') {
                    if (is_numeric($val)) $normalized[] = ((float)$val / 10.0) * 100.0;
                } elseif ($type === 'scale_5') {
                    if (is_numeric($val)) $normalized[] = ((float)$val / 5.0) * 100.0;
                } elseif ($type === 'likert') {
                    if (is_numeric($val)) {
                        $v = (float)$val;
                        $normalized[] = (($v - 1.0) / 4.0) * 100.0;
                    }
                } elseif ($type === 'bool') {
                    $boolCount++;
                    if (is_string($val)) {
                        $yesCount += (strtolower($val) === 'si') ? 1 : 0;
                    } elseif (is_numeric($val)) {
                        $yesCount += ((int)$val) === 1 ? 1 : 0;
                    }
                } elseif ($type === 'multi') {
                    $multiCount++;
                } elseif ($type === 'text') {
                    $textCount++;
                } else {
                    if (is_numeric($val)) $normalized[] = ((float)$val / 10.0) * 100.0;
                }
            }

            $avgNorm = !empty($normalized) ? round(collect($normalized)->avg(), 2) : null;
            $dimRow = Dimension::query()
                ->where('survey_name', $survey->title)
                ->where('item', $dim)
                ->first();
            $kpi = $dimRow?->kpi_target;
            if ($kpi === null) {
                // fallback defaults
                $kpi = 100.0;
            }
            $compliance = ($avgNorm !== null && $kpi > 0) ? min(100.0, round(($avgNorm / $kpi) * 100, 2)) : null;
            $rating = null;
            if ($compliance !== null) {
                $rating = match (true) {
                    $compliance < 55 => 'Deficiente',
                    $compliance < 70 => 'Regular',
                    $compliance < 85 => 'Bueno',
                    default => 'Excelente',
                };
            }
            $result[$dim] = [
                'questions_count' => $qIds->count(),
                'responses_count' => $responses->count(),
                'avg' => $avgNorm, // 0-100
                'kpi' => $kpi,
                'compliance_pct' => $compliance,
                'rating' => $rating,
                'weight' => $dimRow?->weight,
                'bool_yes_pct' => $boolCount > 0 ? round(($yesCount / $boolCount) * 100, 2) : null,
                'text_count' => $textCount,
                'multi_count' => $multiCount,
            ];
        }
        return $result;
    }

    public function globalAvg(array $dimensionStats): ?float
    {
        $avg = collect($dimensionStats)->pluck('avg')->filter()->avg();
        return $avg ? round($avg, 2) : null;
    }

    public function weightedAvg(array $dimensionStats): ?float
    {
        $stats = collect($dimensionStats)
            ->filter(function ($info) {
                return isset($info['avg']) && is_numeric($info['avg']) && isset($info['weight']) && is_numeric($info['weight']);
            });
        $totalWeight = $stats->sum(function ($info) { return (float) $info['weight']; });
        if ($totalWeight <= 0) return null;
        $sum = $stats->sum(function ($info) {
            return ((float) $info['avg']) * ((float) $info['weight']);
        });
        return round($sum / $totalWeight, 2);
    }

    public function typeSummary(Survey $survey): array
    {
        $questions = $survey->questions()->get(['id','type','item']);
        $qMap = $questions->keyBy('id');
        $responses = Response::whereIn('question_id', $questions->pluck('id'))->get(['question_id','value']);

        $data = [
            'scale_5' => ['count' => 0, 'responses' => 0, 'avg' => null],
            'scale_10' => ['count' => 0, 'responses' => 0, 'avg' => null],
            'likert' => ['count' => 0, 'responses' => 0, 'avg' => null],
            'bool' => ['count' => 0, 'responses' => 0, 'yes_pct' => null],
            'multi' => ['count' => 0, 'responses' => 0],
            'text' => ['count' => 0, 'responses' => 0],
        ];
        $norm = ['scale_5' => [], 'scale_10' => [], 'likert' => []];
        $yes = 0; $yesDen = 0;

        foreach ($questions as $q) {
            $t = $q->type;
            if (isset($data[$t])) {
                $data[$t]['count']++;
            }
        }
        foreach ($responses as $r) {
            $qid = $r->question_id;
            $t = $qMap->get($qid)?->type;
            $v = $r->value;
            if ($t === 'scale_5') {
                $data['scale_5']['responses']++;
                if (is_numeric($v)) $norm['scale_5'][] = ((float)$v / 5.0) * 100.0;
            } elseif ($t === 'scale_10') {
                $data['scale_10']['responses']++;
                if (is_numeric($v)) $norm['scale_10'][] = ((float)$v / 10.0) * 100.0;
            } elseif ($t === 'likert') {
                $data['likert']['responses']++;
                if (is_numeric($v)) $norm['likert'][] = (((float)$v - 1.0) / 4.0) * 100.0;
            } elseif ($t === 'bool') {
                $data['bool']['responses']++;
                $yesDen++;
                if (is_string($v)) {
                    $yes += (strtolower($v) === 'si') ? 1 : 0;
                } elseif (is_numeric($v)) {
                    $yes += ((int)$v) === 1 ? 1 : 0;
                }
            } elseif ($t === 'multi') {
                $data['multi']['responses']++;
            } elseif ($t === 'text') {
                $data['text']['responses']++;
            }
        }
        foreach (['scale_5','scale_10','likert'] as $k) {
            $data[$k]['avg'] = !empty($norm[$k]) ? round(array_sum($norm[$k]) / count($norm[$k]), 2) : null;
        }
        $data['bool']['yes_pct'] = $yesDen > 0 ? round(($yes / $yesDen) * 100, 2) : null;

        return $data;
    }
}
