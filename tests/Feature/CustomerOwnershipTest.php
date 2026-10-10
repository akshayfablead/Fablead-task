<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\FirebaseFirestoreService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Laravel\Passport\Passport;
use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;
use Mockery;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CustomerOwnershipTest extends TestCase
{
    use RefreshDatabase;

    public function test_regular_users_only_see_their_own_customers(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $this->mockFirestore([
            ['id' => 'own-customer', 'created_by' => $user->getKey()],
            ['id' => 'other-customer', 'created_by' => $otherUser->getKey()],
            ['id' => 'legacy-customer'],
        ]);

        $this->actingAs($user)
            ->getJson('/api/customers')
            ->assertOk()
            ->assertJsonPath('data.0.id', 'own-customer')
            ->assertJsonCount(1, 'data');
    }

    public function test_admins_can_see_customers_from_all_users(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(Role::create([
            'name' => 'Admin',
            'guard_name' => 'web',
        ]));

        $this->mockFirestore([
            ['id' => 'own-customer', 'created_by' => 1],
            ['id' => 'other-customer', 'created_by' => 2],
            ['id' => 'legacy-customer'],
        ]);

        $this->actingAs($admin)
            ->getJson('/api/customers')
            ->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_passport_authenticated_users_can_access_api_routes(): void
    {
        $user = User::factory()->create();
        $this->mockFirestore([
            ['id' => 'passport-customer', 'created_by' => $user->getKey()],
        ]);

        Passport::actingAs($user, ['*']);

        $this->getJson('/api/customers')
            ->assertOk()
            ->assertJsonPath('data.0.id', 'passport-customer');
    }

    public function test_localhost_laravel_server_is_recognized_as_a_stateful_sanctum_frontend(): void
    {
        $request = Request::create('/api/customers', 'GET', [], [], [], [
            'HTTP_REFERER' => 'http://localhost:8000/customers',
        ]);

        $this->assertTrue(
            EnsureFrontendRequestsAreStateful::fromFrontend($request)
        );
    }

    public function test_non_admin_passport_users_cannot_access_admin_api_routes(): void
    {
        Passport::actingAs(User::factory()->create(), ['*']);

        $this->postJson('/api/admin/accounts', [])
            ->assertForbidden();
    }

    public function test_users_cannot_read_or_modify_another_users_customer(): void
    {
        $user = User::factory()->create();
        $customer = [
            'id' => 'other-customer',
            'created_by' => $user->getKey() + 1,
        ];

        $firestore = Mockery::mock(FirebaseFirestoreService::class);
        $firestore->shouldReceive('find')
            ->times(3)
            ->with('customers', 'other-customer')
            ->andReturn($customer);
        $this->app->instance(FirebaseFirestoreService::class, $firestore);

        $this->actingAs($user)
            ->getJson('/api/customers/other-customer')
            ->assertNotFound();

        $this->putJson('/api/customers/other-customer', [
            'name' => 'Updated',
            'email' => 'updated@example.com',
            'phone' => '+15555555555',
        ])->assertNotFound();

        $this->deleteJson('/api/customers/other-customer')
            ->assertNotFound();
    }

    public function test_customer_creation_uses_the_authenticated_user_as_owner(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $firestore = Mockery::mock(FirebaseFirestoreService::class);
        $firestore->shouldReceive('create')
            ->once()
            ->with('customers', Mockery::on(
                fn (array $data): bool => $data['created_by'] === $user->getAuthIdentifier()
            ))
            ->andReturn([
                'id' => 'new-customer',
                'created_by' => $user->getAuthIdentifier(),
            ]);
        $this->app->instance(FirebaseFirestoreService::class, $firestore);

        $this->actingAs($user)
            ->postJson('/api/customers', [
                'name' => 'New Customer',
                'email' => 'customer@example.com',
                'phone' => '+15555555555',
                'created_by' => $otherUser->getAuthIdentifier(),
            ])
            ->assertCreated()
            ->assertJsonPath('data.created_by', $user->getAuthIdentifier());
    }

    public function test_customer_and_whatsapp_ajax_routes_require_authentication(): void
    {
        $this->getJson('/api/customers')->assertUnauthorized();
        $this->postJson('/api/whatsapp/send')->assertUnauthorized();
        $this->postJson('/api/whatsapp/send-template')->assertUnauthorized();
    }

    private function mockFirestore(array $customers): void
    {
        $firestore = Mockery::mock(FirebaseFirestoreService::class);
        $firestore->shouldReceive('getAll')
            ->once()
            ->with('customers')
            ->andReturn($customers);

        $this->app->instance(FirebaseFirestoreService::class, $firestore);
    }
}
