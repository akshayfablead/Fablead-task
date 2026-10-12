@extends('layouts.app')

@push('styles')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css">
@endpush

@section('content')
    <section class="workspace-surface p-4 p-lg-5">
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
            <div>
                <p class="text-uppercase small fw-semibold text-success mb-2">Imported and relationship test accounts</p>
                <h1 class="h2 mb-2">Imported users</h1>
            </div>
            <span class="badge text-bg-secondary">{{ number_format($testUserCount) }} test users</span>
        </div>

        <div id="imported-users-alert" class="alert d-none" role="alert"></div>

        <div class="table-responsive">
            <table id="imported-users-table" class="table table-striped table-hover align-middle w-100">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Type</th>
                        <th>Related records</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
            </table>
        </div>
    </section>

    <div class="modal fade" id="edit-imported-user-modal" tabindex="-1" aria-labelledby="edit-imported-user-title"
        aria-hidden="true">
        <div class="modal-dialog">
            <form id="edit-imported-user-form" class="modal-content">
                @csrf
                <input type="hidden" name="_method" value="PUT">
                <div class="modal-header">
                    <h2 class="modal-title fs-5" id="edit-imported-user-title">Edit imported user</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-danger d-none" data-form-errors role="alert"></div>
                    <label class="form-label" for="imported-user-name">Name</label>
                    <input class="form-control mb-3" id="imported-user-name" name="name" maxlength="255" required>

                    <label class="form-label" for="imported-user-email">Email</label>
                    <input class="form-control mb-3" id="imported-user-email" name="email" type="email" maxlength="255"
                        required>

                    <label class="form-label" for="imported-user-phone">Phone</label>
                    <input class="form-control" id="imported-user-phone" name="phone" maxlength="255">
                    <input type="hidden" name="user_id">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Save changes</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>
    <script>
        $(function() {
            const tableElement = $('#imported-users-table');
            const alertElement = $('#imported-users-alert');
            const modalElement = document.getElementById('edit-imported-user-modal');
            const editModal = new bootstrap.Modal(modalElement);
            const form = $('#edit-imported-user-form');
            const dataUrl = @json(route('admin.imported-users.data'));
            const updateUrlTemplate = @json(route('admin.imported-users.update', ['user' => '__USER__']));
            const deleteUrlTemplate = @json(route('admin.imported-users.destroy', ['user' => '__USER__']));

            const showAlert = (message, type) => {
                alertElement.removeClass('d-none alert-success alert-danger')
                    .addClass(`alert-${type}`)
                    .text(message);
            };

            const escapeHtml = value => $('<div>').text(value ?? '').html();
            $.fn.dataTable.ext.errMode = 'none';
            tableElement.on('error.dt', function() {
                showAlert(
                    'Could not load the users table. Check the database connection and available disk space, then reload.',
                    'danger'
                );
            });
            tableElement.on('xhr.dt', function(_event, _settings, json) {
                if (json) {
                    alertElement.addClass('d-none').text('');
                }
            });

            const table = tableElement.DataTable({
                processing: true,
                serverSide: true,
                searchDelay: 350,
                pageLength: 10,
                lengthMenu: [10, 25, 50, 100],
                order: [
                    [0, 'desc']
                ],
                ajax: {
                    url: dataUrl,
                    type: 'GET'
                },
                columns: [{
                        data: 'id',
                        searchable: false
                    },
                    {
                        data: 'name',
                        render: escapeHtml
                    },
                    {
                        data: 'email',
                        render: escapeHtml
                    },
                    {
                        data: 'is_imported_user',
                        searchable: false,
                        render: value => value ? 'Imported' : 'Relationship test'
                    },
                    {
                        data: 'records',
                        searchable: false,
                        orderable: false,
                        render: (records, type, row) => {
                            if (!records?.length) {
                                return '<span class="text-muted">No records</span>';
                            }

                            const items = records.map(record =>
                                `<div><span>${escapeHtml(record.title)}</span> <span class="text-muted">(${escapeHtml(record.status)})</span></div>`
                            ).join('');

                            return `<div><div class="small text-muted mb-1">${Number(row.records_count)} record(s)</div>${items}</div>`;
                        }
                    },
                    {
                        data: 'created_at',
                        searchable: false,
                        render: value => escapeHtml(value)
                    },
                    {
                        data: null,
                        searchable: false,
                        orderable: false,
                        render: row => row.is_imported_user ?
                            `<button type="button" class="btn btn-sm btn-outline-primary me-1 edit-imported-user" data-user-id="${Number(row.id)}">Edit</button><button type="button" class="btn btn-sm btn-outline-danger delete-imported-user" data-user-id="${Number(row.id)}">Delete</button>` :
                            '<span class="text-muted">Test data</span>',
                    },
                ],
                language: {
                    emptyTable: 'No imported test users are available.',
                    zeroRecords: 'No matching imported users found.',
                },
            });

            tableElement.on('click', '.edit-imported-user', function() {
                const row = table.row($(this).closest('tr')).data();
                if (!row) return;

                form[0].reset();
                form.find('[name="user_id"]').val(row.id);
                form.find('[name="name"]').val(row.name);
                form.find('[name="email"]').val(row.email);
                form.find('[name="phone"]').val(row.phone ?? '');
                form.find('[data-form-errors]').addClass('d-none').empty();
                editModal.show();
            });

            form.on('submit', function(event) {
                event.preventDefault();
                const userId = form.find('[name="user_id"]').val();
                const button = form.find('[type="submit"]');
                button.prop('disabled', true);

                $.ajax({
                    url: updateUrlTemplate.replace('__USER__', encodeURIComponent(userId)),
                    method: 'POST',
                    data: form.serialize(),
                }).done(response => {
                    editModal.hide();
                    showAlert(response.message, 'success');
                    table.ajax.reload(null, false);
                }).fail(xhr => {
                    const messages = Object.values(xhr.responseJSON?.errors ?? {}).flat();
                    const message = messages.length ?
                        messages.join(' ') :
                        (xhr.responseJSON?.message ?? 'Could not update this imported user.');
                    form.find('[data-form-errors]').removeClass('d-none').text(message);
                }).always(() => button.prop('disabled', false));
            });

            tableElement.on('click', '.delete-imported-user', function() {
                const button = $(this);
                const userId = button.data('user-id');
                const row = table.row(button.closest('tr')).data();

                if (button.data('deleting')) return;

                Swal.fire({
                    title: 'Delete imported user?',
                    text: `Delete ${row?.name ?? 'this test user'} permanently? This cannot be undone.`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, delete it',
                    cancelButtonText: 'Cancel',
                    confirmButtonColor: '#dc3545',
                    reverseButtons: true,
                    focusCancel: true,
                }).then(result => {
                    if (!result.isConfirmed) return;

                    button.data('deleting', true).prop('disabled', true);
                    $.ajax({
                        url: deleteUrlTemplate.replace('__USER__', encodeURIComponent(
                            userId)),
                        method: 'DELETE',
                    }).done(response => {
                        table.ajax.reload(null, false);
                        Swal.fire({
                            title: 'Deleted!',
                            text: response.message,
                            icon: 'success',
                            timer: 1500,
                            showConfirmButton: false,
                        });
                    }).fail(xhr => {
                        Swal.fire({
                            title: 'Delete failed',
                            text: xhr.responseJSON?.message ??
                                'Could not delete this imported user.',
                            icon: 'error',
                        });
                    }).always(() => {
                        button.data('deleting', false).prop('disabled', false);
                    });
                });
            });
        });
    </script>
@endpush
