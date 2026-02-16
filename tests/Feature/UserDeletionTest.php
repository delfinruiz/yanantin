<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\EmailAccount;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use Wirechat\Wirechat\Models\Conversation;
use Wirechat\Wirechat\Models\Participant;
use Wirechat\Wirechat\Enums\ParticipantRole;
use Wirechat\Wirechat\Enums\ConversationType;

class UserDeletionTest extends TestCase
{
    use DatabaseTransactions;

    public function test_user_deletion_unassigns_email_and_removes_from_groups()
    {
        // 1. Setup User and EmailAccount
        $user = User::factory()->create();
        $emailAccount = EmailAccount::create([
            'email' => 'test_' . uniqid() . '@example.com',
            'password' => 'password',
            'domain' => 'example.com',
            'quota' => 100,
            'used' => 0,
            'user_id' => $user->id,
            'assigned_at' => now(),
        ]);

        // 2. Setup Wirechat Group and Participant
        $conversation = new Conversation();
        $conversation->type = ConversationType::GROUP;
        $conversation->save();

        Participant::create([
            'conversation_id' => $conversation->id,
            'participantable_id' => $user->id,
            'participantable_type' => $user->getMorphClass(),
            'role' => ParticipantRole::PARTICIPANT,
        ]);

        // Verify initial state
        $this->assertDatabaseHas('email_accounts', [
            'id' => $emailAccount->id,
            'user_id' => $user->id,
        ]);
        $this->assertDatabaseHas('wirechat_participants', [
            'conversation_id' => $conversation->id,
            'participantable_id' => $user->id,
        ]);

        // 3. Delete User
        $user->delete();

        // 4. Verify EmailAccount is unassigned
        $this->assertDatabaseHas('email_accounts', [
            'id' => $emailAccount->id,
            'user_id' => null,
            'assigned_at' => null,
        ]);

        // 5. Verify User is removed from participants
        $this->assertDatabaseMissing('wirechat_participants', [
            'conversation_id' => $conversation->id,
            'participantable_id' => $user->id,
        ]);
    }
}
