<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Throwable;

class SeedRelationshipLoadTest extends Command
{
    protected $signature = 'load-test:seed-relationships
        {--users=100000 : Number of synthetic users to create}
        {--records=150000 : Number of synthetic records to create}
        {--confirm : Confirm additive data creation on the local database}';

    protected $description = 'Create marked synthetic users and related records on local MySQL';

    public function handle(): int
    {
        if (! $this->isLocalDatabase()) {
            $this->error('This command is restricted to local APP_ENV and loopback MySQL.');

            return self::FAILURE;
        }

        if (! $this->option('confirm')) {
            $this->error('No data was created. Review the target and rerun with --confirm.');

            return self::FAILURE;
        }

        $userCount = filter_var($this->option('users'), FILTER_VALIDATE_INT);
        $recordCount = filter_var($this->option('records'), FILTER_VALIDATE_INT);

        if ($userCount === false || $userCount < 1 || $userCount > 500_000
            || $recordCount === false || $recordCount < 1 || $recordCount > 1_000_000) {
            $this->error('Users must be 1-500,000 and records must be 1-1,000,000.');

            return self::FAILURE;
        }

        $this->info(sprintf(
            'Target: local MySQL %s / %s; create %s users and %s related records.',
            config('database.connections.'.config('database.default').'.host'),
            DB::connection()->getDatabaseName(),
            number_format($userCount),
            number_format($recordCount)
        ));

        $batchId = (string) Str::uuid();
        $now = now();

        DB::table('relationship_load_test_batches')->insert([
            'id' => $batchId,
            'status' => 'processing',
            'requested_users' => $userCount,
            'requested_records' => $recordCount,
            'created_users' => 0,
            'created_records' => 0,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        try {
            $password = Hash::make(Str::random(64));
            $this->createUsers($batchId, $userCount, $password);

            $userIds = DB::table('users')
                ->where('load_test_batch_id', $batchId)
                ->orderBy('id')
                ->pluck('id')
                ->all();

            if (count($userIds) !== $userCount) {
                throw new \RuntimeException('The generated user count did not match the requested count.');
            }

            $this->createRecords($batchId, $userIds, $recordCount);

            DB::table('relationship_load_test_batches')
                ->where('id', $batchId)
                ->update([
                    'status' => 'completed',
                    'created_users' => $userCount,
                    'created_records' => $recordCount,
                    'updated_at' => now(),
                ]);
        } catch (Throwable $exception) {
            DB::table('relationship_load_test_batches')
                ->where('id', $batchId)
                ->update([
                    'status' => 'failed',
                    'created_users' => DB::table('users')
                        ->where('load_test_batch_id', $batchId)
                        ->count(),
                    'created_records' => DB::table('records')
                        ->where('load_test_batch_id', $batchId)
                        ->count(),
                    'error_message' => Str::limit($exception->getMessage(), 60_000, ''),
                    'updated_at' => now(),
                ]);

            throw $exception;
        }

        $this->newLine();
        $this->info('Relationship load-test data created.');
        $this->line('Batch ID: '.$batchId);
        $this->line('Use the admin Imported Users page to verify each user and related records.');
        $this->line('Run php artisan load-test:cleanup-relationships '.$batchId.' --confirm when finished.');

        return self::SUCCESS;
    }

    private function createUsers(string $batchId, int $count, string $password): void
    {
        $now = now();

        for ($offset = 0; $offset < $count; $offset += 500) {
            $users = [];
            $limit = min(500, $count - $offset);

            for ($index = 1; $index <= $limit; $index++) {
                $number = $offset + $index;
                $users[] = [
                    'name' => sprintf('Load Test User %06d', $number),
                    'email' => sprintf('loadtest.%s.%06d@example.test', $batchId, $number),
                    'phone' => null,
                    'password' => $password,
                    'email_verified_at' => null,
                    'load_test_batch_id' => $batchId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            DB::table('users')->insert($users);
            DB::table('relationship_load_test_batches')
                ->where('id', $batchId)
                ->increment('created_users', count($users), ['updated_at' => now()]);

            $this->output->write('.');
        }
    }

    /**
     * @param  array<int, int>  $userIds
     */
    private function createRecords(string $batchId, array $userIds, int $count): void
    {
        $statuses = ['draft', 'active', 'inactive'];
        $now = now();

        for ($offset = 0; $offset < $count; $offset += 500) {
            $records = [];
            $limit = min(500, $count - $offset);

            for ($index = 1; $index <= $limit; $index++) {
                $number = $offset + $index;
                $records[] = [
                    'title' => sprintf('Load Test Record %06d', $number),
                    'description' => sprintf('Synthetic relationship test record %06d.', $number),
                    'status' => $statuses[($number - 1) % count($statuses)],
                    'created_by' => $userIds[($number - 1) % count($userIds)],
                    'load_test_batch_id' => $batchId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            DB::table('records')->insert($records);
            DB::table('relationship_load_test_batches')
                ->where('id', $batchId)
                ->increment('created_records', count($records), ['updated_at' => now()]);

            $this->output->write('.');
        }
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
