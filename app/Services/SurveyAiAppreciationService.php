<?php

namespace App\Services;

use App\Models\AiAppreciation;
use App\Models\Survey;
use App\Models\Response;
use App\Services\SurveyStatsService;

class SurveyAiAppreciationService
{
    public function buildReport(Survey $survey): string
    {
        $statsService = app(SurveyStatsService::class);
        $dimensions = $statsService->dimensionStats($survey);
        $globalAvg = $statsService->globalAvg($dimensions);
        $weighted = $statsService->weightedAvg($dimensions);
        $typeSummary = $statsService->typeSummary($survey);

        $participantsUsers = Response::whereHas('question', fn ($q) => $q->where('survey_id', $survey->id))
            ->whereNotNull('user_id')
            ->distinct('user_id')
            ->count('user_id');

        $participantsGuests = Response::whereHas('question', fn ($q) => $q->where('survey_id', $survey->id))
            ->whereNull('user_id')
            ->whereNotNull('guest_email')
            ->distinct('guest_email')
            ->count('guest_email');

        $report = "Título: {$survey->title}\n";
        $report .= "Descripción: " . ($survey->description ?? '') . "\n";
        $report .= "Participantes: " . ($participantsUsers + $participantsGuests) . " (Usuarios: {$participantsUsers}, Invitados: {$participantsGuests})\n";
        $report .= "Promedio global: {$globalAvg}\n";
        $report .= "Promedio ponderado: {$weighted}\n";
        $report .= "Dimensiones:\n";
        foreach ($dimensions as $name => $d) {
            $avg = $d['avg'] ?? null;
            $compliance = $d['compliance_pct'] ?? null;
            $report .= "- {$name}: promedio=" . ($avg ?? 'N/A') . " cumplimiento_kpi=" . ($compliance !== null ? "{$compliance}%" : 'N/A') . "\n";
        }
        $report .= "Resumen por tipo de pregunta:\n";
        foreach ($typeSummary as $k => $v) {
            $report .= "- {$k}: " . json_encode($v) . "\n";
        }

        return $report;
    }

    public function generate(Survey $survey): AiAppreciation
    {
        $report = $this->buildReport($survey);

        $ai = app(AiProviderService::class)->text()
            ->using('openai', 'gpt-5-nano')
            ->withPrompt(
                "Analiza el siguiente reporte de encuesta y genera ÚNICAMENTE una conclusión final y sugerencias breves. No incluyas títulos, ni secciones de mejoras, ni introducciones. Solo entrega el texto de la conclusión final.\n\n{$report}"
            );

        $response = $ai->asText();

        $content = $response->text;

        $model = 'gpt-5-nano';
        $usageTokens = $response->usage->promptTokens + $response->usage->completionTokens;
        $reasoningTokens = $response->usage->thoughtTokens ?? null;

        $appreciation = $survey->aiAppreciation;
        if (! $appreciation) {
            $appreciation = new AiAppreciation();
            $appreciation->survey_id = $survey->id;
        }

        $appreciation->content = $content;
        $appreciation->provider = 'openai';
        $appreciation->model = $model;
        $appreciation->usage_tokens = $usageTokens;
        $appreciation->reasoning_tokens = $reasoningTokens;
        $appreciation->error_message = null;
        $appreciation->save();

        return $appreciation;
    }
}
