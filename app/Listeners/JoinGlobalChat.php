<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Log;
use Wirechat\Wirechat\Enums\ParticipantRole;
use Wirechat\Wirechat\Models\Group;
use Wirechat\Wirechat\Models\Participant;
use Wirechat\Wirechat\Enums\GroupType;
use App\Models\User as AppUser;
use Illuminate\Support\Facades\Storage;

class JoinGlobalChat
{
    public function handle(Login $event): void
    {
        try {
            /** @var \App\Models\User $user */
            $user = $event->user;

            Log::info("JoinGlobalChat: Verificando acceso para usuario ID {$user->id}");

            if (!$user instanceof \App\Models\User) {
                Log::warning("JoinGlobalChat: El usuario no es instancia de App\Models\User. Es: " . get_class($user));
                return;
            }

            if (!method_exists($user, 'createGroup')) {
                Log::warning("JoinGlobalChat: El usuario ID {$user->id} no tiene el trait InteractsWithWirechat.");
                return;
            }
            
            $group = Group::where('name', 'General')->first();
            $conversation = null;
            
            if (!$group) {
                Log::info("JoinGlobalChat: Grupo 'General' no existe. Creándolo con el usuario ID {$user->id} como dueño.");
                $conversation = $user->createGroup('General', 'Chat General del Sistema');
                $user->sendMessageTo($conversation, "¡Bienvenidos al Chat General del Sistema!");
                $group = $conversation->group;
                if ($group) {
                    $group->forceFill(['type' => GroupType::PUBLIC->value])->save();
                    $group->avatar_url = Storage::url('groups/avatars/01KECPXESFJ5SEA60BCHRZ8B25.jpg');
                    $group->save();
                    if (! $group->cover) {
                        $path = 'groups/avatars/01KECPXESFJ5SEA60BCHRZ8B25.jpg';
                        $group->cover()->create([
                            'file_path' => $path,
                            'file_name' => basename($path),
                            'original_name' => basename($path),
                            'mime_type' => 'image/jpeg',
                            'url' => Storage::url($path),
                        ]);
                    }
                }
                // Agregar todos los usuarios existentes como participantes (excepto el owner)
                $allUsers = AppUser::where('id', '!=', $user->id)->get();
                foreach ($allUsers as $u) {
                    try {
                        $conversation->addParticipant($u, ParticipantRole::PARTICIPANT);
                    } catch (\Throwable $e) {
                        // omitir duplicados u otros errores
                    }
                }
            } else {
                $conversation = $group->conversation;
                if (empty($group->avatar_url)) {
                    $group->avatar_url = Storage::url('groups/avatars/01KECPXESFJ5SEA60BCHRZ8B25.jpg');
                    $group->save();
                }
                if ($group->type !== GroupType::PUBLIC) {
                    $group->forceFill(['type' => GroupType::PUBLIC->value])->save();
                }
                if (! $group->cover) {
                    $path = 'groups/avatars/01KECPXESFJ5SEA60BCHRZ8B25.jpg';
                    $group->cover()->create([
                        'file_path' => $path,
                        'file_name' => basename($path),
                        'original_name' => basename($path),
                        'mime_type' => 'image/jpeg',
                        'url' => Storage::url($path),
                    ]);
                }
            }

            if (!$conversation) {
                Log::warning("JoinGlobalChat: Conversación para 'General' no disponible. Omitiendo registro de participante.");
                return;
            }

            $this->ensureParticipant($user, $conversation, "General");

            $publicGroups = Group::where('type', \Wirechat\Wirechat\Enums\GroupType::PUBLIC->value)
                ->where('id', '!=', $group->id ?? 0)
                ->get();

            foreach ($publicGroups as $publicGroup) {
                if ($publicGroup->conversation) {
                    $this->ensureParticipant($user, $publicGroup->conversation, $publicGroup->name);
                }
            }
        } catch (\Throwable $e) {
            Log::error('JoinGlobalChat: Error durante el manejo de login: ' . $e->getMessage(), [
                'exception' => $e,
            ]);
        }
    }

    private function ensureParticipant(\App\Models\User $user, $conversation, string $groupName): void
    {
        // Consultar participante ignorando global scopes para detectar bloqueos por admin
        $participant = Participant::withoutGlobalScopes()
            ->where('conversation_id', $conversation->id)
            ->where('participantable_id', $user->id)
            ->where('participantable_type', $user->getMorphClass())
            ->first();
        if ($participant && $participant->isRemovedByAdmin()) {
            // Si está bloqueado por admin, no auto-agregar
            return;
        }
        $isParticipant = (bool) $participant;

        if (!$isParticipant) {
            Log::info("JoinGlobalChat: Usuario ID {$user->id} no es miembro del grupo '{$groupName}'. Agregando...");

            Participant::create([
                'conversation_id' => $conversation->id,
                'participantable_id' => $user->id,
                'participantable_type' => $user->getMorphClass(),
                'role' => ParticipantRole::PARTICIPANT,
            ]);

            Log::info("JoinGlobalChat: Usuario ID {$user->id} agregado exitosamente a '{$groupName}'.");
        }
    }
}
