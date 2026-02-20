<?php

namespace Tests\Unit;

use App\Models\BirthdayGreeting;
use App\Models\User;
use App\Services\BirthdayGreetingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BirthdayGreetingServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_replaces_placeholders_and_makes_urls_absolute(): void
    {
        $user = User::factory()->create([
            'name' => 'Juan Pérez',
            'email' => 'juan@example.com',
        ]);

        BirthdayGreeting::create([
            'user_id' => $user->id,
            'content' => '<p>¡Feliz día, {{nombre}}! Saludos de {{empresa}}.</p><img src="/storage/test.png">',
        ]);

        config(['app.url' => 'https://app.test']);

        $service = app(BirthdayGreetingService::class);
        $html = $service->renderContent($user);

        $this->assertStringContainsString('Juan Pérez', $html);
        $this->assertStringContainsString('https://app.test/storage/test.png', $html);
    }
}

