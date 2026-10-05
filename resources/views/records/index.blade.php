@extends('layouts.app')

@section('content')
    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between mb-3">
                <h1 class="h3">
                    {{ auth()->user()->isAdmin() ? 'All Records' : 'My Records' }}
                </h1>

                @can('create', \App\Models\Record::class)
                    <button type="button" id="add-record" class="btn btn-primary">
                        Add Record
                    </button>
                @endcan
            </div>

            @can('viewAny', \App\Models\Record::class)
                <form id="filters" class="row g-2 mb-3">
                    <div class="col">
                        <input class="form-control" name="q" placeholder="Search title or description"
                            aria-label="Search">
                    </div>

                    <div class="col">
                        <select class="form-select" name="status" aria-label="Status">
                            <option value="">All statuses</option>
                            <option value="draft">Draft</option>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>

                    @if (auth()->user()->isAdmin())
                        <div class="col">
                            <input class="form-control" type="number" min="1" name="creator" placeholder="Creator ID"
                                aria-label="Creator ID">
                        </div>

                        <div class="col">
                            <input class="form-control" name="role" placeholder="Role name" aria-label="Role name">
                        </div>
                    @endif

                    <div class="col-auto">
                        <button type="submit" class="btn btn-outline-primary">
                            Filter
                        </button>
                    </div>
                </form>

                <div id="record-list" aria-live="polite">
                    Loading...
                </div>
            @else
                <div class="alert alert-info">
                    You do not have View permission.
                    If Insert is allowed, you can still add records.
                </div>
            @endcan
        </div>
    </div>

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

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
@push('scripts')
    <script>
        const recordBase = @json(url('/records'));

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

            $('#record-list').text('Loading...');

            listRequest = $.get(
                    @json(route('records.data')),
                    $('#filters').serialize() + '&page=' + page
                )
                .done(function(result) {
                    $('#record-list').html(result.html);
                })
                .fail(function(xhr) {
                    if (xhr.statusText !== 'abort') {
                        $('#record-list').empty();
                        requestError(xhr);
                    }
                });
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

                $.get(recordBase + '/' + button.data('id'))
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

            form.find('.form-errors')
                .addClass('d-none')
                .empty();

            $('#record-save')
                .prop('disabled', true)
                .text('Saving...');

            $.ajax({
                    url: id ? recordBase + '/' + id : recordBase,
                    method: id ? 'PUT' : 'POST',
                    data: form.serialize()
                })
                .done(function(result) {
                    modal.hide();
                    notify(result.message);
                    loadRecords(1);
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

        // Delete a record after confirmation.
        // $(document).on('click', '.record-delete', function() {
        //     if (!confirm('Delete this record permanently?')) {
        //         return;
        //     }

        //     const button = $(this).prop('disabled', true);

        //     $.ajax({
        //             url: recordBase + '/' + button.data('id'),
        //             method: 'DELETE'
        //         })
        //         .done(function(result) {
        //             notify(result.message);
        //             loadRecords(1);
        //         })
        //         .fail(function(xhr) {
        //             requestError(xhr);
        //         })
        //         .always(function() {
        //             button.prop('disabled', false);
        //         });
        // });

        // Delete a record after SweetAlert confirmation.
        $(document).on('click', '.record-delete', function () {
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
            }).then(function (result) {
                if (!result.isConfirmed) {
                    return;
                }
                button.data('deleting', true);
                const originalText = button.text();
                button.prop('disabled', true).text('Deleting...');
                $.ajax({
                    url: recordBase + '/' + button.data('id'),
                    method: 'DELETE'
                })
                .done(function (result) {
                    Swal.fire({
                        title: 'Deleted!',
                        text: result.message,
                        icon: 'success',
                        timer: 1500,
                        showConfirmButton: false
                    });
                    loadRecords(1);
                })
                .fail(function (xhr) {
                    requestError(xhr);
                    Swal.fire({
                        title: 'Delete Failed',
                        text: xhr.responseJSON?.message
                            ?? 'Unable to delete the record.',
                        icon: 'error'
                    });
                })
                .always(function () {
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
