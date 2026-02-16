<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use Wirechat\Wirechat\Models\Conversation;
use Wirechat\Wirechat\Models\Participant;
use Wirechat\Wirechat\Enums\ParticipantRole;
use Wirechat\Wirechat\Enums\ConversationType;
use Wirechat\Wirechat\Enums\GroupType;
use Wirechat\Wirechat\Models\Group;

class UserEmailUpdateTest extends TestCase
{
    use DatabaseTransactions;

    public function test_user_stays_in_public_group_after_email_change()
    {
        // 1. Create Users
        $owner = User::factory()->create();
        $user = User::factory()->create();

        // 2. Create Public Group (simulating logic from CreateGroup page or similar)
        // Usually, creating a public group adds all existing users.
        
        $conversation = new Conversation();
        $conversation->type = ConversationType::GROUP;
        $conversation->save();

        $group = new Group([
            'conversation_id' => $conversation->id,
            'name' => 'Public Group Test',
            'type' => GroupType::PUBLIC->value, // Ensure it's public
        ]);
        $group->save();

        // Add Owner
        Participant::create([
            'conversation_id' => $conversation->id,
            'participantable_id' => $owner->id,
            'participantable_type' => $owner->getMorphClass(),
            'role' => ParticipantRole::OWNER,
        ]);

        // Add User (as logic dictates for public groups)
        Participant::create([
            'conversation_id' => $conversation->id,
            'participantable_id' => $user->id,
            'participantable_type' => $user->getMorphClass(),
            'role' => ParticipantRole::PARTICIPANT,
        ]);

        // Verify initial state
        $this->assertDatabaseHas('wirechat_participants', [
            'conversation_id' => $conversation->id,
            'participantable_id' => $user->id,
        ]);

        // 3. Update User Email
        $newEmail = 'new_email_' . uniqid() . '@example.com';
        $user->email = $newEmail;
        $user->save();

        // 4. Verify User is STILL in participants
        $this->assertDatabaseHas('wirechat_participants', [
            'conversation_id' => $conversation->id,
            'participantable_id' => $user->id,
        ]);
    }
}
