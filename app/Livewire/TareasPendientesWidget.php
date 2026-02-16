<?php

namespace App\Livewire;

use App\Models\Task;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Illuminate\Support\Facades\Auth;

class TareasPendientesWidget extends BaseWidget
{
    protected ?string $pollingInterval = null;
    public ?string $heading = null;

    public ?string $description = null;

    protected function getPollingInterval(): ?string
    {
        return null;
    }

    protected function getStats(): array
    {
        return [
            Stat::make('', $this->countByStatusForUser('completed'))
                ->description(__('Tasks_Completed'))
                ->descriptionIcon('heroicon-o-face-smile')
                ->color('success'),
            Stat::make('', $this->countByStatusForUser('in_progress'))
                ->description(__('Tasks_In_Progress'))
                ->descriptionIcon('heroicon-o-arrow-path-rounded-square')
                ->color('warning'),
            Stat::make('', $this->countByStatusForUser('pending'))
                ->description(__('Tasks_Pending'))
                ->descriptionIcon('heroicon-o-face-frown')
                ->color('danger'),
        ];
    }


    //necsito rescatar el valor de una variable publica de la clase Tasks.php
    public int $currentYear;

    //mount para rescatar el valor de la variable publica currentYear de la clase Tasks.php
    public function mount(): void
    {
        $this->currentYear = date('Y');
        $this->heading = __('Current_Year');
        $this->description = __('Task_Counter_Description');
    }



    protected function countByStatusForUser(string $status): int
    {

        $userId = Auth::id();

        if (! $userId) {
            return 0;
        }

        return Task::where(function ($q) use ($userId) {
            $q->where('created_by', $userId)
                ->orWhere('assigned_to', $userId);
        })
            ->whereYear('created_at', $this->currentYear) // FILTRO DEL AÑO ACTUAL  
            ->whereHas('status', fn($q) => $q->where('title', $status))
            ->count();
    }
}
