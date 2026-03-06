<?php

namespace App\Livewire;

use App\Models\JobApplication;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Filament\Actions\Contracts\HasActions;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Action; // Filament v4 unified action namespace
use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Filament\Notifications\Notification;

class MyApplicationsTable extends Component implements HasForms, HasTable, HasActions
{
    use InteractsWithForms;
    use InteractsWithTable;
    use InteractsWithActions;

    protected $listeners = ['applicationSubmitted' => '$refresh'];

    public function table(Table $table): Table
    {
        return $table
            ->query(
                JobApplication::query()
                    ->where('user_id', Auth::id())
                    ->with('jobOffer')
                    ->latest('submitted_at')
            )
            ->columns([
                Tables\Columns\TextColumn::make('jobOffer.title')
                    ->label('Oferta')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('jobOffer.city')
                    ->label('Ubicación')
                    ->state(fn (JobApplication $record) => ($record->jobOffer->city ?? '') . ', ' . ($record->jobOffer->country ?? '')),
                Tables\Columns\TextColumn::make('submitted_at')
                    ->label('Fecha de postulación')
                    ->dateTime('d M, Y H:i')
                    ->sortable()
                    ->state(fn (JobApplication $record) => $record->submitted_at ?? $record->created_at),
                Tables\Columns\TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'submitted', 'pending' => 'info',
                        'reviewed' => 'warning',
                        'hired' => 'success',
                        'rejected' => 'danger',
                        'cancelled' => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'submitted', 'pending' => 'Enviada',
                        'reviewed' => 'En revisión',
                        'hired' => 'Seleccionado',
                        'rejected' => 'No seleccionado',
                        'cancelled' => 'Cancelada',
                        default => ucfirst($state),
                    }),
            ])
            ->recordActions([
                Action::make('cancel')
                    ->label('Cancelar Postulación')
                    ->color('danger')
                    ->icon('heroicon-o-x-circle')
                    ->requiresConfirmation()
                    ->modalHeading('Cancelar Postulación')
                    ->modalDescription('¿Estás seguro de que deseas cancelar tu postulación a esta oferta? Esta acción no se puede deshacer.')
                    ->visible(fn (JobApplication $record) => in_array($record->status, ['submitted', 'pending', 'reviewed'])) // Permitir cancelar incluso si está en revisión, según requerimiento
                    ->action(function (JobApplication $record) {
                        $record->update(['status' => 'cancelled']);
                        
                        Notification::make()
                            ->title('Postulación cancelada')
                            ->body('Tu postulación ha sido cancelada exitosamente.')
                            ->success()
                            ->send();
                    }),
            ])
            ->emptyStateHeading('No has realizado postulaciones aún.');
    }

    public function render()
    {
        return view('livewire.my-applications-table');
    }
}