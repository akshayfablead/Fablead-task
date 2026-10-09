<?php

namespace Tests\Feature;

use App\Models\Record;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RecordNotificationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_creating_a_record_notifies_other_admin_users(): void
    {
        $creator = $this->createAdmin();
        $otherAdmin = $this->createAdmin();

        $this->actingAs($creator)
            ->postJson('/records', [
                'title' => 'New CRM record',
                'description' => 'A record created through AJAX.',
                'status' => 'active',
            ])
            ->assertCreated()
            ->assertJsonPath('message', 'Record created successfully.');

        $this->assertDatabaseHas('records', [
            'title' => 'New CRM record',
            'created_by' => $creator->getKey(),
        ]);

        $notification = $otherAdmin->notifications()->firstOrFail();

        $this->assertSame(
            'record.created',
            data_get($notification->getAttribute('data'), 'event')
        );
    }

    public function test_updating_a_record_notifies_other_admin_users(): void
    {
        $creator = $this->createAdmin();
        $otherAdmin = $this->createAdmin();
        $record = $creator->records()->create([
            'title' => 'Existing record',
            'description' => 'Before update.',
            'status' => 'draft',
        ]);

        $this->actingAs($creator)
            ->putJson("/records/{$record->getKey()}", [
                'title' => 'Updated CRM record',
                'description' => 'After update.',
                'status' => 'active',
            ])
            ->assertOk()
            ->assertJsonPath('message', 'Record updated successfully.');

        $notification = $otherAdmin->notifications()->firstOrFail();

        $this->assertSame(
            'record.updated',
            data_get($notification->getAttribute('data'), 'event')
        );
    }

    private function createAdmin(): User
    {
        $adminRole = Role::firstOrCreate([
            'name' => 'Admin',
            'guard_name' => 'web',
        ]);
        $user = User::factory()->create();
        $user->assignRole($adminRole);

        return $user;
    }
}
