<?php

namespace App\Jobs;

use App\Models\Survey;
use App\Services\AiProviderService;
use App\Services\SurveyAiAppreciationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GenerateSurveyAiAppreciationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [10, 30, 60];

    public function __construct(public int $surveyId)
    {
    }

    public function handle(): void
    {
        $survey = Survey::findOrFail($this->surveyId);
        $provider = app(AiProviderService::class);
        if (! $provider->hasToken()) {
            return;
        }

        try {
            app(SurveyAiAppreciationService::class)->generate($survey);
        } catch (\Throwable $e) {
            $appreciation = $survey->aiAppreciation ?: $survey->aiAppreciation()->make();
            $appreciation->survey_id = $survey->id;
            $appreciation->content = '';
            $appreciation->provider = 'openai';
            $appreciation->error_message = $e->getMessage();
            $appreciation->save();
            throw $e;
        }
    }
}
