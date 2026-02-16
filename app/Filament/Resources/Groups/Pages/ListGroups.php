<?php

namespace App\Filament\Resources\Groups\Pages;

use App\Filament\Resources\Groups\GroupResource;
use App\Models\User;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\Width;
use Filament\Facades\Filament;

class ListGroups extends ListRecords
{
    protected static string $resource = GroupResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->using(function (array $data): \Illuminate\Database\Eloquent\Model {
                    $conversation = new \Wirechat\Wirechat\Models\Conversation();
                    $conversation->type = \Wirechat\Wirechat\Enums\ConversationType::GROUP;
                    $conversation->save();

                    $data['conversation_id'] = $conversation->getKey();

                    $createData = $data;
                    unset($createData['participants']);
                    
                    // Set default permissions (Only admins can add members and edit info)
                    $createData['allow_members_to_add_others'] = false;
                    $createData['allow_members_to_edit_group_info'] = false;
                    // Default for sending messages usually true, but explicit is good
                    $createData['allow_members_to_send_messages'] = true; 

                    $model = static::getResource()::getModel();
                    $record = new $model;
                    $record->forceFill($createData);
                    $record->save();

                    return $record;
                })
                ->after(function (\Wirechat\Wirechat\Models\Group $record, array $data): void {
                    $conversation = $record->conversation;
                    $authUser = Filament::auth()->user();
                    $ownerUser = $authUser ? User::find($authUser->getAuthIdentifier()) : null;

                    if ($ownerUser) {
                        $conversation->addParticipant($ownerUser, \Wirechat\Wirechat\Enums\ParticipantRole::OWNER);
                    }

                    // Add Participants logic
                    if (($data['type'] ?? null) === \Wirechat\Wirechat\Enums\GroupType::PUBLIC->value) {
                         // Public Group: Add ALL users except owner
                         $allUsers = User::where('id', '!=', $ownerUser?->getKey())->get();
                         foreach ($allUsers as $participant) {
                             $conversation->addParticipant($participant, \Wirechat\Wirechat\Enums\ParticipantRole::PARTICIPANT);
                         }
                    } else {
                        // Private Group: Add Selected Participants
                        $participantIds = $data['participants'] ?? [];
                        if (! empty($participantIds)) {
                            $users = User::whereIn('id', $participantIds)->get();

                            foreach ($users as $user) {
                                if (! $ownerUser || $user->getKey() !== $ownerUser->getKey()) {
                                    $conversation->addParticipant($user, \Wirechat\Wirechat\Enums\ParticipantRole::PARTICIPANT);
                                }
                            }
                        }
                    }

                    // Create initial system message
                    if ($ownerUser) {
                        \Wirechat\Wirechat\Models\Message::create([
                            'conversation_id' => $conversation->id,
                            'sendable_id' => $ownerUser->getKey(),
                            'sendable_type' => $ownerUser->getMorphClass(),
                            'body' => 'Group created',
                        ]);
                    }

                    // Handle Avatar Attachment
                    if (! empty($data['avatar_url'])) {
                        $path = $data['avatar_url'];
                        $record->cover()->create([
                            'file_path' => $path,
                            'file_name' => basename($path),
                            'original_name' => basename($path),
                            'url' => \Illuminate\Support\Facades\Storage::url($path),
                            'mime_type' => \Illuminate\Support\Facades\Storage::mimeType($path),
                        ]);
                    }
                }),
        ];
    }

        public function getMaxContentWidth(): Width
    {
        return Width::Full;
    }
}
