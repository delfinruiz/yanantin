<?php

namespace App\Services;

use App\Models\JobApplication;
use Illuminate\Support\Facades\Log;
use Prism\Prism\Enums\Provider;

class OpenAiCandidateAnalysisService
{
    public function __construct(
        protected AiProviderService $aiProvider
    ) {}

    /**
     * Analiza cualitativamente una postulación usando IA.
     */
    public function analyzeApplication(JobApplication $application): array
    {
        try {
            $jobOffer = $application->jobOffer;
            $candidateData = $application->cv_snapshot;

            // Preparar el prompt
            $prompt = $this->buildPrompt($jobOffer, $candidateData, $application);

            // Llamar a Prism a través del AiProviderService
            $response = $this->aiProvider->text()
                ->using(Provider::OpenAI, 'gpt-4o-mini') // O gpt 4 mini según preferencia/costo
                ->withSystemPrompt('Eres un experto reclutador de RRHH. Tu tarea es analizar objetivamente la idoneidad de un candidato para una oferta laboral.')
                ->withPrompt($prompt)
                ->generate();

            // Extraer JSON de la respuesta
            $responseText = $response->text;
            $jsonContent = $this->extractJson($responseText);
            
            return json_decode($jsonContent, true) ?? [];

        } catch (\Throwable $e) {
            Log::error("Error en OpenAiCandidateAnalysisService para Application ID {$application->id}: " . $e->getMessage());
            return ['error' => 'No se pudo realizar el análisis: ' . $e->getMessage()];
        }
    }

    private function buildPrompt($jobOffer, $candidateData, $application): string
    {
        $offerDetails = json_encode([
            'title' => $jobOffer->title,
            'description' => strip_tags($jobOffer->description),
            'requirements' => $jobOffer->jobOfferRequirements->map(fn($r) => "{$r->type}: {$r->category} - {$r->level} ({$r->evidence})")->toArray(),
        ], JSON_UNESCAPED_UNICODE);

        $languages = $candidateData['languages'] ?? [];
        $languages = collect(is_array($languages) ? $languages : [])
            ->map(function ($item) {
                if (is_array($item)) {
                    return [
                        'language' => $item['language'] ?? $item['name'] ?? null,
                        'level' => $item['level'] ?? null,
                    ];
                }

                return [
                    'language' => is_string($item) ? $item : null,
                    'level' => null,
                ];
            })
            ->filter(fn (array $row) => ! empty($row['language']))
            ->values()
            ->all();

        $candidateDetails = json_encode([
            'experience' => $candidateData['work_experience'] ?? [],
            'education' => $candidateData['education'] ?? [],
            'skills' => array_merge($candidateData['technical_skills'] ?? [], $candidateData['soft_skills'] ?? []),
            'languages' => $languages,
        ], JSON_UNESCAPED_UNICODE);

        return <<<EOT
Analiza la siguiente postulación de manera independiente y crítica.

DATOS DE LA OFERTA (Analiza en profundidad):
$offerDetails

DATOS DEL CANDIDATO (Evalúa ajuste real):
$candidateDetails

CONTEXTO DEL SISTEMA (Solo como referencia, TU JUICIO ES INDEPENDIENTE):
- El sistema automático marcó elegibilidad como: {$application->eligibility_status}
- Score técnico calculado: {$application->score}/100

INSTRUCCIONES CRÍTICAS:
1. TU OBJETIVO: Evaluar el "Ajuste Cualitativo" y potencial real, más allá de palabras clave.
2. Si el candidato fue rechazado por el sistema, analiza si es un "falso negativo" (ej: tiene la experiencia pero escrita diferente) o confirma el rechazo.
3. Si el candidato fue aprobado, busca "red flags" o debilidades ocultas.
4. El "qualitative_score" es TU evaluación (0-100). Puede ser muy distinto al score técnico. Un candidato con score técnico bajo podría tener un alto potencial cualitativo (y viceversa).
5. Sé estricto con los requisitos obligatorios pero flexible con la terminología.
6. NO INVENTES DATOS: si no existe evidencia explícita en DATOS DEL CANDIDATO (por ejemplo nivel de idioma), indícalo como "no informado" y no lo asumas.

FORMATO DE SALIDA (JSON VÁLIDO ÚNICAMENTE):
{
    "qualitative_score": (0-100, tu valoración experta independiente),
    "summary": "Resumen ejecutivo: ¿Por qué sí o por qué no? (max 50 palabras)",
    "strengths": ["Fortaleza cualitativa 1", "Fortaleza 2", "Fortaleza 3"],
    "weaknesses": ["Riesgo/Debilidad 1", "Riesgo 2"],
    "interview_questions": ["Pregunta clave para validar experiencia", "Pregunta de ajuste cultural"]
}
EOT;
    }

    private function extractJson(string $text): string
    {
        // Intenta encontrar el bloque JSON si hay texto alrededor
        if (preg_match('/\{[\s\S]*\}/', $text, $matches)) {
            return $matches[0];
        }
        return $text;
    }
}
