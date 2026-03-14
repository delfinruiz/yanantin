<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Wirechat\Wirechat\Enums\GroupType;
use Wirechat\Wirechat\Models\Group;
use Wirechat\Wirechat\Models\Participant;

class WirechatDetachPublicUsers extends Command
{
    protected $signature = 'wirechat:detach-public-users {--group=General : Nombre del grupo base (por defecto General)}';

    protected $description = 'Elimina usuarios con rol public de los grupos públicos de Wirechat (por defecto desde el grupo General y otros públicos).';

    public function handle(): int
    {
        $publicUserIds = User::query()
            ->where('is_internal', false)
            ->orWhereHas('roles', fn ($q) => $q->where('name', 'public'))
            ->pluck('id')
            ->unique()
            ->values();

        if ($publicUserIds->isEmpty()) {
            $this->info('No hay usuarios public para remover.');
            return self::SUCCESS;
        }

        $publicGroups = Group::query()
            ->where('type', GroupType::PUBLIC->value)
            ->get()
            ->filter(fn (Group $g) => (bool) $g->conversation)
            ->values();

        $conversationIds = $publicGroups
            ->map(fn (Group $g) => $g->conversation->id)
            ->unique()
            ->values();

        if ($conversationIds->isEmpty()) {
            $this->info('No hay conversaciones de grupos públicos para limpiar.');
            return self::SUCCESS;
        }

        $userMorph = (new User())->getMorphClass();

        $deleted = Participant::withoutGlobalScopes()
            ->whereIn('conversation_id', $conversationIds)
            ->where('participantable_type', $userMorph)
            ->whereIn('participantable_id', $publicUserIds)
            ->delete();

        $this->info("Participaciones eliminadas: {$deleted}");

        return self::SUCCESS;
    }
}

