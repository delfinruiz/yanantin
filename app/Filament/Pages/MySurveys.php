<?php

namespace App\Filament\Pages;

use App\Models\Survey;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Concerns\InteractsWithTable;
use Illuminate\Support\Facades\Auth;
use Filament\Support\Enums\Width;

use Filament\Support\Icons\Heroicon;

class MySurveys extends Page implements HasTable
{
    use InteractsWithTable;

    protected string $view = 'filament.pages.my-surveys';

    protected static bool $shouldRegisterNavigation = true;

    protected static BackedEnum|string|null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;

    public function getTitle(): string
    {
        return 'Mis encuestas';
    }

    public static function getNavigationLabel(): string
    {
        return __('navigation.labels.my_surveys');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('navigation.groups.my_apps');
    }

    public static function getNavigationSort(): ?int
    {
        return 1;
    }

    public static function getNavigationBadge(): ?string
    {
        $userId = \Illuminate\Support\Facades\Auth::id();
        if (! $userId) return null;
        $count = Survey::query()
            ->where('active', true)
            ->where(function ($q) {
                $q->whereNull('deadline')
                  ->orWhere('deadline', '>=', now());
            })
            ->accessibleToUser($userId)
            ->pendingForUser($userId)
            ->count();
        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }

    public function getMaxContentWidth(): Width
    {
        return Width::Full;
    }

    public function table(Table $table): Table
    {
        $userId = Auth::id();

        return $table
            ->query(
                Survey::query()
                    ->where('active', true)
                    ->where(function ($q) {
                        $q->whereNull('deadline')
                          ->orWhere('deadline', '>=', now());
                    })
                    ->accessibleToUser($userId)
                    ->pendingForUser($userId)
            )
            ->columns([
                Tables\Columns\TextColumn::make('title')->label('Título')->searchable(),
                Tables\Columns\TextColumn::make('deadline')->dateTime()->label('Fecha límite'),
            ])
            ->emptyStateHeading('No hay encuestas por responder')
            ->recordActions([
                \Filament\Actions\Action::make('respond')
                    ->label('Responder')
                    ->url(fn (Survey $record) => route('surveys.respond.show', $record))
                    ->openUrlInNewTab(),
            ]);
    }
}
