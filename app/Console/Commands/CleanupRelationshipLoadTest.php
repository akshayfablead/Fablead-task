<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CleanupRelationshipLoadTest extends Command
{
    protected $signature = 'load-test:cleanup-relationships
        {batchId : Relationship load-test batch UUID}
        {--confirm : Confirm deletion of this marked synthetic batch}';

    protected $description = 'Remove one marked relationship load-test batch on local MySQL';

    public function handle(): int
    {
        if (! $this->isLocalDatabase()) {
            $this->error('This command is restricted to local APP_ENV and loopback MySQL.');

            return self::FAILURE;
        }

        if (! $this->option('confirm')) {
            $this->error('No data was deleted. Rerun with --confirm after reviewing the batch ID.');

            return self::FAILURE;
        }

        $batchId = (string) $this->argument('batchId');
        $batch = DB::table('relationship_load_test_batches')->where('id', $batchId)->first();

        if ($batch === null || $batch->status === 'processing') {
            $this->error('The batch is missing or still processing; nothing was deleted.');

            return self::FAILURE;
        }

        $userIds = DB::table('users')
            ->select('id')
            ->where('load_test_batch_id', $batchId);

        if (DB::table('records')
            ->whereIn('created_by', $userIds)
            ->whereNull('load_test_batch_id')
            ->exists()) {
            $this->error('Batch users have records outside this test batch; cleanup was stopped.');

            return self::FAILURE;
        }

        DB::transaction(function () use ($batchId): void {
            $userIds = DB::table('users')
                ->select('id')
                ->where('load_test_batch_id', $batchId);

            DB::table('records')
                ->where('load_test_batch_id', $batchId)
                ->delete();

            DB::table('sessions')->whereIn('user_id', $userIds)->delete();
            DB::table('oauth_access_tokens')->whereIn('user_id', $userIds)->delete();
            DB::table('oauth_auth_codes')->whereIn('user_id', $userIds)->delete();
            DB::table('oauth_device_codes')->whereIn('user_id', $userIds)->delete();
            DB::table('google_calendar_tokens')->whereIn('user_id', $userIds)->delete();
            DB::table('notifications')
                ->where('notifiable_type', 'App\\Models\\User')
                ->whereIn('notifiable_id', $userIds)
                ->delete();
            DB::table('model_has_roles')
                ->where('model_type', 'App\\Models\\User')
                ->whereIn('model_id', $userIds)
                ->delete();
            DB::table('model_has_permissions')
                ->where('model_type', 'App\\Models\\User')
                ->whereIn('model_id', $userIds)
                ->delete();
            DB::table('password_reset_tokens')
                ->whereIn('email', DB::table('users')
                    ->select('email')
                    ->where('load_test_batch_id', $batchId))
                ->delete();
            DB::table('users')->where('load_test_batch_id', $batchId)->delete();
            DB::table('relationship_load_test_batches')->where('id', $batchId)->delete();
        });

        $this->info('Marked relationship load-test batch removed: '.$batchId);

        return self::SUCCESS;
    }

    private function isLocalDatabase(): bool
    {
        $connection = config('database.default');
        $host = (string) config("database.connections.{$connection}.host");

        return app()->environment('local')
            && DB::connection()->getDriverName() === 'mysql'
            && in_array($host, ['127.0.0.1', 'localhost', '::1'], true);
    }
}
