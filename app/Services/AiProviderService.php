<?php

namespace App\Services;

use Prism\Prism\Facades\Prism;

class AiProviderService
{
    public function hasToken(): bool
    {
        $token = app(SettingService::class)->get('token_ai');
        return is_string($token) && strlen($token) > 0;
    }

    public function configureOpenAi(): void
    {
        $token = app(SettingService::class)->get('token_ai');
        if ($token) {
            config(['prism.providers.openai.api_key' => $token]);
        }
    }

    public function text()
    {
        $this->configureOpenAi();
        return Prism::text();
    }
}
