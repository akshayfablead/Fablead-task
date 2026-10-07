<div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
            <tr>
                <th>Index</th>
                <th>Title</th>
                <th>Status</th>

                @if(auth()->user()->isAdmin())
                    <th>Creator</th>
                    <th>Role</th>
                @endif

                <th>Actions</th>
            </tr>
        </thead>

        <tbody>
            @forelse($records as $record)
                <tr>
                    <td class="text-muted-strong fw-semibold">
                        {{ $records->firstItem() + $loop->index }}
                    </td>

                    <td>
                        <div class="fw-semibold">{{ $record->title }}</div>
                        <div class="small text-muted-strong">
                            ID #{{ $record->id }}
                        </div>
                    </td>

                    <td>
                        <span class="badge text-bg-{{ $record->status === 'active' ? 'success' : ($record->status === 'inactive' ? 'secondary' : 'warning') }}">
                            {{ ucfirst($record->status) }}
                        </span>
                    </td>

                    @if(auth()->user()->isAdmin())
                        <td>
                            {{ $record->creator?->name ?? 'Unknown' }}
                            (#{{ $record->created_by }})
                        </td>

                        <td>
                            {{ $record->creator?->roles?->pluck('name')->implode(', ') ?: 'No Role' }}
                        </td>
                    @endif

                    <td class="text-nowrap">
                        @can('view', $record)
                            <button
                                type="button"
                                class="btn btn-sm btn-outline-secondary record-view"
                                data-id="{{ $record->id }}"
                            >
                                View
                            </button>
                        @endcan

                        @can('update', $record)
                            <button
                                type="button"
                                class="btn btn-sm btn-outline-primary record-edit"
                                data-id="{{ $record->id }}"
                            >
                                Edit
                            </button>
                        @endcan

                        @can('delete', $record)
                            <button
                                type="button"
                                class="btn btn-sm btn-outline-danger record-delete"
                                data-id="{{ $record->id }}"
                            >
                                Delete
                            </button>
                        @endcan
                    </td>
                </tr>
            @empty
                <tr>
                    <td
                        colspan="{{ auth()->user()->isAdmin() ? 6 : 4 }}"
                        class="text-center text-muted-strong py-4"
                    >
                        No records found.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="d-flex align-items-center gap-3 border-top pt-3 mt-3">
    <button
        type="button"
        class="btn btn-outline-secondary btn-sm record-page"
        data-page="{{ max(1, $records->currentPage() - 1) }}"
        @disabled($records->onFirstPage())
    >
        Previous
    </button>

    <span class="text-muted-strong small">
        Page {{ $records->currentPage() }}
        / {{ $records->lastPage() }}
        &middot; {{ $records->total() }} records
    </span>

    <button
        type="button"
        class="btn btn-outline-secondary btn-sm record-page"
        data-page="{{ $records->currentPage() + 1 }}"
        @disabled(!$records->hasMorePages())
    >
        Next
    </button>
</div>
