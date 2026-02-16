<?php

namespace App\Filament\Resources\Evaluations\EvaluationResultResource\Pages;

use App\Filament\Resources\Evaluations\EvaluationResultResource;
use App\Models\EvaluationCycle;
use App\Models\StrategicObjective;
use App\Services\Evaluations\EvaluationCalculator;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\Width;

class ListEvaluationResults extends ListRecords
{
    protected static string $resource = EvaluationResultResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            Action::make('generate_results')
                ->label('Generar Resultados')
                ->icon('heroicon-o-calculator')
                ->schema([
                    Select::make('evaluation_cycle_id')
                        ->label('Ciclo de Evaluación')
                        ->options(EvaluationCycle::all()->pluck('name', 'id'))
                        ->required(),
                ])
                ->action(function (array $data) {
                    $cycleId = $data['evaluation_cycle_id'];
                    $cycle = EvaluationCycle::find($cycleId);
                    
                    if (!$cycle) {
                        Notification::make()
                            ->title('Ciclo no encontrado')
                            ->danger()
                            ->send();
                        return;
                    }

                    // Encontrar usuarios con objetivos en este ciclo (aprobados)
                    $userIds = StrategicObjective::where('evaluation_cycle_id', $cycleId)
                        ->where('status', 'approved')
                        ->distinct()
                        ->pluck('owner_user_id');

                    if ($userIds->isEmpty()) {
                        Notification::make()
                            ->title('No se encontraron empleados con objetivos aprobados en este ciclo')
                            ->warning()
                            ->send();
                        return;
                    }

                    $calculator = new EvaluationCalculator();
                    $count = 0;

                    foreach ($userIds as $userId) {
                        $user = \App\Models\User::find($userId);
                        if ($user) {
                            $calculator->computeForEmployee($cycle, $user);
                            $count++;
                        }
                    }

                    Notification::make()
                        ->title("Resultados generados para {$count} empleados")
                        ->success()
                        ->send();
                }),
        ];
    }
    
    public function getMaxContentWidth(): Width
    {
        return Width::Full;
    }
}

