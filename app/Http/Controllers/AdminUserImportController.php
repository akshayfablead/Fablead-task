<?php

namespace App\Http\Controllers;

use App\Jobs\ImportUsersCsv;
use App\Models\UserImport;
use App\Services\XlsxToCsvConverter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Throwable;

class AdminUserImportController extends Controller
{
    public function index(): View
    {
        return view('admin.user-imports.index', [
            'imports' => UserImport::query()
                ->withCount('failures')
                ->latest()
                ->paginate(20),
        ]);
    }

    public function upload(Request $request, XlsxToCsvConverter $xlsxConverter): RedirectResponse
    {
        $request->validate([
            'file' => [
                'required',
                'file',
                'max:'.config('user_imports.max_file_kb', 51200),
            ],
        ]);

        /** @var UploadedFile $file */
        $file = $request->file('file');
        $extension = strtolower($file->getClientOriginalExtension());

        if (! in_array($extension, ['csv', 'xlsx'], true)) {
            return back()
                ->withErrors(['file' => 'Upload a .csv or .xlsx file.'])
                ->withInput();
        }

        $id = (string) Str::uuid();
        $path = "user-imports/{$id}.csv";
        $disk = Storage::disk('local');
        $disk->makeDirectory('user-imports');

        try {
            if ($extension === 'xlsx') {
                try {
                    $xlsxConverter->convert($file->getRealPath(), $disk->path($path));
                } catch (\RuntimeException $exception) {
                    throw ValidationException::withMessages([
                        'file' => $exception->getMessage(),
                    ]);
                }
            } else {
                $storedPath = $disk->putFileAs('user-imports', $file, "{$id}.csv");

                if ($storedPath === false) {
                    throw new \RuntimeException('The CSV file could not be stored privately.');
                }
            }

            [$totalRows, $originalName] = $this->inspectCsv(
                $disk->path($path),
                $file->getClientOriginalName()
            );

            if ($totalRows === 0) {
                $disk->delete($path);

                return back()
                    ->withErrors(['file' => 'The file contains no non-empty data rows.'])
                    ->withInput();
            }

            $import = UserImport::query()->create([
                'id' => $id,
                'uploaded_by' => $request->user()->getKey(),
                'original_name' => $originalName,
                'file_path' => $path,
                'status' => UserImport::STATUS_PENDING,
                'total_rows' => $totalRows,
            ]);
        } catch (Throwable $exception) {
            $disk->delete($path);

            throw $exception;
        }

        return redirect()
            ->route('admin.user-imports.show', $import)
            ->with('status', 'File validated for review. No users have been imported yet.');
    }

    public function show(UserImport $userImport): View
    {
        return view('admin.user-imports.show', [
            'import' => $userImport->loadCount('failures'),
            'failures' => $userImport->failures()
                ->latest('row_number')
                ->paginate(100),
        ]);
    }

    public function confirm(Request $request, UserImport $userImport): RedirectResponse
    {
        if (config('queue.default') === 'sync') {
            return back()->withErrors([
                'import' => 'Set QUEUE_CONNECTION=database and run a queue worker before starting a large import.',
            ]);
        }

        $started = DB::transaction(function () use ($userImport): bool {
            $lockedImport = UserImport::query()
                ->whereKey($userImport->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if (! in_array($lockedImport->status, [
                UserImport::STATUS_PENDING,
                UserImport::STATUS_FAILED,
            ], true)) {
                return false;
            }

            $lockedImport->forceFill([
                'status' => UserImport::STATUS_QUEUED,
                'error_message' => null,
                'finished_at' => null,
                'duration_ms' => null,
            ])->save();

            return true;
        });

        if (! $started) {
            return back()->withErrors([
                'import' => 'This import has already been confirmed or processed.',
            ]);
        }

        try {
            ImportUsersCsv::dispatch($userImport->getKey());
        } catch (Throwable $exception) {
            UserImport::query()
                ->whereKey($userImport->getKey())
                ->where('status', UserImport::STATUS_QUEUED)
                ->update(['status' => $userImport->status]);

            throw $exception;
        }

        return redirect()
            ->route('admin.user-imports.show', $userImport)
            ->with('status', 'Import queued. Progress will update while the worker runs.');
    }

    public function status(UserImport $userImport): JsonResponse
    {
        $elapsedMs = $userImport->duration_ms
            ?? ($userImport->started_at
                ? max(1, $userImport->started_at->diffInMilliseconds(now()))
                : null);

        return response()->json([
            'id' => $userImport->getKey(),
            'status' => $userImport->status,
            'error_message' => $userImport->error_message,
            'total_rows' => $userImport->total_rows,
            'processed_rows' => $userImport->processed_rows,
            'imported_rows' => $userImport->imported_rows,
            'skipped_rows' => $userImport->skipped_rows,
            'failed_rows' => $userImport->failed_rows,
            'duration_ms' => $elapsedMs,
            'records_per_second' => $elapsedMs && $elapsedMs > 0
                ? round($userImport->imported_rows / ($elapsedMs / 1000), 2)
                : null,
        ]);
    }

    /**
     * @return array{int, string}
     */
    private function inspectCsv(string $path, string $uploadedName): array
    {
        $handle = fopen($path, 'rb');

        if ($handle === false) {
            throw new \RuntimeException('The staged import file could not be read.');
        }

        try {
            $header = fgetcsv($handle, null, ',', '"', '');

            if ($header === false || $header === []) {
                throw ValidationException::withMessages([
                    'file' => 'The file header could not be read.',
                ]);
            }

            $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) $header[0]);
            $normalized = array_map(
                fn ($column): string => mb_strtolower(trim((string) $column)),
                $header
            );

            if (in_array('', $normalized, true)
                || count(array_unique($normalized)) !== count($normalized)) {
                throw ValidationException::withMessages([
                    'file' => 'Column names must be non-empty and unique.',
                ]);
            }

            if (! in_array('name', $normalized, true)
                || ! in_array('email', $normalized, true)) {
                throw ValidationException::withMessages([
                    'file' => 'The file must contain Name and Email columns. Phone is optional.',
                ]);
            }

            $count = 0;

            while (($row = fgetcsv($handle, null, ',', '"', '')) !== false) {
                foreach ($row as $value) {
                    if (trim((string) $value) !== '') {
                        $count++;
                        break;
                    }
                }
            }

            $originalName = basename(str_replace('\\', '/', $uploadedName));

            return [$count, Str::limit($originalName, 255, '')];
        } finally {
            fclose($handle);
        }
    }
}
