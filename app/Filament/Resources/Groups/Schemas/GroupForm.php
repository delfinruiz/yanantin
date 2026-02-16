<?php

namespace App\Filament\Resources\Groups\Schemas;

use App\Models\User;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Wirechat\Wirechat\Enums\GroupType;

class GroupForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label(__('groups.fields.name'))
                    ->required()
                    ->maxLength(255)
                    ->unique(\Wirechat\Wirechat\Models\Group::class, 'name', ignoreRecord: true),
                Select::make('type')
                    ->label(__('groups.fields.type'))
                    ->options([
                        GroupType::PUBLIC->value => __('groups.fields.public'),
                        GroupType::PRIVATE->value => __('groups.fields.private'),
                    ])
                    ->default(GroupType::PRIVATE->value)
                    ->required()
                    ->live(),
                Textarea::make('description')
                    ->label(__('groups.fields.description'))
                    ->required()
                    ->rows(3)
                    ->maxLength(65535),
                FileUpload::make('avatar_url')
                    ->label(__('groups.fields.avatar'))
                    ->required()
                    ->image()
                    ->directory('groups/avatars')
                    ->visibility('public'),
                Select::make('participants')
                    ->label(__('groups.fields.members'))
                    //required solo cuando el tipo es private
                    ->required(fn (Get $get) => $get('type') === GroupType::PRIVATE->value)
                    ->multiple()
                    ->searchable()
                    ->preload()
                    ->getSearchResultsUsing(fn (string $search) => User::where('name', 'like', "%{$search}%")->limit(50)->pluck('name', 'id'))
                    ->getOptionLabelsUsing(fn (array $values): array => User::whereIn('id', $values)->pluck('name', 'id')->toArray())
                    ->formatStateUsing(function ($record) {
                        if (! $record) {
                            return [];
                        }

                        // Ensure we have a conversation
                        $conversation = $record->conversation;
                        if (! $conversation) {
                            return [];
                        }

                        // Get participants that are NOT owners (assuming we only want to manage regular members)
                        // Also filtering for User model to match the options
                        return $conversation->participants()
                            ->where('participantable_type', (new User)->getMorphClass())
                            ->where('role', '!=', \Wirechat\Wirechat\Enums\ParticipantRole::OWNER)
                            ->pluck('participantable_id')
                            ->toArray();
                    })
                    ->visible(fn (Get $get) => $get('type') === GroupType::PRIVATE->value),
            ]);
    }
}
