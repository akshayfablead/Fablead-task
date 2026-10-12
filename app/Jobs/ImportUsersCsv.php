<?php

namespace App\Jobs;

use App\Models\UserImport;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Throwable;

class ImportUsersCsv implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 60;

    public function __construct(public string $userImportId) {}

    public function handle(): void
    {
        $import = UserImport::query()->findOrFail($this->userImportId);

        if (! in_array($import->status, [
            UserImport::STATUS_QUEUED,
            UserImport::STATUS_PROCESSING,
        ], true)) {
            return;
        }

        $filePath = Storage::disk('local')->path($import->file_path);

        if (! is_file($filePath)) {
            $this->markFailed('The staged CSV file is missing.');

            return;
        }

        $handle = fopen($filePath, 'rb');

        if ($handle === false) {
            $this->markFailed('The staged CSV file could not be opened.');

            return;
        }

        try {
            $startOffset = $import->next_byte_offset;

            if ($startOffset > 0 && fseek($handle, $startOffset) !== 0) {
                $this->markFailed('The staged CSV checkpoint could not be read.');

                return;
            }

            $header = $startOffset === 0
                ? fgetcsv($handle, null, ',', '"', '')
                : null;

            if ($startOffset === 0 && $header === false) {
                $this->markFailed('The staged CSV header could not be read.');

                return;
            }

            if ($header !== null) {
                $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) $header[0]);
            }

            $columnMap = $this->columnMap($header ?? $this->readHeader($filePath));
            $nextRowNumber = $import->next_row_number;
            $rows = [];
            $reachedEnd = false;

            while (count($rows) < $this->batchSize()) {
                $cells = fgetcsv($handle, null, ',', '"', '');

                if ($cells === false) {
                    $reachedEnd = true;
                    break;
                }

                $rowNumber = $nextRowNumber++;

                if ($this->isBlankRow($cells)) {
                    continue;
                }

                $rows[] = [
                    'cells' => $cells,
                    'row_number' => $rowNumber,
                ];
            }

            $nextByteOffset = ftell($handle);

            if ($nextByteOffset === false) {
                $this->markFailed('The staged CSV checkpoint could not be recorded.');

                return;
            }

            $rowsAtEnd = $reachedEnd || feof($handle);
            $outcomes = $this->validateRows($rows, $columnMap);
            $passwordHash = Hash::make(Str::random(64));
            $continue = false;

            DB::transaction(function () use (
                $import,
                $startOffset,
                $nextByteOffset,
                $nextRowNumber,
                $rowsAtEnd,
                $outcomes,
                $passwordHash,
                &$continue
            ): void {
                $lockedImport = UserImport::query()
                    ->whereKey($import->getKey())
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($lockedImport->next_byte_offset !== $startOffset
                    || ! in_array($lockedImport->status, [
                        UserImport::STATUS_QUEUED,
                        UserImport::STATUS_PROCESSING,
                    ], true)) {
                    return;
                }

                $now = now();
                $users = array_map(
                    fn (array $row): array => [
                        'name' => $row['name'],
                        'email' => $row['email'],
                        'phone' => $row['phone'],
                        'password' => $passwordHash,
                        'email_verified_at' => null,
                        'import_batch_id' => $lockedImport->getKey(),
                        'created_at' => $now,
                        'updated_at' => $now,
                    ],
                    $outcomes['users']
                );

                if ($users !== []) {
                    foreach (array_chunk($users, 100) as $userChunk) {
                        DB::table('users')->insert($userChunk);
                    }
                }

                if ($outcomes['failures'] !== []) {
                    $failureRows = array_map(
                        fn (array $failure): array => [
                            'user_import_id' => $lockedImport->getKey(),
                            'row_number' => $failure['row_number'],
                            'reason' => $failure['reason'],
                            'created_at' => $now,
                            'updated_at' => $now,
                        ],
                        $outcomes['failures']
                    );

                    foreach (array_chunk($failureRows, 100) as $failureChunk) {
                        DB::table('user_import_failures')->insert($failureChunk);
                    }
                }

                $processed = count($outcomes['users'])
                    + count($outcomes['failures'])
                    + $outcomes['skipped'];
                $complete = $rowsAtEnd;
                $startedAt = $lockedImport->started_at ?? $now;
                $finishedAt = $complete ? $now : null;

                $lockedImport->forceFill([
                    'status' => $complete
                        ? UserImport::STATUS_COMPLETED
                        : UserImport::STATUS_PROCESSING,
                    'error_message' => null,
                    'started_at' => $startedAt,
                    'finished_at' => $finishedAt,
                    'duration_ms' => $finishedAt
                        ? max(1, $finishedAt->diffInMilliseconds($startedAt))
                        : null,
                    'processed_rows' => $lockedImport->processed_rows + $processed,
                    'imported_rows' => $lockedImport->imported_rows
                        + count($outcomes['users']),
                    'skipped_rows' => $lockedImport->skipped_rows
                        + $outcomes['skipped'],
                    'failed_rows' => $lockedImport->failed_rows
                        + count($outcomes['failures']),
                    'next_byte_offset' => $nextByteOffset,
                    'next_row_number' => $nextRowNumber,
                ])->save();

                $continue = ! $complete;
            });

            if ($continue) {
                self::dispatch($this->userImportId);
            } else {
                $completedImport = UserImport::query()->find($this->userImportId);

                if ($completedImport?->status === UserImport::STATUS_COMPLETED
                    && ! Storage::disk('local')->delete($completedImport->file_path)) {
                    Log::warning('Completed CSV user import file could not be removed.', [
                        'user_import_id' => $this->userImportId,
                    ]);
                }
            }
        } finally {
            fclose($handle);
        }
    }

    public function failed(Throwable $exception): void
    {
        Log::error('CSV user import job failed.', [
            'user_import_id' => $this->userImportId,
            'message' => $exception->getMessage(),
        ]);

        $this->markFailed('The import job failed. Check the application log for details.');
    }

    /**
     * @param  array<int, array{cells: array<int, string|null>, row_number: int}>  $rows
     * @param  array<string, int>  $columnMap
     * @return array{users: array<int, array{name: string, email: string, phone: ?string, row_number: int}>, failures: array<int, array{row_number: int, reason: string}>, skipped: int}
     */
    private function validateRows(array $rows, array $columnMap): array
    {
        $users = [];
        $failures = [];
        $skipped = 0;
        $seenEmails = [];
        $seenPhones = [];

        foreach ($rows as $row) {
            if (count($row['cells']) !== count($columnMap)) {
                $failures[] = [
                    'row_number' => $row['row_number'],
                    'reason' => 'Column count does not match the CSV header.',
                ];

                continue;
            }

            $data = [
                'name' => trim((string) ($row['cells'][$columnMap['name']] ?? '')),
                'email' => trim((string) ($row['cells'][$columnMap['email']] ?? '')),
                'phone' => isset($columnMap['phone'])
                    ? trim((string) ($row['cells'][$columnMap['phone']] ?? ''))
                    : null,
            ];
            $data['phone'] = $data['phone'] === '' ? null : $data['phone'];

            $containsInvalidText = collect($data)
                ->filter(fn (?string $value): bool => $value !== null)
                ->contains(fn (string $value): bool => ! mb_check_encoding($value, 'UTF-8')
                    || str_contains($value, "\0"));

            if ($containsInvalidText) {
                $failures[] = [
                    'row_number' => $row['row_number'],
                    'reason' => 'A field contains invalid text.',
                ];

                continue;
            }

            $validator = Validator::make($data, [
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'string', 'email', 'max:255'],
                'phone' => ['nullable', 'string', 'max:255'],
            ]);

            if ($validator->fails()) {
                $failures[] = [
                    'row_number' => $row['row_number'],
                    'reason' => $validator->errors()->first(),
                ];

                continue;
            }

            $email = mb_strtolower($data['email']);

            if (isset($seenEmails[$email])) {
                $skipped++;

                continue;
            }

            $seenEmails[$email] = true;
            $phone = $data['phone'];

            if ($phone !== null && isset($seenPhones[$phone])) {
                $failures[] = [
                    'row_number' => $row['row_number'],
                    'reason' => 'Phone number is duplicated in this file.',
                ];

                continue;
            }

            if ($phone !== null) {
                $seenPhones[$phone] = true;
            }

            $users[] = [
                'name' => $data['name'],
                'email' => $email,
                'phone' => $phone,
                'row_number' => $row['row_number'],
            ];
        }

        if ($users === []) {
            return compact('users', 'failures', 'skipped');
        }

        $emails = array_column($users, 'email');
        $existingEmails = [];
        $connectionName = config('database.default');
        $caseInsensitiveEmailLookup = in_array(
            DB::connection()->getDriverName(),
            ['mysql', 'mariadb'],
            true
        ) && str_ends_with(
            (string) config("database.connections.{$connectionName}.collation"),
            '_ci'
        );

        foreach (array_chunk($emails, 500) as $emailChunk) {
            $emailColumn = $caseInsensitiveEmailLookup ? 'email' : DB::raw('LOWER(email)');
            $matchedEmails = DB::table('users')
                ->whereIn($emailColumn, $emailChunk)
                ->pluck('email')
                ->map(fn (string $email): string => mb_strtolower($email))
                ->all();

            $existingEmails += array_fill_keys($matchedEmails, true);
        }

        $phones = array_values(array_filter(array_column($users, 'phone')));
        $existingPhones = [];

        foreach (array_chunk($phones, 500) as $phoneChunk) {
            $existingPhones += array_fill_keys(
                DB::table('users')->whereIn('phone', $phoneChunk)->pluck('phone')->all(),
                true
            );
        }

        $accepted = [];

        foreach ($users as $user) {
            if (isset($existingEmails[$user['email']])) {
                $skipped++;

                continue;
            }

            if ($user['phone'] !== null && isset($existingPhones[$user['phone']])) {
                $failures[] = [
                    'row_number' => $user['row_number'],
                    'reason' => 'Phone number already exists in the database.',
                ];

                continue;
            }

            $accepted[] = $user;
        }

        return [
            'users' => $accepted,
            'failures' => $failures,
            'skipped' => $skipped,
        ];
    }

    /**
     * @param  array<int, string|null>|null  $header
     * @return array<string, int>
     */
    private function columnMap(?array $header): array
    {
        if ($header === null || $header === []) {
            throw new \UnexpectedValueException('The CSV header is missing.');
        }

        $map = [];

        foreach ($header as $index => $column) {
            $key = mb_strtolower(trim((string) $column));

            if ($key !== '') {
                $map[$key] = $index;
            }
        }

        if (! isset($map['name'], $map['email'])) {
            throw new \UnexpectedValueException(
                'The CSV must contain Name and Email columns.'
            );
        }

        return $map;
    }

    /**
     * @return array<int, string|null>
     */
    private function readHeader(string $filePath): array
    {
        $handle = fopen($filePath, 'rb');

        if ($handle === false) {
            throw new \RuntimeException('The staged CSV file could not be opened.');
        }

        try {
            $header = fgetcsv($handle, null, ',', '"', '');

            if ($header === false) {
                throw new \UnexpectedValueException('The CSV header could not be read.');
            }

            $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) $header[0]);

            return $header;
        } finally {
            fclose($handle);
        }
    }

    /**
     * @param  array<int, string|null>  $cells
     */
    private function isBlankRow(array $cells): bool
    {
        foreach ($cells as $cell) {
            if (trim((string) $cell) !== '') {
                return false;
            }
        }

        return true;
    }

    private function markFailed(string $message): void
    {
        $import = UserImport::query()->find($this->userImportId);

        if ($import === null || $import->status === UserImport::STATUS_COMPLETED) {
            return;
        }

        $finishedAt = now();

        $import->forceFill([
            'status' => UserImport::STATUS_FAILED,
            'error_message' => $message,
            'finished_at' => $finishedAt,
            'duration_ms' => $import->started_at
                ? max(1, $finishedAt->diffInMilliseconds($import->started_at))
                : null,
        ])->save();

        Log::warning('CSV user import stopped.', [
            'user_import_id' => $this->userImportId,
            'message' => $message,
        ]);
    }

    private function batchSize(): int
    {
        return max(1, min(5000, (int) config('user_imports.batch_size', 1000)));
    }
}
