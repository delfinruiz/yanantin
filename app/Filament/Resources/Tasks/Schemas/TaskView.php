<?php

namespace App\Filament\Resources\Tasks\Schemas;

use Filament\Schemas\Schema;
use Filament\Infolists\Components\TextEntry;
use Mokhosh\FilamentRating\Entries\RatingEntry;
use Filament\Schemas\Components\Section;
use Filament\Infolists\Components\ImageEntry;

class TaskView
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('General Information'))
                    ->columns(2)
                    ->schema([
                        TextEntry::make('id')
                            ->label(__('Identified_Task')),
                        TextEntry::make('title')
                            ->label(__('Title_Task')),
                        TextEntry::make('status.title')
                            ->label(__('Status_Task'))
                            ->badge()
                            ->formatStateUsing(fn ($state) => __($state))
                            ->color(fn (string $state): string => match ($state) {
                                'Pending' => 'danger',
                                'Completed' => 'success',
                                'In_Progress' => 'warning',
                                default => 'gray',
                            }),
                        RatingEntry::make('rating')
                            ->label(__('Rating_Task')),
                        TextEntry::make('priority')
                            ->label(__('Priority'))
                            ->badge()
                            ->formatStateUsing(fn (string $state): string => match ($state) {
                                'High' => __('priority.high'),
                                'Medium' => __('priority.medium'),
                                'Low' => __('priority.low'),
                                default => $state,
                            })
                            ->color(fn (string $state): string => match ($state) {
                                'High' => 'danger',
                                'Medium' => 'warning',
                                'Low' => 'success',
                                default => 'gray',
                            }),
                        TextEntry::make('due_date')
                            ->label(__('Due Date'))
                            ->date('d-m-Y'),
                    ]),

                Section::make(__('Assignment Details'))
                    ->columns(2)
                    ->schema([
                        TextEntry::make('creator.name')
                            ->label(__('Created_by')),
                        TextEntry::make('assignee.name')
                            ->label(__('Assigned_to')),
                        TextEntry::make('created_at')
                            ->label(__('Created_at'))
                            ->formatStateUsing(fn ($state) => $state->format('d-m-Y H:i:s')),
                        TextEntry::make('updated_at')
                            ->label(__('Updated_at'))
                            ->formatStateUsing(fn ($state) => $state->format('d-m-Y H:i:s')),
                    ]),

                Section::make(__('Content'))
                    ->columnSpanFull()
                    ->schema([
                        TextEntry::make('attachment')
                            ->label(__('Attachment'))
                            ->formatStateUsing(fn ($state) => $state ? basename($state) : '-')
                            ->url(fn ($state) => $state ? '/storage/' . $state : null)
                            ->openUrlInNewTab()
                            ->visible(fn ($state) => !empty($state)),
                        TextEntry::make('description')
                            ->label(__('Description_Task'))
                            ->html()
                            ->columnSpanFull(),
                        TextEntry::make('observation')
                            ->label(__('Observation_Task'))
                            ->html()
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
