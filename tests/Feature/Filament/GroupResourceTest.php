<?php

namespace Tests\Feature\Filament;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Wirechat\Wirechat\Models\Group;
use Wirechat\Wirechat\Models\Conversation;
use Wirechat\Wirechat\Models\Participant;
use Wirechat\Wirechat\Enums\ConversationType;
use Wirechat\Wirechat\Enums\GroupType;
use Wirechat\Wirechat\Enums\ParticipantRole;
use Filament\Facades\Filament;
use Spatie\Permission\Models\Permission;
use Livewire\Livewire;

class GroupResourceTest extends TestCase
{
    // Note: This test requires the sqlite pdo driver to be enabled in your php.ini
    // or configuration to use a testing database.
    // use RefreshDatabase; 

    public function test_admin_can_render_group_resource_page()
    {
        $user = User::factory()->create();
        
        $permission = Permission::firstOrCreate(['name' => 'view_any_group', 'guard_name' => 'web']);
        $user->givePermissionTo($permission);
        
        $this->actingAs($user);

        $response = $this->get(Filament::getPanel('admin')->getUrl() . '/groups');
        
        $response->assertStatus(200);
    }

    public function test_admin_can_create_group_livewire()
    {
        $user = User::factory()->create();
        $permissions = ['view_any_group', 'create_group', 'view_group'];
        foreach($permissions as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
            $user->givePermissionTo($perm);
        }
        
        $this->actingAs($user);
        
        Livewire::test(\App\Filament\Resources\Groups\Pages\CreateGroup::class)
            ->fillForm([
                'name' => 'Test Group Unit ' . uniqid(),
                'type' => GroupType::PUBLIC->value,
                'description' => 'Test Description',
            ])
            ->call('create')
            ->assertHasNoErrors();
            
        $group = Group::latest()->first();
        
        $this->assertNotNull($group);
        $this->assertEquals('Test Description', $group->description);
        
        // Verify Conversation exists
        $this->assertNotNull($group->conversation);
        $this->assertEquals(ConversationType::GROUP, $group->conversation->type);
        
        // Verify Participant (Owner)
        $this->assertDatabaseHas((new Participant)->getTable(), [
            'conversation_id' => $group->conversation_id,
            'participantable_id' => $user->id,
            'role' => ParticipantRole::OWNER->value,
        ]);
        
        // Clean up
        $group->conversation->delete(); // Should delete group via cascade if set, or just delete conversation
        $group->delete();
        $user->delete();
    }
}
