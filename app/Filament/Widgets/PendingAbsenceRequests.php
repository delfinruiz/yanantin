<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\AbsenceRequests\AbsenceRequestResource;
use App\Models\AbsenceRequest;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Facades\Auth;
use Filament\Actions\Action;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;

class PendingAbsenceRequests extends BaseWidget
{
    use HasWidgetShield;

    protected static ?int $sort = 2;
    protected int | string | array $columnSpan = 'full';
    protected static ?string $heading = 'Solicitudes de Ausencia Pendientes';

    public function table(Table $table): Table
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        return $table
            ->query(
                AbsenceRequest::query()
                    ->accessibleBy($user)
                    ->whereIn('status', ['pending', 'approved_supervisor'])
            )
            ->columns([
                Tables\Columns\TextColumn::make('employee.user.name')
                    ->label('Empleado')
                    ->sortable(),
                Tables\Columns\TextColumn::make('type.name')
                    ->label('Tipo')
                    ->badge()
                    ->color(fn ($record) => $record->type->color ?? 'gray'),
                Tables\Columns\TextColumn::make('start_date')
                    ->label('Desde')
                    ->date('d-m-Y'),
                Tables\Columns\TextColumn::make('end_date')
                    ->label('Hasta')
                    ->date('d-m-Y'),
                Tables\Columns\TextColumn::make('days_requested')
                    ->label('Días'),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'approved_supervisor' => 'info',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending' => 'Pendiente Supervisor',
                        'approved_supervisor' => 'Pendiente RRHH',
                        default => $state,
                    }),
            ])
            ->recordActions([
                Action::make('review')
                    ->label('Gestionar')
                    ->icon('heroicon-o-arrow-right')
                    ->url(fn (): string => AbsenceRequestResource::getUrl('index')),
            ]);
    }
}
