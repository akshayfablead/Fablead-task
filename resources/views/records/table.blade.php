<div class="table-responsive">
    <table class="table table-striped align-middle">
        <thead>
            <tr>
                <th>ID</th>
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
                    <td>{{ $record->id }}</td>

                    <td>{{ $record->title }}</td>

                    <td>{{ $record->status }}</td>

                    @if(auth()->user()->isAdmin())
                        <td>
                            {{ $record->creator->name }}
                            (#{{ $record->created_by }})
                        </td>

                        <td>
                            {{ $record->creator->roles->pluck('name')->implode(', ') }}
                        </td>
                    @endif

                    <td class="text-nowrap">
                        @can('view', $record)
                            <button
                                type="button"
                                class="btn btn-sm btn-outline-info record-view"
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
                        class="text-center py-4"
                    >
                        No records found.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="d-flex align-items-center gap-3">
    <button
        type="button"
        class="btn btn-outline-secondary btn-sm record-page"
        data-page="{{ max(1, $records->currentPage() - 1) }}"
        @disabled($records->onFirstPage())
    >
        Previous
    </button>

    <span>
        Page {{ $records->currentPage() }}
        / {{ $records->lastPage() }}
        · {{ $records->total() }} records
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