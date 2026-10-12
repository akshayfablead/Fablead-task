<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class RelationshipLoadTestCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_relationship_load_test_seed_is_blocked_outside_local_mysql(): void
    {
        $exitCode = Artisan::call('load-test:seed-relationships', [
            '--users' => 1,
            '--records' => 1,
            '--confirm' => true,
        ]);

        $this->assertSame(1, $exitCode);
        $this->assertSame(0, \DB::table('relationship_load_test_batches')->count());
        $this->assertSame(0, \DB::table('users')->count());
        $this->assertSame(0, \DB::table('records')->count());
    }
}
