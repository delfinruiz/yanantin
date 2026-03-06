<?php

namespace App\Services;

use App\Models\JobApplication;
use App\Models\JobOffer;
use App\Models\JobOfferRequirement;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class ApplicationScoringService
{
    public function __construct(
        protected OpenAiCandidateAnalysisService $aiAnalysisService
    ) {}

    /**
     * Procesa una aplicación para determinar elegibilidad y puntaje.
     */
    public function processApplication(JobApplication $application): void
    {
        // Evitar reprocesar si ya fue evaluada (salvo que se fuerce manualmente en otro contexto)
        if ($application->eligibility_status !== null) {
            return;
        }

        $jobOffer = $application->jobOffer;
        // Usamos cv_snapshot para asegurar integridad histórica de los datos al momento de postular
        $candidateData = $application->cv_snapshot;

        if (empty($candidateData)) {
            Log::warning("Application {$application->id} has no cv_snapshot. Skipping scoring.");
            return;
        }

        // 1. Validación de Requisitos Obligatorios
        $eligibilityResult = $this->checkEligibility($jobOffer, $candidateData);

        if ($eligibilityResult['status'] === 'not_eligible') {
            $application->update([
                'eligibility_status' => 'not_eligible',
                'rejection_reason' => $eligibilityResult['reason'],
                'auto_decision_log' => $eligibilityResult['log'],
                'score' => 0,
                'auto_processed_at' => now(),
                'status' => 'reviewed', // Cambiar a 'reviewed' para indicar que fue analizado, pero NO 'rejected'
            ]);
            
            // Forzar ejecución de IA incluso si es rechazado, para mostrar Score IA y análisis
            $this->runAiAnalysis($application); 
            return;
        }

        // 2. Cálculo de Puntaje (Scoring) si es elegible
        $scoreResult = $this->calculateScore($jobOffer, $candidateData);

        $application->update([
            'eligibility_status' => 'eligible',
            'score' => $scoreResult['total_score'],
            'auto_decision_log' => array_merge($eligibilityResult['log'], $scoreResult['log']),
            'auto_processed_at' => now(),
            'status' => 'reviewed', // Marcar como revisado automáticamente
        ]);

        // 3. Ejecutar Análisis IA Automático
        $this->runAiAnalysis($application);
    }

    private function runAiAnalysis(JobApplication $application): void
    {
        try {
            $analysis = $this->aiAnalysisService->analyzeApplication($application);
            if (! isset($analysis['error'])) {
                $application->update(['ai_analysis' => $analysis]);
            }
        } catch (\Exception $e) {
            Log::error("Error running AI analysis for application {$application->id}: " . $e->getMessage());
        }
    }

    /**
     * Verifica si el candidato cumple con los requisitos obligatorios ("Must Haves").
     */
    private function checkEligibility(JobOffer $jobOffer, array $candidateData): array
    {
        $log = [];
        
        // Obtener requisitos obligatorios desde la relación
        $requiredExperience = $this->getRequiredExperienceYears($jobOffer);
        $requiredEducationLevel = $this->getRequiredEducationLevel($jobOffer);
        $requiredSkills = $this->getRequiredSkills($jobOffer);

        // 1. Validar Experiencia
        $candidateExperience = $this->calculateCandidateExperienceYears($candidateData);
        $experiencePassed = $candidateExperience >= $requiredExperience;

        $log['experience_check'] = [
            'required' => $requiredExperience,
            'candidate' => $candidateExperience,
            'passed' => $experiencePassed
        ];
        
        if (! $experiencePassed) {
            return [
                'status' => 'not_eligible', 
                'reason' => 'Experiencia insuficiente',
                'log' => $log
            ];
        }

        // 2. Validar Educación
        $candidateEducationLevel = $this->getCandidateEducationLevel($candidateData);
        $educationPassed = $this->compareEducationLevels($candidateEducationLevel, $requiredEducationLevel) >= 0;
        
        $log['education_check'] = [
            'required' => $requiredEducationLevel,
            'candidate' => $candidateEducationLevel,
            'passed' => $educationPassed
        ];

        if (! $educationPassed) {
            return [
                'status' => 'not_eligible', 
                'reason' => 'Nivel educativo insuficiente',
                'log' => $log
            ];
        }

        // 3. Validar Habilidades Obligatorias
        if (! empty($requiredSkills)) {
            $candidateSkills = $this->getCandidateSkills($candidateData);
            $missingSkills = $this->findMissingSkills($requiredSkills, $candidateSkills);
            
            $skillsPassed = empty($missingSkills);
            $log['skills_check'] = [
                'required_count' => count($requiredSkills),
                'missing_count' => count($missingSkills),
                'passed' => $skillsPassed
            ];

            if (! $skillsPassed) {
                return [
                    'status' => 'not_eligible', 
                    'reason' => 'Faltan habilidades obligatorias',
                    'log' => $log
                ];
            }
        } else {
            $log['skills_check'] = ['status' => 'skipped_no_requirements'];
        }

        return ['status' => 'eligible', 'reason' => null, 'log' => $log];
    }

    /**
     * Calcula el puntaje para candidatos elegibles ("Nice to Haves").
     */
    private function calculateScore(JobOffer $jobOffer, array $candidateData): array
    {
        $score = 0;
        $log = [];

        // Configuración de pesos (Total: 100)
        // Ajustamos para distribuir los puntos de certificaciones si no tenemos datos
        $weights = [
            'extra_experience' => 30,
            'desirable_skills' => 40,
            'superior_education' => 10,
            'certifications' => 20,
        ];

        // 1. Experiencia Adicional (Max 30 pts)
        $requiredExperience = $this->getRequiredExperienceYears($jobOffer);
        $candidateExperience = $this->calculateCandidateExperienceYears($candidateData);
        $extraYears = max(0, $candidateExperience - $requiredExperience);
        
        // 5 puntos por cada año extra hasta llegar al máximo
        $experienceScore = min($weights['extra_experience'], $extraYears * 5);
        
        $score += $experienceScore;
        $log['scoring_experience'] = [
            'extra_years' => $extraYears,
            'points' => $experienceScore
        ];

        // 2. Habilidades Deseables (Max 40 pts)
        $desirableSkills = $this->getDesirableSkills($jobOffer);
        $candidateSkills = $this->getCandidateSkills($candidateData);
        $skillsScore = 0;

        if (! empty($desirableSkills)) {
            $matchedSkillsCount = 0;
            $normalizedCandidateSkills = array_map('strtolower', $candidateSkills);
            
            foreach ($desirableSkills as $skill) {
                if (in_array(strtolower($skill), $normalizedCandidateSkills)) {
                    $matchedSkillsCount++;
                }
            }
            
            $pointsPerSkill = $weights['desirable_skills'] / count($desirableSkills);
            $skillsScore = round($matchedSkillsCount * $pointsPerSkill, 2);
        }

        $score += $skillsScore;
        $log['scoring_skills'] = [
            'matched_count' => $matchedSkillsCount ?? 0,
            'total_desirable' => count($desirableSkills),
            'points' => $skillsScore
        ];

        // 3. Educación Superior (Max 10 pts)
        $requiredEducationLevel = $this->getRequiredEducationLevel($jobOffer);
        $candidateEducationLevel = $this->getCandidateEducationLevel($candidateData);
        
        $educationScore = 0;
        if ($this->compareEducationLevels($candidateEducationLevel, $requiredEducationLevel) > 0) {
            $educationScore = $weights['superior_education'];
        }

        $score += $educationScore;
        $log['scoring_education'] = [
            'is_superior' => $educationScore > 0,
            'points' => $educationScore
        ];

        // 4. Certificaciones (Pendiente: no tenemos estructura clara en cv_snapshot para esto aun)
        // Asignamos 0 por ahora.
        $log['scoring_certifications'] = ['points' => 0, 'note' => 'Not implemented yet'];

        return [
            'total_score' => min(100, $score), // Cap en 100
            'log' => $log
        ];
    }

    // --- Helpers de Extracción de Datos ---

    private function getRequiredExperienceYears(JobOffer $jobOffer): int
    {
        $requirement = $jobOffer->jobOfferRequirements()
            ->where('category', 'Experiencia laboral')
            ->where('type', 'Obligatorio')
            ->first();

        if (! $requirement) return 0;

        // Parsear "5+ años" -> 5
        if (preg_match('/(\d+)/', $requirement->level, $matches)) {
            return (int) $matches[1];
        }

        return 0;
    }

    private function calculateCandidateExperienceYears(array $candidateData): int
    {
        // Asumimos que cv_snapshot tiene 'work_experience' como array de objetos con start_date y end_date
        $workExperience = $candidateData['work_experience'] ?? [];
        $totalMonths = 0;

        foreach ($workExperience as $job) {
            // Si es array, acceder por clave, si es objeto, por propiedad (depende de cómo se guarde el snapshot)
            // Asumimos array asociativo por el cast 'array' en el modelo
            $start = isset($job['start_date']) ? Carbon::parse($job['start_date']) : null;
            $end = isset($job['end_date']) ? Carbon::parse($job['end_date']) : Carbon::now(); // Si sigue trabajando

            if ($start) {
                $totalMonths += $start->diffInMonths($end);
            }
        }

        return (int) floor($totalMonths / 12);
    }

    private function getRequiredEducationLevel(JobOffer $jobOffer): string
    {
        // Buscar en requirements categoría Educación
        $requirement = $jobOffer->jobOfferRequirements()
            ->where('category', 'Educación')
            ->where('type', 'Obligatorio')
            ->first();

        return $requirement ? ($requirement->level ?? 'tecnico') : 'tecnico'; // Default bajo
    }

    private function getCandidateEducationLevel(array $candidateData): string
    {
        // Obtener el nivel más alto de educación del candidato
        $education = $candidateData['education'] ?? [];
        $highestLevel = 'tecnico'; // Base
        
        foreach ($education as $edu) {
            $level = $edu['level'] ?? ''; // Asumimos campo 'level' en el form del candidato
            if ($this->compareEducationLevels($level, $highestLevel) > 0) {
                $highestLevel = $level;
            }
        }
        
        return $highestLevel;
    }

    private function compareEducationLevels(string $levelA, string $levelB): int
    {
        $hierarchy = [
            'secundaria' => 1,
            'tecnico' => 2,
            'profesional' => 3,
            'licenciatura' => 4,
            'magister' => 5,
            'doctorado' => 6,
        ];

        $valA = $hierarchy[strtolower(trim($levelA))] ?? 0;
        $valB = $hierarchy[strtolower(trim($levelB))] ?? 0;

        return $valA <=> $valB;
    }

    private function getRequiredSkills(JobOffer $jobOffer): array
    {
        // Skills podrían estar como requirements con categoría 'Habilidad técnica', 'Habilidad blanda' o 'Idioma'
        $requirements = $jobOffer->jobOfferRequirements()
            ->whereIn('category', ['Habilidad técnica', 'Habilidad blanda', 'Idioma']) 
            ->where('type', 'Obligatorio')
            ->get();
            
        // Usamos 'evidence' como el nombre del skill.
        return $requirements->pluck('evidence')->filter()->map(fn($item) => trim($item))->toArray(); 
    }

    private function getDesirableSkills(JobOffer $jobOffer): array
    {
        $requirements = $jobOffer->jobOfferRequirements()
            ->whereIn('category', ['Habilidad técnica', 'Habilidad blanda', 'Idioma'])
            ->where('type', 'Deseable')
            ->get();

        return $requirements->pluck('evidence')->filter()->map(fn($item) => trim($item))->toArray();
    }

    private function getCandidateSkills(array $candidateData): array
    {
        // Unificar technical_skills, soft_skills y languages
        $technical = $candidateData['technical_skills'] ?? []; 
        $soft = $candidateData['soft_skills'] ?? [];
        $languages = $candidateData['languages'] ?? []; 

        $skills = [];

        // Normalizar a array de strings
        foreach ($technical as $s) {
            // El repeater guarda 'software' como nombre
            $name = is_array($s) ? ($s['software'] ?? $s['name'] ?? '') : $s;
            if ($name) $skills[] = trim($name);
        }
        foreach ($soft as $s) {
            // El repeater guarda 'skill' como nombre
            $name = is_array($s) ? ($s['skill'] ?? $s['name'] ?? '') : $s;
            if ($name) $skills[] = trim($name);
        }
        foreach ($languages as $l) {
            // El repeater guarda 'language' como nombre
            $name = is_array($l) ? ($l['language'] ?? $l['name'] ?? '') : $l;
            if ($name) $skills[] = trim($name);
        }

        return array_unique(array_filter($skills));
    }

    private function findMissingSkills(array $required, array $candidate): array
    {
        $missing = [];
        $candidateNormalized = array_map('strtolower', array_map('trim', $candidate));

        foreach ($required as $req) {
            if (! in_array(strtolower(trim($req)), $candidateNormalized)) {
                $missing[] = $req;
            }
        }

        return $missing;
    }
}
