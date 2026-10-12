<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\UserImport;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BenchmarkUserImport extends Command
{
    protected $signature = 'users:benchmark-import {importId}';

    protected $description = 'Benchmark local user listing and search queries for a completed CSV import';

    public function handle(): int
    {
        $connection = DB::connection();
        $host = (string) config('database.connections.'.config('database.default').'.host');

        if (! app()->environment('local')
            || config('database.default') !== 'mysql'
            || ! in_array($host, ['127.0.0.1', 'localhost', '::1'], true)) {
            $this->error('Benchmarking is restricted to the local APP_ENV and loopback MySQL.');

            return self::FAILURE;
        }

        $import = UserImport::query()->find($this->argument('importId'));

        if ($import === null || $import->status !== UserImport::STATUS_COMPLETED) {
            $this->error('Provide the ID of a completed user import.');

            return self::FAILURE;
        }

        $count = User::query()
            ->where('import_batch_id', $import->getKey())
            ->count();

        if ($count !== $import->imported_rows) {
            $this->error('The import marker count does not match the reported imported row count.');

            return self::FAILURE;
        }

        $sample = User::query()
            ->where('import_batch_id', $import->getKey())
            ->orderByDesc('id')
            ->first(['name', 'email']);

        if ($sample === null) {
            $this->error('The import batch contains no users to benchmark.');

            return self::FAILURE;
        }

        $nameTerm = $sample->name;
        $results = [
            $this->measure('Paginated listing (15, with roles)', fn () => User::query()
                ->where('import_batch_id', $import->getKey())
                ->with('roles')
                ->orderBy('id')
                ->paginate(15)
                ->items()),
            $this->measure('Name search (contains)', fn () => User::query()
                ->where('import_batch_id', $import->getKey())
                ->where('name', 'like', '%'.$nameTerm.'%')
                ->orderBy('id')
                ->limit(15)
                ->get(['id', 'name', 'email'])
                ->all()),
            $this->measure('Email search (exact)', fn () => User::query()
                ->where('import_batch_id', $import->getKey())
                ->where('email', $sample->email)
                ->first(['id', 'name', 'email'])),
            $this->measure('Phone filter (present)', fn () => User::query()
                ->where('import_batch_id', $import->getKey())
                ->whereNotNull('phone')
                ->orderBy('id')
                ->limit(15)
                ->get(['id', 'name', 'email', 'phone'])
                ->all()),
        ];

        $this->info('Local user import benchmark');
        $this->line('Environment: '.app()->environment());
        $this->line('Database: '.config('database.default').' / '.$connection->getDatabaseName());
        $this->line('Import batch: '.$import->getKey());
        $this->line('Marked users: '.number_format($count));
        $this->table(
            ['Query', 'Rows returned', 'Elapsed ms', 'DB query ms', 'Memory delta KB'],
            array_map(fn (array $result): array => [
                $result['label'],
                $result['rows'],
                number_format($result['elapsed_ms'], 2),
                number_format($result['db_ms'], 2),
                number_format($result['memory_delta_kb'], 1),
            ], $results)
        );
        $this->warn('These are in-process database query measurements, not HTTP round-trip times.');
        $this->warn('Measure an authenticated admin page in Postman/browser separately for HTTP response time.');

        return self::SUCCESS;
    }

    /**
     * @return array{label: string, rows: int, elapsed_ms: float, db_ms: float, memory_delta_kb: float}
     */
    private function measure(string $label, callable $query): array
    {
        $connection = DB::connection();
        $connection->flushQueryLog();
        $connection->enableQueryLog();
        $memoryBefore = memory_get_usage();
        $startedAt = hrtime(true);
        $result = $query();
        $elapsedMs = (hrtime(true) - $startedAt) / 1_000_000;
        $queryLog = $connection->getQueryLog();
        $connection->disableQueryLog();

        return [
            'label' => $label,
            'rows' => is_array($result) ? count($result) : ($result === null ? 0 : 1),
            'elapsed_ms' => $elapsedMs,
            'db_ms' => array_sum(array_column($queryLog, 'time')),
            'memory_delta_kb' => (memory_get_usage() - $memoryBefore) / 1024,
        ];
    }
}
