<?php

namespace App\Jobs;

use App\Models\JobOffer;
use App\Services\ApplicationScoringService;
use App\Models\JobApplication;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class RecalculateOfferScores implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public JobOffer $jobOffer
    ) {}

    /**
     * Execute the job.
     */
    public function handle(ApplicationScoringService $scoringService): void
    {
        Log::info("RecalculateOfferScores Job started for Offer ID: {$this->jobOffer->id}");

        // Aumentar tiempo de ejecución
        set_time_limit(600);

        $applications = JobApplication::where('job_offer_id', $this->jobOffer->id)->get();
        
        foreach ($applications as $app) {
            try {
                // Resetear estado para forzar recálculo
                $app->eligibility_status = null;
                $app->ai_analysis = null;
                
                $scoringService->processApplication($app);
            } catch (\Exception $e) {
                Log::error("Error recalculating application {$app->id}: " . $e->getMessage());
            }
        }

        Log::info("RecalculateOfferScores Job completed for Offer ID: {$this->jobOffer->id}. Processed " . $applications->count() . " applications.");
    }
}
