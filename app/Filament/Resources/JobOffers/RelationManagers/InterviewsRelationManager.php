<?php

namespace App\Filament\Resources\JobOffers\RelationManagers;


use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Models\JobInterview;
use App\Models\Survey;
use App\Models\User;
use Filament\Notifications\Notification;
use Filament\Forms; // Still needed for components

use Filament\Schemas\Schema;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction; // Se usa ahora

class InterviewsRelationManager extends RelationManager
{
    protected static string $relationship = 'interviews';

    protected static ?string $title = 'Entrevistas Programadas';

    protected static string|\BackedEnum|null $icon = 'heroicon-o-chat-bubble-left-right';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\Select::make('job_application_id')
                    ->label('Candidato')
                    ->options(function ($livewire) {
                        $jobOffer = $livewire->getOwnerRecord();
                        // Verificar si jobOffer es instancia válida, sino intentar recuperarla
                        if (! $jobOffer || ! method_exists($jobOffer, 'jobApplications')) {
                             return \App\Models\JobApplication::pluck('applicant_name', 'id');
                        }
                        return $jobOffer->jobApplications()->pluck('applicant_name', 'id');
                    })
                    ->required()
                    ->searchable(),
                
                Forms\Components\Select::make('survey_id')
                    ->label('Plantilla de Entrevista')
                    ->options(Survey::where('is_interview', true)->where('active', true)->pluck('title', 'id'))
                    ->required()
                    ->searchable(),

                Forms\Components\Select::make('interviewer_id')
                    ->label('Entrevistador')
                    ->options(User::pluck('name', 'id'))
                    ->required()
                    ->searchable(),

                Forms\Components\DateTimePicker::make('scheduled_at')
                    ->label('Fecha y Hora')
                    ->required()
                    ->native(false),
                    
                Forms\Components\Textarea::make('comments')
                    ->label('Comentarios / Notas')
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('status')
            ->columns([
                Tables\Columns\TextColumn::make('jobApplication.applicant_name')
                    ->label('Candidato')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('interviewer.name')
                    ->label('Entrevistador')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('survey.title')
                    ->label('Tipo de Entrevista')
                    ->sortable(),

                Tables\Columns\TextColumn::make('scheduled_at')
                    ->label('Fecha Agendada')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'scheduled' => 'warning',
                        'completed' => 'success',
                        'cancelled' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'scheduled' => 'Agendada',
                        'completed' => 'Realizada',
                        'cancelled' => 'Cancelada',
                        default => $state,
                    }),
                
                Tables\Columns\TextColumn::make('score')
                    ->label('Puntaje')
                    ->placeholder('-'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Estado')
                    ->options([
                        'scheduled' => 'Agendada',
                        'completed' => 'Realizada',
                        'cancelled' => 'Cancelada',
                    ]),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Agendar Entrevista')
                    ->successNotificationTitle('Entrevista Agendada')
                    ->after(function ($record) {
                        // Asegurar que el entrevistador tenga acceso a la encuesta
                        $survey = Survey::find($record->survey_id);
                        if ($survey && ! $survey->users()->where('users.id', $record->interviewer_id)->exists()) {
                            $survey->users()->attach($record->interviewer_id);
                        }
                    }),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
