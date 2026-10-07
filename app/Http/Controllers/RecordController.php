<?php

namespace App\Http\Controllers;

use App\Http\Requests\RecordRequest;
use App\Models\Record;
use App\Services\GoogleSheetsService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class RecordController extends Controller
{
    /**
     * Display the records page.
     */
    public function page(): View
    {
        Gate::authorize('viewAny', Record::class);

        $spreadsheetId = config('services.google_sheets.spreadsheet_id');

        return view('records.index', [
            'googleSheetEmbedUrl' => $spreadsheetId
                ? sprintf(
                    'https://docs.google.com/spreadsheets/d/%s/preview?widget=true&headers=false',
                    rawurlencode($spreadsheetId)
                )
                : null,
            'googleSheetOpenUrl' => $spreadsheetId
                ? sprintf(
                    'https://docs.google.com/spreadsheets/d/%s/edit',
                    rawurlencode($spreadsheetId)
                )
                : null,
        ]);
    }

    /**
     * Return the filtered records table.
     */
    public function index(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', Record::class);

        $filters = $this->validateFilters($request);

        $records = $this->buildRecordQuery(
            $request,
            $filters
        )
            ->latest('id')
            ->paginate(10)
            ->withQueryString();

        return response()->json([
            'html' => view('records.table', [
                'records' => $records,
            ])->render(),
        ]);
    }

    /**
     * Display a single record.
     */
    public function show(Record $record): JsonResponse
    {
        Gate::authorize('view', $record);

        return response()->json([
            'data' => $record->only([
                'id',
                'title',
                'description',
                'status',
            ]),
        ]);
    }

    /**
     * Store a new record.
     */
    public function store(RecordRequest $request): JsonResponse
    {
        $record = $request->user()
            ->records()
            ->create($request->validated());

        try {
            app(GoogleSheetsService::class)->append(
                'Sheet1!A:F',
                [
                    $record->id,
                    $record->title,
                    $record->description,
                    $record->status,
                    $request->user()->name,
                    $record->created_at?->toDateTimeString(),
                ]
            );
        } catch (\Throwable $exception) {
            Log::warning('Record could not be appended to Google Sheet.', [
                'record_id' => $record->id,
                'message' => $exception->getMessage(),
            ]);
        }

        return response()->json([
            'message' => 'Record created successfully.',
        ], 201);
    }

    /**
     * Update an existing record.
     */
    public function update(
        RecordRequest $request,
        Record $record
    ): JsonResponse {
        Gate::authorize('update', $record);

        $record->update(
            $request->validated()
        );

        return response()->json([
            'message' => 'Record updated successfully.',
        ]);
    }

    /**
     * Delete an existing record.
     */
    public function destroy(Record $record): JsonResponse
    {
        Gate::authorize('delete', $record);

        $record->delete();

        return response()->json([
            'message' => 'Record deleted successfully.',
        ]);
    }

    /**
     * Validate record filters.
     */
    private function validateFilters(
        Request $request
    ): array {
        return $request->validate([
            'q' => [
                'nullable',
                'string',
                'max:150',
            ],

            'status' => [
                'nullable',
                Rule::in([
                    'draft',
                    'active',
                    'inactive',
                ]),
            ],

            'creator' => [
                'nullable',
                'integer',
                'min:1',
            ],

            'role' => [
                'nullable',
                'string',
                'max:100',
            ],
        ]);
    }

    /**
     * Build the records query.
     */
    private function buildRecordQuery(
        Request $request,
        array $filters
    ): Builder {
        $user = $request->user();

        return Record::query()
            ->visibleTo($user)
            ->with([
                'creator.roles',
            ])

            /*
             * Search by title or description.
             */
            ->when(
                $filters['q'] ?? null,
                function (
                    Builder $query,
                    string $search
                ): void {
                    $search = '%' . $search . '%';

                    $query->where(function (
                        Builder $query
                    ) use ($search): void {
                        $query
                            ->where(
                                'title',
                                'like',
                                $search
                            )
                            ->orWhere(
                                'description',
                                'like',
                                $search
                            );
                    });
                }
            )

            /*
             * Filter by status.
             */
            ->when(
                $filters['status'] ?? null,
                function (
                    Builder $query,
                    string $status
                ): void {
                    $query->where(
                        'status',
                        $status
                    );
                }
            )

            /*
             * Admin-only creator filter.
             */
            ->when(
                $user->isAdmin()
                && ($filters['creator'] ?? null),
                function (Builder $query) use ($filters): void {
                    $query->where(
                        'created_by',
                        $filters['creator']
                    );
                }
            )

            /*
             * Admin-only role filter.
             */
            ->when(
                $user->isAdmin()
                && ($filters['role'] ?? null),
                function (Builder $query) use ($filters): void {
                    $query->whereHas(
                        'creator.roles',
                        function (
                            Builder $query
                        ) use ($filters): void {
                            $query->where(
                                'name',
                                $filters['role']
                            );
                        }
                    );
                }
            );
    }
}

