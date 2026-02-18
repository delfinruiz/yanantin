<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\User;

class AiMessageService
{
    public function generateDailyMessage(User $user, string $mood): ?array
    {
        $apiKey = config('services.openai.key') ?: env('OPENAI_API_KEY');
        $model = config('services.openai.model', 'gpt-4o-mini');

        $system = "Eres un asistente de bienestar laboral. Devuelve un mensaje corto (máx 240 caracteres), positivo, en segunda persona, en español, con 1-2 emojis, alineado al estado de ánimo. No incluyas encabezados ni listas.";
        $prompt = "Usuario: {$user->name}\nEstado de ánimo: {$mood}\nContexto: empresa quiere mejorar productividad cuidando bienestar.\nTarea: Genera un 'Mensaje del día' personalizado y motivador.";

        if (!$apiKey) {
            return [
                'message' => $this->fallbackMessage($mood, $user->name),
                'model' => 'fallback',
            ];
        }

        try {
            $response = Http::withToken($apiKey)
                ->timeout(15)
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => $model,
                    'temperature' => 0.7,
                    'messages' => [
                        ['role' => 'system', 'content' => $system],
                        ['role' => 'user', 'content' => $prompt],
                    ],
                ]);
            $text = trim((string) data_get($response, 'choices.0.message.content', ''));
            return [
                'message' => $text ?: $this->fallbackMessage($mood, $user->name),
                'model' => $model,
            ];
        } catch (\Throwable $e) {
            Log::warning('OpenAI daily message error: '.$e->getMessage());
            return [
                'message' => $this->fallbackMessage($mood, $user->name),
                'model' => 'fallback',
            ];
        }
    }

    public function generateCompanySuggestions(array $stats): ?array
    {
        $apiKey = config('services.openai.key') ?: env('OPENAI_API_KEY');
        $model = config('services.openai.model', 'gpt-4o-mini');

        $summary = json_encode($stats, JSON_UNESCAPED_UNICODE);
        $system = "Eres consultor en RR.HH. y bienestar. Devuelve 4-6 acciones concretas, numeradas, cada una en ~1 línea, enfocadas en mejorar el clima laboral y productividad. Español neutro.";
        $prompt = "Datos de ánimo agregados: {$summary}\nPropón acciones priorizadas para la empresa. Evita jerga técnica.";

        if (!$apiKey) {
            return [
                'text' => $this->fallbackSuggestions($stats),
                'model' => 'fallback',
            ];
        }

        try {
            $response = Http::withToken($apiKey)
                ->timeout(20)
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => $model,
                    'temperature' => 0.6,
                    'messages' => [
                        ['role' => 'system', 'content' => $system],
                        ['role' => 'user', 'content' => $prompt],
                    ],
                ]);
            $text = trim((string) data_get($response, 'choices.0.message.content', ''));
            return [
                'text' => $text ?: $this->fallbackSuggestions($stats),
                'model' => $model,
            ];
        } catch (\Throwable $e) {
            Log::warning('OpenAI suggestions error: '.$e->getMessage());
            return [
                'text' => $this->fallbackSuggestions($stats),
                'model' => 'fallback',
            ];
        }
    }

    protected function fallbackMessage(string $mood, string $name): string
    {
        return match ($mood) {
            'happy' => "¡Excelente, {$name}! Mantén esa energía y compártela con tu equipo. 🌟",
            'med_happy' => "Vas por buen camino, {$name}. Da un paso a la vez y celebra avances. 🙂",
            'neutral' => "Respira y enfócate en lo esencial hoy, {$name}. ¡Tú puedes! 🌿",
            'med_sad' => "Tómate una pausa breve, {$name}. Pide apoyo si lo necesitas. 🤝",
            default => "Día retador, {$name}. Cuida tu bienestar: micro descansos y prioridades claras. 💙",
        };
    }

    protected function fallbackSuggestions(array $stats): string
    {
        return "1) Promover pausas activas y check-ins breves diarios\n"
            ."2) Reconocer logros semanales en equipo\n"
            ."3) Habilitar horarios flexibles en días de alta carga\n"
            ."4) Ofrecer espacios de escucha con jefaturas\n"
            ."5) Organizar micro-capacitaciones de manejo del estrés";
    }
}
