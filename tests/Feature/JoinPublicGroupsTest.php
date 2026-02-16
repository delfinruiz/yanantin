<?php

namespace Tests\Feature;

use App\Models\User;
use App\Listeners\JoinGlobalChat;
use Illuminate\Auth\Events\Login;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use Wirechat\Wirechat\Models\Conversation;
use Wirechat\Wirechat\Models\Participant;
use Wirechat\Wirechat\Enums\ParticipantRole;
use Wirechat\Wirechat\Enums\ConversationType;
use Wirechat\Wirechat\Enums\GroupType;
use Wirechat\Wirechat\Models\Group;

class JoinPublicGroupsTest extends TestCase
{
    use DatabaseTransactions;

    public function test_user_joins_public_groups_on_login()
    {
        // 1. Create Users
        $owner = User::factory()->create();
        $user = User::factory()->create();

        // 2. Create Public Group WITHOUT the user initially
        $conversation = new Conversation();
        $conversation->type = ConversationType::GROUP;
        $conversation->save();

        $group = new Group([
            'conversation_id' => $conversation->id,
            'name' => 'Public Group Auto Join',
        ]);
        $group->forceFill([
            'type' => GroupType::PUBLIC->value,
        ]);
        $group->save();

        // Only add owner initially
        Participant::create([
            'conversation_id' => $conversation->id,
            'participantable_id' => $owner->id,
            'participantable_type' => $owner->getMorphClass(),
            'role' => ParticipantRole::OWNER,
        ]);

        // Verify User is NOT in participants yet
        $this->assertDatabaseMissing('wirechat_participants', [
            'conversation_id' => $conversation->id,
            'participantable_id' => $user->id,
        ]);

        // 3. Trigger Login Event
        $event = new Login('web', $user, false);
        $listener = new JoinGlobalChat();
        $listener->handle($event);

        // 4. Verify User IS NOW in participants
        $this->assertDatabaseHas('wirechat_participants', [
            'conversation_id' => $conversation->id,
            'participantable_id' => $user->id,
            'role' => ParticipantRole::PARTICIPANT->value ?? 'participant', // Adjust based on enum value
        ]);
    }
}
