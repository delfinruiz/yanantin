<?php

namespace App\Filament\Resources\Meetings\RelationManagers;

use Filament\Actions\AssociateAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\DissociateAction;
use Filament\Actions\DissociateBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ParticipantsRelationManager extends RelationManager
{
    protected static string $relationship = 'participants';

    public static function getModelLabel(): string
    {
        return __('meetings.participants.participant');
    }

    public static function getPluralModelLabel(): string
    {
        return __('meetings.participants.title');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('user_id')
                    ->label(__('meetings.participants.participant'))
                    ->relationship('user', 'name')
                    ->required()
                    ->searchable()
                    ->preload()
                    ->unique(ignoreRecord: true, modifyRuleUsing: function ($rule, $get) {
                        // Optional: ensure user is not already added to this meeting
                        return $rule->where('meeting_id', $this->getOwnerRecord()->id);
                    }),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('user.name')
            ->columns([
                TextColumn::make('user.name')
                    ->label(__('meetings.participants.participant'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('user.email')
                    ->label(__('meetings.participants.email'))
                    ->searchable(),
                TextColumn::make('meeting.join_url')
                    ->label(__('meetings.participants.join_url'))
                    ->icon('heroicon-o-link')
                    ->copyable()
                    ->limit(20)
                    ->state(fn ($record) => $record->meeting->join_url),
                TextColumn::make('status')
                    ->label(__('meetings.participants.status'))
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => __('meetings.participants.statuses.' . $state))
                    ->color(fn (string $state): string => match ($state) {
                        'accepted' => 'success',
                        'invited' => 'gray',
                        'rejected' => 'danger',
                        default => 'warning',
                    }),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                CreateAction::make()
                    ->label(__('meetings.participants.add_registrant'))
                    ->after(function ($record) {
                        /** @var \App\Models\MeetingParticipant $record */
                        $meeting = $record->meeting;
                        $user = $record->user;
                        
                        if ($user) {
                            Notification::make()
                                ->title(__('meetings.notifications.added_as_participant_title'))
                                ->body(__('meetings.notifications.added_as_participant_body', ['topic' => $meeting->topic]))
                                ->success()
                                ->sendToDatabase($user);
                        }
                    }),
            ])
            ->recordActions([
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
