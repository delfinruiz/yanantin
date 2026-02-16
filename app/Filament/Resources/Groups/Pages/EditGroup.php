<?php

namespace App\Filament\Resources\Groups\Pages;

use App\Filament\Resources\Groups\GroupResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Wirechat\Wirechat\Models\Participant;
use Wirechat\Wirechat\Enums\ParticipantRole;
use Wirechat\Wirechat\Enums\GroupType;
use App\Models\User;

use Wirechat\Wirechat\Models\Attachment;
use Illuminate\Support\Facades\Storage;

class EditGroup extends EditRecord
{
    protected static string $resource = GroupResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $group = $this->getRecord();
        $conversation = $group->conversation;
        if ($conversation) {
             $participantIds = $conversation->participants()
                ->where('participantable_type', app(User::class)->getMorphClass())
                ->pluck('participantable_id')
                ->toArray();
             
             $data['participants'] = $participantIds;
        }
        
        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $record->update([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
        ]);
        
        $record->forceFill([
            'type' => $data['type'],
            'avatar_url' => $data['avatar_url'] ?? null,
        ])->save();

        // Handle Avatar Attachment Update
        if ($record->wasChanged('avatar_url')) {
            // Delete old cover
            $record->cover()->delete();

            if (!empty($record->avatar_url)) {
                $path = $record->avatar_url;
                $mimeType = null;
                try {
                    $fullPath = Storage::disk('public')->path($path);
                    if (file_exists($fullPath)) {
                        $mimeType = mime_content_type($fullPath);
                    }
                } catch (\Throwable $e) {}

                $url = '/storage/' . $path;
                try {
                    /** @var \Illuminate\Filesystem\FilesystemAdapter $disk */
                    $disk = Storage::disk('public');
                    $url = $disk->url($path);
                } catch (\Throwable $e) {}

                Attachment::create([
                    'attachable_id' => $record->id,
                    'attachable_type' => $record->getMorphClass(),
                    'file_path' => $path,
                    'file_name' => basename($path),
                    'mime_type' => $mimeType,
                    'original_name' => basename($path),
                    'url' => $url,
                ]);
            }
        }
        
        $conversation = $record->conversation;
        if ($conversation && isset($data['participants'])) {
             $existing = $conversation->participants()
                 ->where('participantable_type', app(User::class)->getMorphClass())
                 ->get();

             $existingIds = $existing->pluck('participantable_id')->toArray();
             $newIds = $data['participants'];

             $toAdd = array_diff($newIds, $existingIds);
             foreach($toAdd as $id) {
                 Participant::create([
                    'conversation_id' => $conversation->id,
                    'participantable_id' => $id,
                    'participantable_type' => app(User::class)->getMorphClass(),
                    'role' => ParticipantRole::PARTICIPANT,
                ]);
             }
             
             $toRemove = array_diff($existingIds, $newIds);
             
             if (!empty($toRemove)) {
                 $conversation->participants()
                     ->where('participantable_type', app(User::class)->getMorphClass())
                     ->whereIn('participantable_id', $toRemove)
                     ->delete();
             }
        }

        // Handle Public Group conversion: Ensure all users are added
        if ($record->type === GroupType::PUBLIC->value || $record->type === 'public') {
             $allUsers = User::all();
             $existingIds = $conversation->participants()
                 ->where('participantable_type', app(User::class)->getMorphClass())
                 ->pluck('participantable_id')
                 ->toArray();
             
             foreach ($allUsers as $user) {
                 if (!in_array($user->id, $existingIds)) {
                     Participant::create([
                        'conversation_id' => $conversation->id,
                        'participantable_id' => $user->id,
                        'participantable_type' => $user->getMorphClass(),
                        'role' => ParticipantRole::PARTICIPANT,
                    ]);
                 }
             }
        }
        
        return $record;
    }
}
