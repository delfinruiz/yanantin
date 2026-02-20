<?php

namespace App\Livewire;

use App\Filament\Pages\MonthlyDashboard;
use App\Models\Task;
use App\Models\Expense;
use App\Models\Survey;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Illuminate\Support\Facades\Auth;
use App\Filament\Pages\MySurveys;

class PrincipalMarcadoresWidget extends BaseWidget
{
    protected ?string $pollingInterval = null;
    protected int|string|array $columnSpan = 9;
    protected static ?int $sort = 3;
    //protected ?string $heading = 'Año Actual';

    //protected ?string $description = 'Contador de tareas pendientes, en curso y completadas en el año actual';

    protected function getPollingInterval(): ?string
    {
        return null;
    }

    protected function getStats(): array
    {
        return [
            Stat::make(__('dashboard.stats.pay.label'), $this->countByStatusForUserExpense('0'))
                ->description(__('dashboard.stats.pay.description'))
                ->descriptionIcon('heroicon-o-credit-card')
                ->color(fn() => $this->countByStatusForUserExpense('0') > 0 ? 'danger' : 'success')
                ->extraAttributes([
                    'class' => 'cursor-pointer',
                ])
                ->url(MonthlyDashboard::getUrl() . '#expenses'),
            Stat::make(__('dashboard.stats.do.label'), $this->countByStatusForUser('pending'))
                ->description(__('dashboard.stats.do.description'))
                ->descriptionIcon('heroicon-o-puzzle-piece')
                ->color(fn() => $this->countByStatusForUser('pending') > 0 ? 'danger' : 'success'),
        ];
    }

    protected function pendingSurveysCount(): int
    {
        $userId = Auth::id();
        if (! $userId) return 0;

        return Survey::whereHas('users', fn ($q) => $q->where('users.id', $userId))
            ->where(function ($q) {
                $q->whereNull('deadline')
                  ->orWhere('deadline', '>=', now());
            })
            ->whereHas('questions', function ($q) use ($userId) {
                $q->where('required', true)
                  ->whereDoesntHave('responses', fn ($r) => $r->where('user_id', $userId));
            })->count();
    }


    //necsito rescatar el valor de una variable publica de la clase Tasks.php
    public int $currentYear;

    //mount para rescatar el valor de la variable publica currentYear de la clase Tasks.php
    public function mount(): void
    {
        $this->currentYear = date('Y');
    }

    protected function countByStatusForUser(string $status): int
    {
        $userId = Auth::id();

        return Task::where(function ($q) use ($userId) {
            $q->where('assigned_to', $userId);
        })
            ->whereYear('created_at', $this->currentYear) // FILTRO DEL AÑO ACTUAL  
            ->whereHas('status', fn($q) => $q->where('title', $status))
            ->count();
    }

    //funcion para obtener del modelo expense el total de las cuentas por pagar segun el status sumar todos los 0
    protected function countByStatusForUserExpense(string $status): int
    {
        $userId = Auth::id();

        return Expense::where(function ($q) use ($userId) {
            $q->where('user_id', $userId);
        })
            ->whereYear('created_at', $this->currentYear) // FILTRO DEL AÑO ACTUAL  
            ->where('status', $status)
            ->count();
    }

}
