<?php

namespace App\Filament\Resources\Groups\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Notifications\Notification;
use Spatie\Permission\Models\Role;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Wirechat\Wirechat\Enums\GroupType;

use Illuminate\Database\Eloquent\Model;

class GroupsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->recordUrl(null)
            ->columns([
                ImageColumn::make('avatar_url')
                    ->label(__('groups.columns.avatar'))
                    ->circular(),
                TextColumn::make('name')
                    ->label(__('groups.columns.name'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('type')
                    ->label(__('groups.columns.type'))
                    ->badge()
                    ->formatStateUsing(fn ($state) => match ($state) {
                        GroupType::PRIVATE => __('groups.fields.private'),
                        GroupType::PUBLIC => __('groups.fields.public'),
                        'private' => __('groups.fields.private'),
                        'public' => __('groups.fields.public'),
                        default => $state,
                    })
                    ->color(fn ($state) => match ($state) {
                        GroupType::PRIVATE => 'danger',
                        GroupType::PUBLIC => 'success',
                        default => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('description')
                    ->label(__('groups.columns.description'))
                    ->limit(50)
                    ->searchable(),
                TextColumn::make('members_count')
                    ->label(__('groups.columns.members'))
                    ->state(fn ($record) => $record->conversation?->participants()->count() ?? 0),
                TextColumn::make('blocked_count')
                    ->label(__('groups.columns.blocked'))
                    ->badge()
                    ->color(fn ($state) => $state > 0 ? 'danger' : 'gray')
                    ->state(fn ($record) => $record->conversation
                        ? $record->conversation->participants()->withoutGlobalScopes()->get()->filter(fn ($p) => $p->isRemovedByAdmin())->count()
                        : 0),
                TextColumn::make('created_at')
                    ->label(__('groups.columns.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label(__('groups.filters.type'))
                    ->options([
                        GroupType::PUBLIC->value => __('groups.fields.public'),
                        GroupType::PRIVATE->value => __('groups.fields.private'),
                    ]),
            ])
            ->recordActions([
                ActionGroup::make([
                    EditAction::make()
                        ->hidden(fn ($record) => $record->name === 'General')
                        ->using(function (Model $record, array $data): Model {
                            $updateData = $data;
                            unset($updateData['participants']);

                            $record->forceFill($updateData);
                            $record->save();

                            if (! empty($data['avatar_url'])) {
                                $path = $data['avatar_url'];
                                $record->cover()->updateOrCreate(
                                    [],
                                    [
                                        'file_path' => $path,
                                        'file_name' => basename($path),
                                        'original_name' => basename($path),
                                        'url' => \Illuminate\Support\Facades\Storage::url($path),
                                        'mime_type' => \Illuminate\Support\Facades\Storage::mimeType($path),
                                    ]
                                );
                            }

                            if (isset($data['participants'])) {
                                $conversation = $record->conversation;
                                if ($conversation) {
                                    $existingParticipants = $conversation->participants()
                                        ->where('participantable_type', (new \App\Models\User)->getMorphClass())
                                        ->where('role', '!=', \Wirechat\Wirechat\Enums\ParticipantRole::OWNER)
                                        ->pluck('participantable_id')
                                        ->toArray();
                                    
                                    $newParticipants = $data['participants'] ?? [];

                                    $toAdd = array_diff($newParticipants, $existingParticipants);
                                    $toRemove = array_diff($existingParticipants, $newParticipants);

                                    if (!empty($toAdd)) {
                                        $usersToAdd = \App\Models\User::whereIn('id', $toAdd)->get();
                                        foreach ($usersToAdd as $user) {
                                            $conversation->addParticipant($user, \Wirechat\Wirechat\Enums\ParticipantRole::PARTICIPANT);
                                        }
                                    }

                                    if (!empty($toRemove)) {
                                        $conversation->participants()
                                            ->where('participantable_type', (new \App\Models\User)->getMorphClass())
                                            ->whereIn('participantable_id', $toRemove)
                                            ->delete();
                                    }
                                }
                            }

                            return $record;
                        }),
                    Action::make('blockUsers')
                        ->label(__('groups.actions.block_users'))
                        ->icon('heroicon-o-user-minus')
                        ->form([
                            \Filament\Forms\Components\Select::make('users')
                                ->label(__('users'))
                                ->multiple()
                                ->searchable()
                                ->options(function (Model $record) {
                                    $conversation = $record->conversation;
                                    if (! $conversation) return [];
                                    $blockedIds = $conversation->participants()->withoutGlobalScopes()->get()
                                        ->filter(fn ($p) => $p->isRemovedByAdmin())
                                        ->where('participantable_type', (new \App\Models\User)->getMorphClass())
                                        ->map(fn ($p) => $p->participantable_id)
                                        ->values()
                                        ->all();
                                    $roleNames = Role::whereIn('name', ['super_admin', 'admin'])->pluck('name')->all();
                                    $adminIds = empty($roleNames)
                                        ? []
                                        : \App\Models\User::whereHas('roles', function ($q) use ($roleNames) {
                                            $q->whereIn('name', $roleNames);
                                        })->pluck('id')->all();
                                    return \App\Models\User::whereNotIn('id', $blockedIds)
                                        ->whereNotIn('id', $adminIds)
                                        ->pluck('name', 'id');
                                })
                                ->required(),
                        ])
                        ->action(function (Model $record, array $data) {
                            $conversation = $record->conversation;
                            if (! $conversation) return;
                            $admin = \Illuminate\Support\Facades\Auth::user();
                            $users = \App\Models\User::whereIn('id', $data['users'] ?? [])->get();
                            foreach ($users as $user) {
                                $participant = $conversation->participants()->withoutGlobalScopes()
                                    ->where('participantable_id', $user->getKey())
                                    ->where('participantable_type', $user->getMorphClass())
                                    ->first();
                                if ($participant) {
                                    $participant->removeByAdmin($admin);
                                } else {
                                    $participant = $conversation->participants()->create([
                                        'participantable_id' => $user->getKey(),
                                        'participantable_type' => $user->getMorphClass(),
                                        'role' => \Wirechat\Wirechat\Enums\ParticipantRole::PARTICIPANT,
                                    ]);
                                    $participant->removeByAdmin($admin);
                                }
                            }
                            Notification::make()->title(__('groups.notifications.blocked_ok'))->success()->send();
                        }),
                    Action::make('unblockUsers')
                        ->label(__('groups.actions.unblock_users'))
                        ->icon('heroicon-o-user-plus')
                        ->form([
                            \Filament\Forms\Components\Select::make('users')
                                ->label(__('users'))
                                ->multiple()
                                ->searchable()
                                ->options(function (Model $record) {
                                    $conversation = $record->conversation;
                                    if (! $conversation) return [];
                                    $blockedIds = $conversation->participants()->withoutGlobalScopes()->get()
                                        ->filter(fn ($p) => $p->isRemovedByAdmin())
                                        ->where('participantable_type', (new \App\Models\User)->getMorphClass())
                                        ->map(fn ($p) => $p->participantable_id)
                                        ->values()
                                        ->all();
                                    return \App\Models\User::whereIn('id', $blockedIds)->pluck('name', 'id');
                                })
                                ->required(),
                        ])
                        ->action(function (Model $record, array $data) {
                            $conversation = $record->conversation;
                            if (! $conversation) return;
                            $users = \App\Models\User::whereIn('id', $data['users'] ?? [])->get();
                            foreach ($users as $user) {
                                try {
                                    $conversation->addParticipant($user, \Wirechat\Wirechat\Enums\ParticipantRole::PARTICIPANT, undoAdminRemovalAction: true);
                                } catch (\Throwable $e) {
                                }
                            }
                            Notification::make()->title(__('groups.notifications.unblocked_ok'))->success()->send();
                        }),
                    Action::make('viewBlocked')
                        ->label(__('groups.actions.view_blocked'))
                        ->icon('heroicon-o-eye')
                        ->modalHeading(__('groups.actions.view_blocked'))
                        ->modalSubmitAction(false)
                        ->modalCancelAction(false)
                        ->action(function () {})
                        ->modalContent(function (Model $record) {
                            $conversation = $record->conversation;
                            if (! $conversation) return new \Illuminate\Support\HtmlString('<div>'.__('No Content').'</div>');
                            $blocked = $conversation->participants()->withoutGlobalScopes()->get()->filter(function ($p) {
                                return $p->isRemovedByAdmin();
                            })->map(function ($p) {
                                return $p->participantable?->name ?? 'Usuario';
                            })->implode(', ');
                            $html = $blocked ? "<div>$blocked</div>" : "<div>".__('No hay usuarios bloqueados')."</div>";
                            return new \Illuminate\Support\HtmlString($html);
                        }),
                    DeleteAction::make()
                        ->hidden(fn ($record) => $record->name === 'General')
                        ->after(function ($record) {
                            $record->conversation?->delete();
                        }),
                ])->label('Acciones'),
            ]);
    }
}
