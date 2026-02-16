<?php

namespace Tests\Unit;

use App\Models\Survey;
use App\Services\AiProviderService;
use App\Services\SurveyAiAppreciationService;
use Mockery;
use Tests\TestCase;

class SurveyAiServiceGenerationTest extends TestCase
{
    public function test_generate_calls_prism_correctly_and_saves_appreciation()
    {
        // Setup data
        $survey = Survey::create([
            'title' => 'Test Survey',
            'active' => true,
        ]);

        // Mocks
        $mockAiProvider = Mockery::mock(AiProviderService::class);
        $mockPendingRequest = Mockery::mock('stdClass'); // Mocking the fluent interface
        $mockResponse = Mockery::mock('stdClass');
        $mockUsage = Mockery::mock('stdClass');

        // Setup Response structure
        $mockUsage->promptTokens = 100;
        $mockUsage->completionTokens = 50;
        $mockUsage->thoughtTokens = 10;
        
        $mockResponse->text = "AI Analysis Result";
        $mockResponse->usage = $mockUsage;

        // Setup Fluent Chain
        $mockPendingRequest->shouldReceive('using')->with('openai', 'gpt-5-nano')->andReturnSelf();
        $mockPendingRequest->shouldReceive('withPrompt')->andReturnSelf();
        $mockPendingRequest->shouldReceive('asText')->once()->andReturn($mockResponse);

        $mockAiProvider->shouldReceive('text')->once()->andReturn($mockPendingRequest);

        // Bind mock
        $this->app->instance(AiProviderService::class, $mockAiProvider);

        // Execute
        $service = app(SurveyAiAppreciationService::class);
        $appreciation = $service->generate($survey);

        // Assert
        $this->assertNotNull($appreciation);
        $this->assertEquals($survey->id, $appreciation->survey_id);
        $this->assertEquals("AI Analysis Result", $appreciation->content);
        $this->assertEquals(150, $appreciation->usage_tokens); // 100 + 50
        $this->assertEquals(10, $appreciation->reasoning_tokens);
    }
}
