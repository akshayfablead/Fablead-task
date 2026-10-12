# Local test-user CSV import

This admin-only tool is for local performance testing. It accepts CSV and `.xlsx` workbooks. Uploading stages the file privately; user rows are not inserted until an administrator reviews the row count and clicks **Start background import**.

## Before setup

Confirm that Artisan points at the intended local development database. The current environment was observed as `APP_ENV=local`, loopback MySQL database `role_crud`, with four existing users. No test users were inserted during implementation.

```powershell
php artisan tinker --execute="dump(['environment' => app()->environment(), 'connection' => config('database.default'), 'host' => config('database.connections.'.config('database.default').'.host'), 'database' => DB::connection()->getDatabaseName(), 'queue' => config('queue.default')]);"
```

Do not proceed unless the environment is local and the database name/host are the intended development values. Back up the local database first. The import requires a schema migration; review it and then apply it manually:

```powershell
php artisan migrate
```

This adds import tracking tables and a nullable `users.import_batch_id` marker; it does not insert sample users.

## Generate a sample CSV or Excel workbook

PHP's standard library is sufficient; no package installation or database connection is needed.

```powershell
php scripts\generate_fake_users_csv.php 100000 storage\app\private\fake-users.csv
```

The output has `Name`, `Email`, and `Phone` columns with unique synthetic values under the reserved `example.test` domain. Name and Email are required. Phone is optional but, when supplied, must be unique because of the existing database constraint.

To create a real Excel workbook instead, use an `.xlsx` output name:

```powershell
php scripts\generate_fake_users_csv.php 100000 storage\app\private\fake-users.xlsx
```

Upload either generated file directly. XLSX files are converted to a private staged CSV during upload; the first worksheet is read, including standard shared-string and inline-string cells. The converter streams worksheet rows and rejects malformed workbooks or ZIP archives that expand beyond 100 MB.

## Upload and run

1. Sign into the CRM as an administrator and open **Test User Import** from the sidebar.
   The page's Security checklist summarizes its admin-only access, CSRF-protected upload, private staging, size limits, explicit confirmation, and safe batch processing.
2. Upload the CSV or `.xlsx` workbook. This counts rows and stores a staged CSV on Laravel's private local disk; it does not create users.
3. Review the filename and data-row count on the confirmation page. Click **Start background import** only when you approve the import.
4. In another terminal, run the queue worker:

   ```powershell
   php artisan queue:work database --queue=default --timeout=60 --tries=3 --memory=512
   ```

   Keep the worker running while the import is processing. The current queue configuration uses the database queue with a 90-second retry window; the job timeout is 60 seconds so the worker can safely retry if needed.
5. The import page reports totals, processed/imported/skipped/failed counts, row-level failure reasons, duration, and average imported records per second.
6. Open **Imported Users** from the admin sidebar to search imported and relationship-test users. The table uses server-side DataTables paging and search, and shows related record titles/statuses through the user's `records` relationship. Ordinary CRM accounts are not included; only CSV-imported users have edit/delete actions.

CSV columns are matched case-insensitively. Duplicate email rows (including addresses already in `users`) are skipped. Invalid data and duplicate phone numbers are reported as failures. Existing accounts are never updated. Imported accounts receive a random, undisclosed password and no roles; no notification, API token, or privileged role is created.

The job streams rows and processes at most 1,000 records per transaction by default. Set `USER_IMPORT_BATCH_SIZE` to change the processing chunk (the application caps it at 5,000); SQL insert statements are split further for database parameter limits. A persisted byte-offset checkpoint makes queue retries resume without inserting a chunk twice. On MySQL/MariaDB, case-insensitive duplicate-email checks use the indexed `users.email` column directly (using the configured case-insensitive email collation); other database drivers retain the `LOWER(email)` lookup. The unique email and phone constraints remain the final protection against races. A repeated upload of the same data is safe: its existing emails are skipped. The staged CSV is removed after successful completion; failed/pending imports retain it for review or retry.

## Identify imported data

Each imported user has `users.import_batch_id` equal to the import UUID shown on the import details page. Review the affected rows before any cleanup:

```sql
SELECT id, name, email, phone
FROM users
WHERE import_batch_id = 'IMPORT_UUID';
```

Cleanup is intentionally manual and must target only a reviewed batch UUID. This tool does not delete users. Check for related CRM data and take a backup before considering any deletion.

## Benchmark after import

After the import is complete, run the local-only benchmark command with its batch UUID:

```powershell
php artisan users:benchmark-import IMPORT_UUID
```

It first verifies the local environment and loopback MySQL connection, then confirms the marker count matches the import report. It measures paginated user listing (15 rows with the existing roles relation), a contains-name search, exact email lookup, and a phone-present filter. Output includes elapsed query time, cumulative database query time, and PHP memory delta. It performs read-only queries and recommends no indexes.

These measurements are in-process database timings, not HTTP round trips. To measure actual HTTP response time, sign in as an administrator, open the CRM page in a browser (or an authenticated Postman session), and record the request timing in the browser Network panel/Postman. The current Accounts & Roles page paginates users but does not provide name/email search; the CLI search timings are direct read-only database-query benchmarks. No 100,000-row benchmark numbers are claimed until an administrator approves and runs an import.

The email column already has a unique index, phone is unique, and this feature adds an index only for the import marker. Evaluate any additional name-search index using measured workload before changing the schema.

## Relationship load test

The CRM has a `User hasMany Record` / `Record belongsTo creator` relationship. The admin-only **Imported Users** table displays imported and relationship-test users with their related records through `User::records()`. The existing Records page continues to show the creator relationship. After reviewing the additive migration, apply it manually:

```powershell
php artisan migrate
```

The local-only generator adds a fresh, marked batch without changing existing users or records. For a test dataset of 100,000 synthetic users and 100,000 related records:

```powershell
php artisan load-test:seed-relationships --users=100000 --records=100000 --confirm
```

It is restricted to `APP_ENV=local` and loopback MySQL, creates rows in 500-row chunks, assigns no roles, and prints a batch UUID. Open **Imported Users** as an administrator to page through test users and their related records. The page uses server-side DataTables paging and eager-loads records for only the current page. Example read-only relationship queries:

```php
App\Models\Record::query()
    ->with('creator')
    ->where('load_test_batch_id', 'BATCH_UUID')
    ->paginate(15);

App\Models\User::query()
    ->with('records')
    ->where('load_test_batch_id', 'BATCH_UUID')
    ->paginate(15);
```

Each generated row has a `load_test_batch_id` marker. To remove only one reviewed synthetic batch after testing:

```powershell
php artisan load-test:cleanup-relationships BATCH_UUID --confirm
```

Cleanup removes records before their generated creators, refuses to run for a processing batch or if its users have records outside that batch, and is restricted to local loopback MySQL. Do not run the cleanup if generated accounts have been repurposed.

## Limits and troubleshooting

- CSV and XLSX uploads are limited to 50 MB by application validation; XLSX expanded contents are limited to 100 MB. PHP's `upload_max_filesize` and `post_max_size` must also allow the file size. Check the active PHP configuration with `php --ini`; if necessary, set `upload_max_filesize=16M` and `post_max_size=18M` in the loaded `php.ini`, then restart the local PHP server.
- If the import remains queued, confirm `QUEUE_CONNECTION=database`, that the `jobs` table is migrated, and that the worker is running.
- If `failed` is nonzero, inspect the row-number/reason report before correcting and re-uploading.
- `maatwebsite/excel` is not installed; XLSX ingestion uses PHP's zip and xmlreader extensions to keep this feature dependency-free.
