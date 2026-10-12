@extends('layouts.app')

@section('content')
<section class="workspace-surface p-4 p-lg-5">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
        <div>
            <p class="text-uppercase small fw-semibold text-success mb-2">Local performance testing</p>
            <h1 class="h2 mb-2">Test user imports</h1>
            <p class="text-muted mb-0">Stage a CSV or Excel file, review its row count, then explicitly start a background import.</p>
        </div>
        <span class="badge text-bg-warning">Admin only</span>
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

    <div class="row g-4">
        <div class="col-lg-7">
            <div class="card h-100 border-0 bg-light">
                <div class="card-body p-4">
                    <h2 class="h5">1. Upload for review</h2>
                    <p class="text-muted">CSV or .xlsx, maximum 50 MB. Uploading stores a private staged file but does not create users.</p>

                    <form method="POST" action="{{ route('admin.user-imports.upload') }}" enctype="multipart/form-data">
                        @csrf
                        <label for="user-import-file" class="form-label">CSV or Excel file</label>
                        <input
                            id="user-import-file"
                            class="form-control mb-3"
                            type="file"
                            name="file"
                            accept=".csv,.xlsx,text/csv,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"
                            required
                        >
                        <button class="btn btn-success" type="submit">Upload and preview</button>
                    </form>

                    <hr class="my-4">
                    <h3 class="h6">Required columns</h3>
                    <code>Name,Email,Phone</code>
                    <p class="small text-muted mt-2 mb-0">Name and Email are required; Phone is optional and must be unique when present. Extra columns are ignored.</p>
                </div>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="alert alert-info h-100 mb-0">
                <h2 class="h6">Security checklist</h2>
                <ul class="small mb-0">
                    <li><strong>Access:</strong> this page and its import actions are admin-only; uploads use CSRF protection.</li>
                    <li><strong>File safety:</strong> CSV/XLSX uploads are limited to 50 MB; XLSX expansion is capped at 100 MB and stored on private local storage.</li>
                    <li><strong>Approval:</strong> uploading only stages the file. Review the row count and explicitly confirm before users are created.</li>
                    <li><strong>Data protection:</strong> existing users are never overwritten; invalid rows are reported, duplicate emails are skipped, and imported accounts get no roles or usable password.</li>
                    <li><strong>Controlled cleanup:</strong> imported accounts carry a batch ID and can be managed from Imported Users without exposing regular accounts to bulk actions.</li>
                    <li><strong>Processing:</strong> a queue worker validates and inserts rows in batches, with checkpoints to make retries safer.</li>
                </ul>
            </div>
        </div>
    </div>

    <div class="mt-5">
        <h2 class="h5 mb-3">Recent imports</h2>
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th scope="col">File</th>
                        <th scope="col">Status</th>
                        <th scope="col">Rows</th>
                        <th scope="col">Imported / skipped / failed</th>
                        <th scope="col">Duration</th>
                        <th scope="col"><span class="visually-hidden">Details</span></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($imports as $import)
                        <tr>
                            <td class="text-break">{{ $import->original_name }}</td>
                            <td><span class="badge text-bg-secondary">{{ ucfirst($import->status) }}</span></td>
                            <td>{{ number_format($import->total_rows) }}</td>
                            <td>{{ number_format($import->imported_rows) }} / {{ number_format($import->skipped_rows) }} / {{ number_format($import->failed_rows) }}</td>
                            <td>{{ $import->duration_ms ? number_format($import->duration_ms / 1000, 2).' s' : '—' }}</td>
                            <td><a class="btn btn-sm btn-outline-success" href="{{ route('admin.user-imports.show', $import) }}">Details</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-muted">No imports yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $imports->links() }}
    </div>
</section>
@endsection
