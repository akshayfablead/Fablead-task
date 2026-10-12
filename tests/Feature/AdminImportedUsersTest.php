<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminImportedUsersTest extends TestCase
{
    use RefreshDatabase;

    public function test_imported_users_page_and_data_endpoint_require_admin_access(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('admin.imported-users.index'))
            ->assertForbidden();

        $this->getJson(route('admin.imported-users.data'))
            ->assertForbidden();
    }

    public function test_datatable_returns_only_imported_users_with_server_side_search(): void
    {
        $admin = $this->createAdmin();
        $imported = User::factory()->create([
            'name' => 'Imported Sample',
            'email' => 'imported-sample@example.test',
            'import_batch_id' => (string) Str::uuid(),
        ]);
        $imported->records()->create([
            'title' => 'Imported sample related record',
            'description' => 'This user should appear in relationship listing.',
            'status' => 'active',
        ]);
        User::factory()->create([
            'name' => 'Ordinary Account',
            'email' => 'ordinary@example.test',
            'import_batch_id' => null,
        ]);
        $unlinkedImported = User::factory()->create([
            'name' => 'Unlinked Imported Sample',
            'email' => 'unlinked-imported-sample@example.test',
            'import_batch_id' => (string) Str::uuid(),
        ]);

        $this->actingAs($admin)
            ->getJson(route('admin.imported-users.data', [
                'draw' => 7,
                'start' => 0,
                'length' => 10,
                'search' => ['value' => 'imported-sample'],
            ]))
            ->assertOk()
            ->assertJsonPath('draw', 7)
            ->assertJsonPath('recordsTotal', 1)
            ->assertJsonPath('recordsFiltered', 1)
            ->assertJsonPath('data.0.id', $imported->id)
            ->assertJsonMissing(['id' => $unlinkedImported->id])
            ->assertJsonMissing(['email' => 'ordinary@example.test']);
    }

    public function test_datatable_includes_related_records_for_imported_users(): void
    {
        $admin = $this->createAdmin();
        $imported = User::factory()->create([
            'import_batch_id' => (string) Str::uuid(),
        ]);
        $record = $imported->records()->create([
            'title' => 'Imported user related record',
            'description' => 'Related through the user model.',
            'status' => 'active',
        ]);

        $this->actingAs($admin)
            ->getJson(route('admin.imported-users.data'))
            ->assertOk()
            ->assertJsonPath('data.0.id', $imported->id)
            ->assertJsonPath('data.0.records_count', 1)
            ->assertJsonPath('data.0.records.0.id', $record->id)
            ->assertJsonPath('data.0.records.0.title', 'Imported user related record')
            ->assertJsonPath('data.0.records.0.status', 'active');
    }

    public function test_datatable_includes_relationship_test_users_with_their_records(): void
    {
        $admin = $this->createAdmin();
        $batchId = (string) Str::uuid();
        DB::table('relationship_load_test_batches')->insert([
            'id' => $batchId,
            'status' => 'completed',
            'requested_users' => 1,
            'requested_records' => 1,
            'created_users' => 1,
            'created_records' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $user = User::factory()->create();
        $user->forceFill(['load_test_batch_id' => $batchId])->save();
        $record = $user->records()->create([
            'title' => 'Relationship test record',
            'description' => 'Loaded using the user relationship.',
            'status' => 'active',
        ]);
        $record->forceFill(['load_test_batch_id' => $batchId])->save();

        $this->actingAs($admin)
            ->getJson(route('admin.imported-users.data'))
            ->assertOk()
            ->assertJsonPath('recordsTotal', 1)
            ->assertJsonPath('data.0.id', $user->id)
            ->assertJsonPath('data.0.is_imported_user', false)
            ->assertJsonPath('data.0.records.0.id', $record->id)
            ->assertJsonPath('data.0.records.0.title', 'Relationship test record');
    }

    public function test_datatable_sorts_using_the_visible_created_column_index(): void
    {
        $admin = $this->createAdmin();
        $older = User::factory()->create([
            'import_batch_id' => (string) Str::uuid(),
            'created_at' => now()->subDay(),
        ]);
        $older->records()->create([
            'title' => 'Older related record',
            'description' => 'Older test row.',
            'status' => 'active',
        ]);

        $newer = User::factory()->create([
            'import_batch_id' => (string) Str::uuid(),
            'created_at' => now(),
        ]);
        $newer->records()->create([
            'title' => 'Newer related record',
            'description' => 'Newer test row.',
            'status' => 'active',
        ]);

        $this->actingAs($admin)
            ->getJson(route('admin.imported-users.data', [
                'start' => 0,
                'length' => 10,
                'order' => [['column' => 5, 'dir' => 'asc']],
            ]))
            ->assertOk()
            ->assertJsonPath('data.0.id', $older->id)
            ->assertJsonPath('data.1.id', $newer->id);
    }

    public function test_admin_can_edit_an_imported_user_but_not_a_regular_account(): void
    {
        $admin = $this->createAdmin();
        $imported = User::factory()->create([
            'email' => 'before@example.test',
            'phone' => null,
            'import_batch_id' => (string) Str::uuid(),
        ]);
        $regular = User::factory()->create([
            'email' => 'regular@example.test',
            'import_batch_id' => null,
        ]);

        $this->actingAs($admin)
            ->putJson(route('admin.imported-users.update', $imported), [
                'name' => 'Updated Imported User',
                'email' => 'after@example.test',
                'phone' => '+15550000017',
            ])
            ->assertOk()
            ->assertJsonPath('message', 'Imported test user updated.');

        $this->assertDatabaseHas('users', [
            'id' => $imported->id,
            'name' => 'Updated Imported User',
            'email' => 'after@example.test',
            'phone' => '+15550000017',
            'import_batch_id' => $imported->import_batch_id,
        ]);

        $this->putJson(route('admin.imported-users.update', $regular), [
            'name' => 'Should Not Change',
            'email' => 'regular@example.test',
            'phone' => null,
        ])->assertNotFound();

        $this->assertDatabaseHas('users', [
            'id' => $regular->id,
            'name' => $regular->name,
            'email' => 'regular@example.test',
        ]);
    }

    public function test_admin_can_delete_an_imported_user_without_touching_regular_accounts(): void
    {
        $admin = $this->createAdmin();
        $imported = User::factory()->create([
            'import_batch_id' => (string) Str::uuid(),
        ]);
        $regular = User::factory()->create([
            'import_batch_id' => null,
        ]);

        $this->actingAs($admin)
            ->deleteJson(route('admin.imported-users.destroy', $imported))
            ->assertOk()
            ->assertJsonPath('message', 'Imported test user deleted.');

        $this->assertDatabaseMissing('users', ['id' => $imported->id]);
        $this->assertDatabaseHas('users', ['id' => $regular->id]);
        $this->deleteJson(route('admin.imported-users.destroy', $regular))
            ->assertNotFound();
        $this->assertDatabaseHas('users', ['id' => $regular->id]);
    }

    private function createAdmin(): User
    {
        $admin = User::factory()->create();
        $role = Role::firstOrCreate([
            'name' => 'Admin',
            'guard_name' => 'web',
        ]);
        $admin->assignRole($role);

        return $admin;
    }
}
