<?php

namespace Tests\Feature;

use App\Listeners\JoinGlobalChat;
use App\Models\User;
use Illuminate\Auth\Events\Login;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use Wirechat\Wirechat\Enums\ConversationType;
use Wirechat\Wirechat\Enums\GroupType;
use Wirechat\Wirechat\Enums\ParticipantRole;
use Wirechat\Wirechat\Models\Conversation;
use Wirechat\Wirechat\Models\Group;
use Wirechat\Wirechat\Models\Participant;

class GroupModerationTest extends TestCase
{
    use DatabaseTransactions;

    public function test_block_user_removes_from_group_queries_and_prevents_auto_join(): void
    {
        $owner = User::factory()->create();
        $user = User::factory()->create();

        $conversation = new Conversation();
        $conversation->type = ConversationType::GROUP;
        $conversation->save();

        $group = new Group([
            'conversation_id' => $conversation->id,
            'name' => 'Public Moderation',
        ]);
        $group->forceFill(['type' => GroupType::PUBLIC->value])->save();

        Participant::create([
            'conversation_id' => $conversation->id,
            'participantable_id' => $owner->id,
            'participantable_type' => $owner->getMorphClass(),
            'role' => ParticipantRole::OWNER,
        ]);

        // Add user as participant
        $conversation->addParticipant($user, ParticipantRole::PARTICIPANT);

        // Block user by admin (owner)
        $participant = $conversation->participant($user, withoutGlobalScopes: true);
        $participant->removeByAdmin($owner);

        // Participant queries without scopes still find record
        $this->assertNotNull(
            Participant::withoutGlobalScopes()->whereParticipantable($user)->where('conversation_id', $conversation->id)->first()
        );
        // Normal queries should not include blocked participant
        $this->assertFalse(
            $conversation->participants()->whereParticipantable($user)->exists()
        );

        // Simulate login auto-join; listener should NOT re-add
        $listener = new JoinGlobalChat();
        $listener->handle(new Login('web', $user, false));

        $this->assertFalse(
            $conversation->participants()->whereParticipantable($user)->exists()
        );
    }

    public function test_unblock_user_can_rejoin_with_undo(): void
    {
        $owner = User::factory()->create();
        $user = User::factory()->create();

        $conversation = new Conversation();
        $conversation->type = ConversationType::GROUP;
        $conversation->save();

        $group = new Group([
            'conversation_id' => $conversation->id,
            'name' => 'Public Moderation',
        ]);
        $group->forceFill(['type' => GroupType::PUBLIC->value])->save();

        Participant::create([
            'conversation_id' => $conversation->id,
            'participantable_id' => $owner->id,
            'participantable_type' => $owner->getMorphClass(),
            'role' => ParticipantRole::OWNER,
        ]);

        $conversation->addParticipant($user, ParticipantRole::PARTICIPANT);
        $participant = $conversation->participant($user, withoutGlobalScopes: true);
        $participant->removeByAdmin($owner);

        // Unblock via addParticipant with undo
        $conversation->addParticipant($user, ParticipantRole::PARTICIPANT, undoAdminRemovalAction: true);
        $this->assertTrue(
            $conversation->participants()->whereParticipantable($user)->exists()
        );
    }
}

