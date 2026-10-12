@extends('layouts.app')

@section('content')
<section
    class="workspace-surface p-4 p-lg-5"
    @if (in_array($import->status, ['queued', 'processing'], true))
        data-import-status-url="{{ route('admin.user-imports.status', $import) }}"
    @endif
>
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
        <div>
            <p class="text-uppercase small fw-semibold text-success mb-2">CSV import</p>
            <h1 class="h2 mb-2">Review import</h1>
            <p class="text-muted mb-0 text-break">{{ $import->original_name }}</p>
        </div>
        <a href="{{ route('admin.user-imports.index') }}" class="btn btn-outline-secondary">All imports</a>
    </div>

    @if (session('status'))
        <div class="alert alert-success" role="status">{{ session('status') }}</div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger" role="alert">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if (in_array($import->status, ['pending', 'failed'], true))
        <div class="alert alert-warning">
            <h2 class="h6">{{ $import->status === 'failed' ? 'Retry the remaining rows' : '2. Confirm before importing' }}</h2>
            <p class="mb-3">
                The staged CSV contains <strong>{{ number_format($import->total_rows) }}</strong> non-empty data rows.
                {{ $import->imported_rows ? number_format($import->imported_rows).' rows are already marked as imported; checkpointing prevents them from being inserted again.' : 'No users have been created yet.' }}
            </p>
            @if ($import->error_message)
                <p class="small mb-3"><strong>Last error:</strong> {{ $import->error_message }}</p>
            @endif
            <form method="POST" action="{{ route('admin.user-imports.confirm', $import) }}">
                @csrf
                <button class="btn btn-success" type="submit">
                    {{ $import->status === 'failed' ? 'Retry background import' : 'Start background import' }}
                </button>
            </form>
        </div>
    @endif

    <div class="row g-3 mb-4" aria-live="polite">
        @foreach ([
            'Status' => ucfirst($import->status),
            'Total rows' => number_format($import->total_rows),
            'Processed' => number_format($import->processed_rows),
            'Imported' => number_format($import->imported_rows),
            'Skipped' => number_format($import->skipped_rows),
            'Failed' => number_format($import->failed_rows),
        ] as $label => $value)
            <div class="col-6 col-lg-2">
                <div class="border rounded p-3 h-100">
                    <div class="small text-muted">{{ $label }}</div>
                    <strong data-stat="{{ \Illuminate\Support\Str::slug($label) }}">{{ $value }}</strong>
                </div>
            </div>
        @endforeach
    </div>

    <div class="progress mb-2" role="progressbar" aria-label="Import progress" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $import->total_rows ? (int) round($import->processed_rows / $import->total_rows * 100) : 0 }}">
        <div
            class="progress-bar bg-success"
            data-progress
            style="width: {{ $import->total_rows ? (int) round($import->processed_rows / $import->total_rows * 100) : 0 }}%"
        ></div>
    </div>
    <p class="small text-muted mb-4" data-throughput>
        Duration: {{ $import->duration_ms ? number_format($import->duration_ms / 1000, 2).' s' : 'Not started' }}
        · Average: {{ $import->duration_ms ? number_format($import->imported_rows / max($import->duration_ms / 1000, 0.001), 2).' records/s' : '—' }}
    </p>

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="h5 mb-0">Failed rows</h2>
        <span class="small text-muted">Email addresses are not stored in the failure report.</span>
    </div>
    <div class="table-responsive">
        <table class="table table-sm table-striped">
            <thead>
                <tr><th scope="col">CSV row</th><th scope="col">Reason</th></tr>
            </thead>
            <tbody>
                @forelse ($failures as $failure)
                    <tr><td>{{ $failure->row_number }}</td><td>{{ $failure->reason }}</td></tr>
                @empty
                    <tr><td colspan="2" class="text-muted">No failed rows reported.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $failures->links() }}
</section>

@if (in_array($import->status, ['queued', 'processing'], true))
    <script>
        (() => {
            const section = document.querySelector('[data-import-status-url]');
            const labels = {
                status: 'Status',
                'total-rows': 'Total rows',
                processed: 'Processed',
                imported: 'Imported',
                skipped: 'Skipped',
                failed: 'Failed',
            };

            const refresh = async () => {
                const response = await fetch(section.dataset.importStatusUrl, {
                    headers: { Accept: 'application/json' },
                    credentials: 'same-origin',
                });

                if (!response.ok) {
                    throw new Error(`Progress request failed (${response.status}).`);
                }

                const result = await response.json();
                Object.entries(labels).forEach(([key, label]) => {
                    const element = section.querySelector(`[data-stat="${key}"]`);
                    if (element) {
                        const value = result[key.replaceAll('-', '_')];
                        element.textContent = typeof value === 'number'
                            ? new Intl.NumberFormat().format(value)
                            : value;
                    }
                });

                const percent = result.total_rows
                    ? Math.round(result.processed_rows / result.total_rows * 100)
                    : 0;
                const progress = section.querySelector('[data-progress]');
                progress.style.width = `${percent}%`;
                progress.parentElement.setAttribute('aria-valuenow', String(percent));
                const seconds = result.duration_ms ? result.duration_ms / 1000 : 0;
                section.querySelector('[data-throughput]').textContent =
                    `Duration: ${seconds ? seconds.toFixed(2) + ' s' : 'Starting'} · Average: ${result.records_per_second ?? '—'} records/s`;

                if (['completed', 'failed'].includes(result.status)) {
                    window.location.reload();
                    return;
                }

                window.setTimeout(() => refresh().catch(error => {
                    console.error('Unable to refresh CSV import progress.', error);
                    section.querySelector('[data-throughput]').textContent =
                        'Progress refresh failed. Reload this page to try again.';
                }), 3000);
            };

            window.setTimeout(() => refresh().catch(error => {
                console.error('Unable to refresh CSV import progress.', error);
                section.querySelector('[data-throughput]').textContent =
                    'Progress refresh failed. Reload this page to try again.';
            }), 3000);
        })();
    </script>
@endif
@endsection
