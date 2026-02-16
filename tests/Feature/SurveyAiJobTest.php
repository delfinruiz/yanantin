<?php

namespace Tests\Feature;

use App\Jobs\GenerateSurveyAiAppreciationJob;
use App\Models\Survey;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class SurveyAiJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_job_does_nothing_without_token()
    {
        Cache::forget('app_settings');
        Setting::firstOrCreate(['company_name' => 'Test']);

        $survey = Survey::create([
            'title' => 'Satisfacción',
            'description' => 'Evaluación de satisfacción',
            'active' => true,
        ]);

        dispatch_sync(new GenerateSurveyAiAppreciationJob($survey->id));

        $this->assertNull($survey->fresh()->aiAppreciation);
    }
}
