@extends('layouts.app')

@section('content')
    <style>
        .record-workspace .nav-tabs {
            border-bottom-color: var(--app-line);
        }

        .record-workspace .nav-tabs .nav-link {
            color: #475569;
            font-weight: 700;
        }

        .record-workspace .nav-tabs .nav-link.active {
            color: var(--app-accent-dark);
            border-color: var(--app-line) var(--app-line) #fff;
        }

        .sheet-toolbar {
            background: #f8faf8;
            border: 1px solid var(--app-line);
        }

        .sheet-empty {
            border: 1px dashed var(--app-line);
            color: var(--app-muted);
        }

        .sheet-frame-wrap {
            height: min(72vh, 760px);
            min-height: 520px;
            border: 1px solid var(--app-line);
            background: #fff;
            overflow: hidden;
        }

        .sheet-frame {
            display: block;
            width: 100%;
            height: 100%;
            border: 0;
        }
    </style>

    <section class="workspace-surface record-workspace rounded-3 p-3 p-lg-4">
        <div class="d-flex flex-column flex-lg-row justify-content-between gap-3 mb-3">
            <div>
                <p class="text-uppercase text-muted-strong fw-semibold small mb-1">
                    Records Workspace
                </p>

                <h1 class="h3 mb-1">
                    {{ auth()->user()->isAdmin() ? 'All Records' : 'My Records' }}
                </h1>

                <p class="text-muted-strong mb-0">
                    Review local records and synced Google Sheet rows from the
                    same workspace.
                </p>
            </div>


        </div>

        <ul class="nav nav-tabs" id="record-tabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button
                    class="nav-link active"
                    id="records-tab"
                    data-bs-toggle="tab"
                    data-bs-target="#records-panel"
                    type="button"
                    role="tab"
                    aria-controls="records-panel"
                    aria-selected="true"
                >
                    Records
                </button>
            </li>

            <li class="nav-item" role="presentation">
                <button
                    class="nav-link"
                    id="google-sheet-tab"
                    data-bs-toggle="tab"
                    data-bs-target="#google-sheet-panel"
                    type="button"
                    role="tab"
                    aria-controls="google-sheet-panel"
                    aria-selected="false"
                >
                    Google Sheet
                </button>
            </li>
        </ul>

        <div class="tab-content pt-3" id="record-tab-content">
            <div
                class="tab-pane fade show active"
                id="records-panel"
                role="tabpanel"
                aria-labelledby="records-tab"
                tabindex="0"
            >
                @can('viewAny', \App\Models\Record::class)
                    <form id="filters" class="row g-2 align-items-end mb-3">
                        <div class="col-lg-4">
                            <label class="form-label small fw-semibold" for="record-search">
                                Search
                            </label>

                            <input
                                class="form-control"
                                id="record-search"
                                name="q"
                                placeholder="Title or description"
                                aria-label="Search"
                            >
                        </div>

                        <div class="col-md-4 col-lg-2">
                            <label class="form-label small fw-semibold" for="record-filter-status">
                                Status
                            </label>

                            <select
                                class="form-select"
                                id="record-filter-status"
                                name="status"
                                aria-label="Status"
                            >
                                <option value="">All statuses</option>
                                <option value="draft">Draft</option>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>

                        @if (auth()->user()->isAdmin())
                            <div class="col-md-4 col-lg-2">
                                <label class="form-label small fw-semibold" for="record-filter-creator">
                                    Creator ID
                                </label>

                                <input
                                    class="form-control"
                                    id="record-filter-creator"
                                    type="number"
                                    min="1"
                                    name="creator"
                                    placeholder="Any"
                                    aria-label="Creator ID"
                                >
                            </div>

                            <div class="col-md-4 col-lg-2">
                                <label class="form-label small fw-semibold" for="record-filter-role">
                                    Role
                                </label>

                                <input
                                    class="form-control"
                                    id="record-filter-role"
                                    name="role"
                                    placeholder="Any role"
                                    aria-label="Role name"
                                >
                            </div>
                        @endif

                        <div class="col-md-auto">
                            <button type="submit" class="btn btn-outline-primary w-100">
                                Filter
                            </button>
                        </div>
                    </form>

                    <div id="record-list" aria-live="polite">
                        <div class="sheet-empty rounded-3 p-4 text-center">
                            Loading records...
                        </div>
                    </div>
                @else
                    <div class="alert alert-info">
                        You do not have View permission.
                        If Insert is allowed, you can still add records.
                    </div>
                @endcan
            </div>

            <div
                class="tab-pane fade"
                id="google-sheet-panel"
                role="tabpanel"
                aria-labelledby="google-sheet-tab"
                tabindex="0"
            >
                <div class="sheet-toolbar rounded-3 p-3 mb-3 d-flex flex-column flex-md-row justify-content-between gap-2">
                    <div>
                        <div class="fw-semibold">Google Sheet Rows</div>
                        <div class="text-muted-strong small">
                            Live spreadsheet view from your configured Google Sheet.
                        </div>
                    </div>

                    <div class="d-flex flex-wrap gap-2">
                        @if($googleSheetOpenUrl)
                            <a
                                href="{{ $googleSheetOpenUrl }}"
                                target="_blank"
                                rel="noopener"
                                class="btn btn-outline-primary btn-sm"
                            >
                                Open in Google Sheets
                            </a>
                        @endif
                    </div>
                </div>

                <div id="google-sheet-list" aria-live="polite">
                    @if($googleSheetEmbedUrl)
                        <div class="sheet-frame-wrap rounded-3">
                            <iframe
                                id="google-sheet-frame"
                                class="sheet-frame"
                                src="{{ $googleSheetEmbedUrl }}"
                                title="Google Sheet"
                                loading="lazy"
                            ></iframe>
                        </div>
                    @else
                        <div class="sheet-empty rounded-3 p-4 text-center">
                            Google Sheet ID is not configured.
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </section>

    @can('create', \App\Models\Record::class)
        <button type="button" id="add-record" class="btn btn-primary btn-lg app-fab">
            Add Record
        </button>
    @endcan

    {{-- Create, edit and view modal --}}
    <div class="modal fade" id="record-modal" tabindex="-1" aria-labelledby="record-modal-title" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="record-form">
                    <div class="modal-header">
                        <h2 id="record-modal-title" class="modal-title fs-5">
                            Record
                        </h2>

                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <div class="modal-body">
                        <div class="form-errors alert alert-danger d-none" role="alert"></div>

                        <input type="hidden" id="record-id">

                        <label class="form-label" for="record-title">
                            Title
                        </label>

                        <input class="form-control mb-3" id="record-title" name="title" required maxlength="150">

                        <label class="form-label" for="record-description">
                            Description
                        </label>

                        <textarea class="form-control mb-3" id="record-description" name="description" maxlength="5000" rows="4"></textarea>

                        <label class="form-label" for="record-status">
                            Status
                        </label>

                        <select class="form-select" id="record-status" name="status">
                            <option value="draft">Draft</option>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            Close
                        </button>

                        <button type="submit" class="btn btn-primary" id="record-save">
                            Save
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        const recordRoutes = {
            index: @json(route('records.data')),
            store: @json(route('records.store')),
            show: @json(route('records.show', ['record' => '__RECORD_ID__'])),
            update: @json(route('records.update', ['record' => '__RECORD_ID__'])),
            delete: @json(route('records.destroy', ['record' => '__RECORD_ID__']))
        };

        function recordRoute(template, id) {
            return template.replace('__RECORD_ID__', encodeURIComponent(id));
        }

        const modal = new bootstrap.Modal(
            document.getElementById('record-modal')
        );

        let currentPage = 1;
        let listRequest = null;
        let viewOnlyMode = false;

        // Load records table through AJAX.
        function loadRecords(page = currentPage) {
            if (!$('#record-list').length) {
                return;
            }

            currentPage = page;

            // Cancel an older listing request to avoid stale results.
            if (listRequest) {
                listRequest.abort();
            }

            $('#record-list').html(
                '<div class="sheet-empty rounded-3 p-4 text-center">Loading records...</div>'
            );

            listRequest = $.get(
                    recordRoutes.index,
                    $('#filters').serialize() + '&page=' + page
                )
                .done(function(result) {
                    $('#record-list').html(result.html);
                })
                .fail(function(xhr) {
                    if (xhr.statusText !== 'abort') {
                        $('#record-list').html(
                            '<div class="alert alert-danger mb-0">Records could not be loaded.</div>'
                        );
                        requestError(xhr);
                    }
                });
        }

        function refreshGoogleSheetFrame() {
            const frame = document.getElementById('google-sheet-frame');

            if (!frame) {
                return;
            }

            const url = new URL(frame.src);
            url.searchParams.set('refresh', Date.now());
            frame.src = url.toString();
        }

        function showGoogleSheetTab() {
            const sheetTabButton = document.getElementById('google-sheet-tab');

            if (!sheetTabButton) {
                return;
            }

            bootstrap.Tab.getOrCreateInstance(sheetTabButton).show();
        }

        // Prepare an empty, editable form.
        function clearRecordForm() {
            viewOnlyMode = false;

            $('#record-form')[0].reset();
            $('#record-id').val('');
            $('#record-form .form-errors').addClass('d-none').empty();
            $('#record-form [name]').prop('disabled', false);
            $('#record-save').removeClass('d-none').prop('disabled', false)
                .text('Save');
        }

        // Search and filter.
        $('#filters').on('submit', function(e) {
            e.preventDefault();
            loadRecords(1);
        });

        // AJAX pagination.
        $(document).on('click', '.record-page', function() {
            loadRecords(Number($(this).data('page')));
        });

        // Open create modal.
        $('#add-record').on('click', function() {
            clearRecordForm();

            $('#record-modal-title').text('Add Record');

            modal.show();
        });

        // Fetch a record and open edit/view modal.
        $(document).on('click', '.record-edit, .record-view',
            function() {
                const viewOnly = $(this).hasClass('record-view');
                const button = $(this).prop('disabled', true);

                $.get(recordRoute(recordRoutes.show, button.data('id')))
                    .done(function(result) {
                        clearRecordForm();
                        viewOnlyMode = viewOnly;
                        const record = result.data;

                        $('#record-id').val(record.id);
                        $('#record-title').val(record.title);

                        $('#record-description').val(
                            record.description
                        );
                        $('#record-status').val(record.status);
                        $('#record-form [name]').prop(
                            'disabled',
                            viewOnly
                        );
                        $('#record-save').toggleClass(
                            'd-none',
                            viewOnly
                        );
                        $('#record-modal-title').text(
                            viewOnly ? 'View Record' : 'Edit Record'
                        );
                        modal.show();
                    })
                    .fail(function(xhr) {
                        requestError(xhr);
                    })
                    .always(function() {
                        button.prop('disabled', false);
                    });
            }
        );

        // Insert or update a record.
        $('#record-form').on('submit', function(e) {
            e.preventDefault();

            if (
                viewOnlyMode ||
                $('#record-save').prop('disabled')
            ) {
                return;
            }

            const form = $(this);
            const id = $('#record-id').val();
            const isCreate = !id;

            form.find('.form-errors')
                .addClass('d-none')
                .empty();

            $('#record-save')
                .prop('disabled', true)
                .text('Saving...');

            $.ajax({
                    url: id
                        ? recordRoute(recordRoutes.update, id)
                        : recordRoutes.store,
                    method: id ? 'PUT' : 'POST',
                    data: form.serialize()
                })
                .done(function(result) {
                    modal.hide();
                    notify(result.message);
                    loadRecords(1);

                    if (isCreate) {
                        showGoogleSheetTab();
                        refreshGoogleSheetFrame();
                    }
                })
                .fail(function(xhr) {
                    requestError(xhr, form);
                })
                .always(function() {
                    $('#record-save')
                        .prop('disabled', false)
                        .text('Save');
                });
        });

        // Delete a record after SweetAlert confirmation.
        $(document).on('click', '.record-delete', function() {
            const button = $(this);

            if (button.data('deleting')) {
                return;
            }

            Swal.fire({
                title: 'Delete Record?',
                text: 'Delete this record permanently?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Yes, delete it',
                cancelButtonText: 'Cancel',
                reverseButtons: true,
                focusCancel: true
            }).then(function(result) {
                if (!result.isConfirmed) {
                    return;
                }

                button.data('deleting', true);
                const originalText = button.text();
                button.prop('disabled', true).text('Deleting...');

                $.ajax({
                    url: recordRoute(recordRoutes.delete, button.data('id')),
                    method: 'DELETE'
                })
                .done(function(result) {
                    Swal.fire({
                        title: 'Deleted!',
                        text: result.message,
                        icon: 'success',
                        timer: 1500,
                        showConfirmButton: false
                    });
                    loadRecords(1);
                })
                .fail(function(xhr) {
                    requestError(xhr);
                    Swal.fire({
                        title: 'Delete Failed',
                        text: xhr.responseJSON?.message
                            ?? 'Unable to delete the record.',
                        icon: 'error'
                    });
                })
                .always(function() {
                    button.data('deleting', false);
                    button
                        .prop('disabled', false)
                        .text(originalText);
                });
            });
        });

        // Initial table load.
        loadRecords(1);
    </script>
@endpush
