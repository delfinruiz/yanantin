<?php

namespace App\Observers;

use App\Models\JobApplication;
use App\Services\ApplicationScoringService;

class JobApplicationObserver
{
    public function __construct(
        protected ApplicationScoringService $scoringService
    ) {}

    /**
     * Handle the JobApplication "created" event.
     */
    public function created(JobApplication $jobApplication): void
    {
        // Ejecutar scoring automático al crear la postulación
        $this->scoringService->processApplication($jobApplication);
    }

    /**
     * Handle the JobApplication "updated" event.
     */
    public function updated(JobApplication $jobApplication): void
    {
        //
    }

    /**
     * Handle the JobApplication "deleted" event.
     */
    public function deleted(JobApplication $jobApplication): void
    {
        //
    }

    /**
     * Handle the JobApplication "restored" event.
     */
    public function restored(JobApplication $jobApplication): void
    {
        //
    }

    /**
     * Handle the JobApplication "force deleted" event.
     */
    public function forceDeleted(JobApplication $jobApplication): void
    {
        //
    }
}
