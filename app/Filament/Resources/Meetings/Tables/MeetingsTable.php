<?php

namespace App\Filament\Resources\Meetings\Tables;

use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Jubaer\Zoom\Facades\Zoom;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use App\Services\ZoomService;
use App\Mail\MeetingCanceled;
use Illuminate\Support\Facades\Mail;

class MeetingsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->poll('10s')
            ->recordUrl(null)
            ->columns([
                TextColumn::make('zoom_id')
                    ->label('Zoom ID')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('topic')
                    ->label(__('meetings.topic'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('start_time')
                    ->label(__('meetings.start_time'))
                    ->state(function ($record) {
                        if ($record->type == 1 || empty($record->start_time)) {
                            return __('meetings.instant');
                        }
                        return $record->start_time?->format('M d, Y H:i');
                    })
                    ->sortable(),
                TextColumn::make('duration')
                    ->label(__('meetings.duration'))
                    ->suffix(' min')
                    ->sortable(),
                TextColumn::make('status')
                    ->label(__('meetings.status'))
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => __('meetings.statuses.' . $state) ?? $state)
                    ->color(fn (string $state): string => match ($state) {
                        'scheduled' => 'info',
                        'active' => 'success',
                        'finished' => 'gray',
                        'canceled' => 'danger',
                        default => 'warning',
                    }),
                TextColumn::make('host.name')
                    ->label(__('meetings.host'))
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ActionGroup::make([
                    EditAction::make()
                        ->visible(fn ($record) => Auth::id() === $record->host_id),
                    Action::make('start')
                        ->label(__('meetings.actions.start'))
                        ->icon('heroicon-o-play')
                        ->url(fn ($record) => $record->start_url)
                        ->openUrlInNewTab()
                        ->visible(fn ($record) => $record->start_url && Auth::id() === $record->host_id),
                    Action::make('join')
                        ->label(__('meetings.actions.join'))
                        ->icon('heroicon-o-video-camera')
                        ->url(fn ($record) => route('meetings.join', ['meeting' => $record]))
                        ->openUrlInNewTab()
                        ->visible(fn ($record) => $record->join_url),
                    Action::make('cancel')
                        ->label(__('meetings.actions.cancel'))
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(fn ($record) => $record->update(['status' => 'canceled']))
                        ->visible(fn ($record) => in_array($record->status, ['scheduled', 'active']) && Auth::id() === $record->host_id),
                    Action::make('finish')
                        ->label(__('meetings.actions.finish'))
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(fn ($record) => $record->update(['status' => 'finished']))
                        ->visible(fn ($record) => $record->status === 'active' && Auth::id() === $record->host_id),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->before(function (Collection $records) {
                            foreach ($records as $record) {
                                if ($record->zoom_id) {
                                     try {
                                        if ($record->host && $record->host->email) {
                                            Mail::to($record->host->email)->send(new MeetingCanceled($record));
                                        }

                                        (new ZoomService())->deleteMeeting($record->zoom_id, ['schedule_for_reminder' => 'false']);
                                     } catch (\Exception $e) {}
                                }
                            }
                        }),
                ]),
            ]);
    }
}
