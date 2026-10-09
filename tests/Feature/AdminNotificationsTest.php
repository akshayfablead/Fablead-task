<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\CrmNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminNotificationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_creating_an_account_notifies_the_new_user(): void
    {
        $admin = $this->createAdmin();
        $role = $this->createRole('Manager');

        $response = $this->actingAs($admin)->postJson('/admin/accounts', [
            'name' => 'New User',
            'email' => 'new-user@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role_id' => $role->getKey(),
            'overrides' => [],
        ]);

        $response->assertCreated();

        $user = User::query()->where('email', 'new-user@example.com')->firstOrFail();

        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $user->getKey(),
            'notifiable_type' => User::class,
            'type' => CrmNotification::class,
        ]);

        $this->assertSame(
            'account.created',
            data_get($user->notifications()->firstOrFail()->getAttribute('data'), 'event')
        );
    }

    public function test_updating_an_account_notifies_the_affected_user(): void
    {
        $admin = $this->createAdmin();
        $role = $this->createRole('Manager');
        $user = User::factory()->create();
        $user->assignRole($role);

        $this->actingAs($admin)
            ->putJson("/admin/accounts/{$user->getKey()}", [
                'name' => 'Updated User',
                'email' => $user->email,
                'role_id' => $role->getKey(),
                'overrides' => [],
            ])
            ->assertOk();

        $this->assertSame(
            'account.updated',
            data_get($user->notifications()->firstOrFail()->getAttribute('data'), 'event')
        );
    }

    public function test_updating_a_role_notifies_users_assigned_to_that_role(): void
    {
        $admin = $this->createAdmin();
        $role = $this->createRole('Sales');
        $user = User::factory()->create();
        $user->assignRole($role);

        $this->actingAs($admin)
            ->putJson("/admin/roles/{$role->getKey()}", [
                'name' => 'Sales Team',
                'permissions' => [],
            ])
            ->assertOk();

        $this->assertSame(
            'role.updated',
            data_get($user->notifications()->firstOrFail()->getAttribute('data'), 'event')
        );
    }

    public function test_other_admins_are_notified_when_a_role_is_created(): void
    {
        $admin = $this->createAdmin();
        $otherAdmin = $this->createAdmin();

        $this->actingAs($admin)
            ->postJson('/admin/roles', [
                'name' => 'Support',
                'permissions' => [],
            ])
            ->assertCreated();

        $this->assertSame(
            'role.created',
            data_get($otherAdmin->notifications()->firstOrFail()->getAttribute('data'), 'event')
        );
        $this->assertSame(0, $admin->notifications()->count());
    }

    public function test_users_can_load_and_mark_their_notifications_as_read(): void
    {
        $user = User::factory()->create();
        $user->notify(new CrmNotification(
            title: 'Test',
            message: 'Test notification.',
            event: 'test.event',
        ));
        $notification = $user->notifications()->firstOrFail();

        $this->actingAs($user)
            ->getJson('/api/notifications')
            ->assertOk()
            ->assertJsonPath('data.0.data.event', 'test.event');

        $this->patchJson("/api/notifications/{$notification->getKey()}/read")
            ->assertOk();

        $this->assertNotNull($notification->fresh()->getAttribute('read_at'));
        $this->getJson('/api/notifications/unread-count')
            ->assertOk()
            ->assertJsonPath('unread_count', 0);
    }

    private function createAdmin(): User
    {
        $admin = User::factory()->create();
        $admin->assignRole($this->createRole('Admin'));

        return $admin;
    }

    private function createRole(string $name): Role
    {
        return Role::firstOrCreate([
            'name' => $name,
            'guard_name' => 'web',
        ]);
    }
}
