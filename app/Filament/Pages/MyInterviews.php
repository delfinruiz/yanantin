<?php

namespace App\Filament\Pages;

use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Pages\Page;
use Filament\Tables\Table;
use Filament\Tables;
use App\Models\JobInterview;
use Illuminate\Support\Facades\Auth;
use Filament\Actions\Action;
use BackedEnum;
use Filament\Support\Enums\Width;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;

class MyInterviews extends Page implements HasTable, HasActions, HasSchemas
{
    use InteractsWithTable;
    use InteractsWithActions;
    use InteractsWithSchemas;
    use HasPageShield;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected string $view = 'filament.pages.my-interviews';

    protected static ?string $title = 'Mis Entrevistas';

    public static function getNavigationBadge(): ?string
    {
        if (! Auth::check()) {
            return null;
        }
        
        $count = JobInterview::where('interviewer_id', Auth::id())
            ->where('status', 'scheduled')
            ->count();
            
        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Gestión Laboral';
    }

    public function getMaxContentWidth(): Width
    {
        return Width::Full;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                JobInterview::query()
                    ->where('interviewer_id', Auth::id())
                    ->with(['jobApplication.jobOffer', 'survey']) // Optimización
                    ->orderBy('scheduled_at', 'asc')
            )
            ->columns([
                Tables\Columns\TextColumn::make('scheduled_at')
                    ->label('Fecha')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('jobApplication.applicant_name')
                    ->label('Candidato')
                    ->searchable(),
                
                Tables\Columns\TextColumn::make('jobApplication.jobOffer.title')
                    ->label('Oferta Laboral')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('survey.title')
                    ->label('Tipo de Entrevista'),

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
                        'scheduled' => 'Pendiente',
                        'completed' => 'Realizada',
                        'cancelled' => 'Cancelada',
                        default => $state,
                    }),
            ])
            ->recordActions([
                Action::make('start_interview')
                    ->label('Realizar Entrevista')
                    ->icon('heroicon-o-play')
                    ->color('primary')
                    ->url(fn (JobInterview $record) => \App\Filament\Resources\Surveys\SurveyResource\Pages\RespondSurvey::getUrl(['record' => $record->survey_id, 'interview_id' => $record->id]))
                    ->openUrlInNewTab()
                    ->visible(fn (JobInterview $record) => $record->status === 'scheduled'),
            ]);
    }
}
