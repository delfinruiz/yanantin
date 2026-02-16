<?php

namespace App\Filament\Resources\Groups\Pages;

use App\Filament\Resources\Groups\GroupResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Wirechat\Wirechat\Enums\ConversationType;
use Wirechat\Wirechat\Enums\ParticipantRole;
use Wirechat\Wirechat\Models\Conversation;
use Wirechat\Wirechat\Models\Group;
use Wirechat\Wirechat\Models\Participant;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

use Wirechat\Wirechat\Models\Attachment;
use Wirechat\Wirechat\Models\Message;
use Wirechat\Wirechat\Enums\MessageType;
use Wirechat\Wirechat\Enums\GroupType;
use Illuminate\Support\Facades\Storage;

class CreateGroup extends CreateRecord
{
    protected static string $resource = GroupResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        // Create Conversation
        $conversation = new Conversation();
        $conversation->type = ConversationType::GROUP;
        $conversation->save();

        // Create Group
        $group = new Group([
            'conversation_id' => $conversation->id,
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
        ]);
        
        $group->forceFill([
            'type' => $data['type'],
            'avatar_url' => $data['avatar_url'] ?? null,
        ]);
        
        $group->save();

        // Create Avatar Attachment if exists
        if (!empty($data['avatar_url'])) {
            $path = $data['avatar_url'];
            if (is_array($path)) $path = reset($path);

            $mimeType = null;
            try {
                $fullPath = Storage::disk('public')->path($path);
                if (file_exists($fullPath)) {
                    $mimeType = mime_content_type($fullPath);
                }
            } catch (\Throwable $e) {
                // Ignore if file not found or mime detection fails
            }

            $url = '/storage/' . $path;
            try {
                /** @var \Illuminate\Filesystem\FilesystemAdapter $disk */
                $disk = Storage::disk('public');
                $url = $disk->url($path);
            } catch (\Throwable $e) {}

            Attachment::create([
                'attachable_id' => $group->id,
                'attachable_type' => $group->getMorphClass(),
                'file_path' => $path,
                'file_name' => basename($path),
                'mime_type' => $mimeType,
                'original_name' => basename($path),
                'url' => $url,
            ]);
        }

        // Add Admin as Owner
        Participant::create([
            'conversation_id' => $conversation->id,
            'participantable_id' => $user->id,
            'participantable_type' => $user->getMorphClass(),
            'role' => ParticipantRole::OWNER,
        ]);

        // Add Participants
        if ($data['type'] === GroupType::PUBLIC->value) {
             // Public Group: Add ALL users except owner
             $allUsers = User::where('id', '!=', $user->id)->get();
             foreach ($allUsers as $participant) {
                 Participant::create([
                    'conversation_id' => $conversation->id,
                    'participantable_id' => $participant->id,
                    'participantable_type' => $user->getMorphClass(),
                    'role' => ParticipantRole::PARTICIPANT,
                ]);
             }
        } elseif (!empty($data['participants'])) {
            // Private Group: Add Selected Participants
            foreach ($data['participants'] as $participantId) {
                if ($participantId == $user->id) continue;

                Participant::create([
                    'conversation_id' => $conversation->id,
                    'participantable_id' => $participantId,
                    'participantable_type' => $user->getMorphClass(),
                    'role' => ParticipantRole::PARTICIPANT,
                ]);
            }
        }

        // Create initial system message to ensure conversation is not blank
        Message::create([
            'conversation_id' => $conversation->id,
            'sendable_id' => $user->id,
            'sendable_type' => $user->getMorphClass(),
            'body' => 'Group created',
            'type' => MessageType::TEXT, // Assuming TEXT is a valid case
        ]);

        return $group;
    }
}
