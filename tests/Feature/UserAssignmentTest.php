<?php

namespace Tests\Feature;

use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Models\EmailAccount;
use App\Models\User;
use Spatie\Permission\Models\Permission;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;
use Tests\TestCase;

class UserAssignmentTest extends TestCase
{
    use DatabaseTransactions;

    public function test_can_assign_email_account_to_new_user()
    {
        // Create admin user
        $admin = User::factory()->create();
        
        // Assign permissions
        $permissions = ['ViewAny:User', 'Create:User'];
        foreach($permissions as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
            $admin->givePermissionTo($perm);
        }
        
        $this->actingAs($admin);

        // 1. Create an unassigned email account
        $emailAccount = EmailAccount::create([
            'email' => 'test_' . uniqid() . '@example.com',
            'password' => 'password',
            'domain' => 'example.com',
            'quota' => 100,
            'used' => 0,
        ]);

        // 2. Create user via Livewire component (CreateUser page)
        $userData = [
            'name' => 'Test User ' . uniqid(),
            'email' => 'user_' . uniqid() . '@example.com',
            'password' => 'password123',
            'email_account_id' => $emailAccount->id,
        ];

        Livewire::test(CreateUser::class)
            ->fillForm($userData)
            ->call('create')
            ->assertHasNoErrors();

        // 3. Verify user created
        $user = User::where('email', $userData['email'])->first();
        $this->assertNotNull($user);

        // 4. Verify email account assigned
        $emailAccount->refresh();
        $this->assertEquals($user->id, $emailAccount->user_id);
        $this->assertNotNull($emailAccount->assigned_at);
        
        // Clean up
        $emailAccount->delete();
        $user->delete();
    }

    public function test_cannot_assign_already_assigned_email_account()
    {
        // Create admin user
        $admin = User::factory()->create();
        
        // Assign permissions
        $permissions = ['ViewAny:User', 'Create:User'];
        foreach($permissions as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
            $admin->givePermissionTo($perm);
        }

        $this->actingAs($admin);

        // 1. Create an assigned email account
        $user1 = User::factory()->create();
        $emailAccount = EmailAccount::create([
            'email' => 'assigned_' . uniqid() . '@example.com',
            'password' => 'password',
            'domain' => 'example.com',
            'quota' => 100,
            'used' => 0,
            'user_id' => $user1->id,
            'assigned_at' => now(),
        ]);

        // 2. Try to create another user with same email account
        $userData = [
            'name' => 'Test User 2 ' . uniqid(),
            'email' => 'user2_' . uniqid() . '@example.com',
            'password' => 'password123',
            'email_account_id' => $emailAccount->id,
        ];

        // Expecting exception
        try {
            Livewire::test(CreateUser::class)
                ->fillForm($userData)
                ->call('create');
        } catch (\Exception $e) {
             $this->assertStringContainsString('La cuenta de correo ya ha sido asignada', $e->getMessage());
             return;
        }
        
        $user2 = User::where('email', $userData['email'])->first();
        $this->assertNull($user2, "User should not be created if assignment fails");

        // Clean up
        $emailAccount->delete();
        $user1->delete();
    }

    public function test_can_create_user_without_email_account()
    {
        // Create admin user
        $admin = User::factory()->create();
        
        // Assign permissions
        $permissions = ['ViewAny:User', 'Create:User'];
        foreach($permissions as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
            $admin->givePermissionTo($perm);
        }
        
        $this->actingAs($admin);

        $userData = [
            'name' => 'Test User No Email ' . uniqid(),
            'email' => 'user_no_email_' . uniqid() . '@example.com',
            'password' => 'password123',
            // No email_account_id
        ];

        Livewire::test(CreateUser::class)
            ->fillForm($userData)
            ->call('create')
            ->assertHasNoErrors();

        $user = User::where('email', $userData['email'])->first();
        $this->assertNotNull($user);
        $this->assertNull($user->emailAccount);
    }
}
