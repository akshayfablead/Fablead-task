@extends('layouts.app')

@section('content')
    <div class="container-fluid">

        {{-- Page Header --}}
        <div class="mb-4">
            <h1 class="h3 mb-2">
                Accounts & Roles
            </h1>

            <p class="text-muted mb-0">
                Admin accounts and the Admin role are protected.
                Create another Admin account using the server command.
            </p>
        </div>

        {{-- Global AJAX message --}}
        <div id="admin-alert" class="alert d-none" role="alert"></div>

        {{-- ========================================================= --}}
        {{-- Create Account                                             --}}
        {{-- ========================================================= --}}

        <section class="mb-4">
            <div class="card">
                <div class="card-body">
                    <h2 class="h5 mb-3">
                        Create Account
                    </h2>

                    @include('admin.account-form', [
                        'account' => null,
                    ])
                </div>
            </div>
        </section>

        {{-- ========================================================= --}}
        {{-- Existing Accounts                                          --}}
        {{-- ========================================================= --}}

        <section class="mb-4">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <h2 class="h4 mb-0">
                    Accounts
                </h2>

                <span class="text-muted small">
                    {{ $accounts->total() }} account(s)
                </span>
            </div>

            @forelse($accounts as $account)
                <details class="card mb-2">

                    {{-- Account Header --}}
                    <summary class="card-header">
                        <div class="d-flex flex-wrap gap-2 align-items-center">

                            <strong>
                                #{{ $account->id }}
                            </strong>

                            <span>
                                {{ $account->name }}
                            </span>

                            <span class="text-muted">
                                {{ $account->email }}
                            </span>

                            <span class="badge text-bg-secondary">
                                {{ $account->roles->pluck('name')->implode(', ') }}
                            </span>

                        </div>
                    </summary>

                    {{-- Account Body --}}
                    <div class="card-body">

                        @if ($account->isAdmin())
                            <div class="alert alert-info mb-0">
                                <strong>Protected Admin account.</strong>
                                This account has full access and cannot be
                                modified or deleted from this page.
                            </div>
                        @else
                            @include('admin.account-form', [
                                'account' => $account,
                            ])

                            <div class="mt-3">
                                <button type="button" class="btn btn-outline-danger btn-sm admin-delete"
                                    data-url="{{ route('admin.accounts.destroy', $account) }}"
                                    data-message="Delete this account permanently?">
                                    Delete Account
                                </button>
                            </div>
                        @endif

                    </div>
                </details>
            @empty

                <div class="alert alert-secondary">
                    No accounts found.
                </div>
            @endforelse

            {{-- Account Pagination --}}
            @if ($accounts->hasPages())
                <nav class="d-flex justify-content-between align-items-center mt-3" aria-label="Account pagination">
                    <div>
                        @if ($accounts->onFirstPage())
                            <span class="btn btn-sm btn-outline-secondary disabled">
                                Previous
                            </span>
                        @else
                            <a href="{{ $accounts->previousPageUrl() }}" class="btn btn-sm btn-outline-secondary">
                                Previous
                            </a>
                        @endif
                    </div>

                    <span class="text-muted small">
                        Page {{ $accounts->currentPage() }}
                        of {{ $accounts->lastPage() }}
                    </span>

                    <div>
                        @if ($accounts->hasMorePages())
                            <a href="{{ $accounts->nextPageUrl() }}" class="btn btn-sm btn-outline-secondary">
                                Next
                            </a>
                        @else
                            <span class="btn btn-sm btn-outline-secondary disabled">
                                Next
                            </span>
                        @endif
                    </div>
                </nav>
            @endif
        </section>

        {{-- ========================================================= --}}
        {{-- Roles                                                       --}}
        {{-- ========================================================= --}}

        <section class="mt-4">

            <div class="mb-3">
                <h2 class="h4 mb-1">
                    Roles
                </h2>

                <p class="text-muted mb-0">
                    Manage custom roles and their permissions.
                </p>
            </div>

            {{-- Create Role --}}
            <div class="card mb-3">
                <div class="card-body">
                    <h3 class="h5 mb-3">
                        Create Role
                    </h3>

                    @include('admin.role-form', [
                        'role' => null,
                    ])
                </div>
            </div>

            {{-- Existing Roles --}}
            @forelse($roles as $role)
                @if ($role->name !== 'Admin')
                    <details class="card mb-2">

                        {{-- Role Header --}}
                        <summary class="card-header">
                            <div class="d-flex align-items-center gap-2">

                                <strong>
                                    {{ $role->name }}
                                </strong>

                                <span class="badge text-bg-light">
                                    {{ $role->permissions->count() }}
                                    permissions
                                </span>

                            </div>
                        </summary>

                        {{-- Role Body --}}
                        <div class="card-body">

                            @include('admin.role-form', [
                                'role' => $role,
                            ])

                            @if (!in_array($role->name, ['User', 'Manager'], true))
                                <div class="mt-3">
                                    <button type="button" class="btn btn-outline-danger btn-sm admin-delete"
                                        data-url="{{ route('admin.roles.destroy', $role) }}"
                                        data-message="Delete this role permanently?">
                                        Delete Role
                                    </button>
                                </div>
                            @endif

                        </div>
                    </details>
                @endif

            @empty

                <div class="alert alert-secondary">
                    No custom roles found.
                </div>
            @endforelse

        </section>

    </div>
@endsection


@push('scripts')
    <script>
        /**
         * Admin AJAX management.
         *
         * Responsibilities:
         * - Submit account forms.
         * - Submit role forms.
         * - Delete accounts.
         * - Delete roles.
         * - Display validation errors.
         * - Display success/error messages.
         */

        $(function() {

            /*
             * -------------------------------------------------------------
             * Configuration
             * -------------------------------------------------------------
             */

            const adminAlert = $('#admin-alert');

            const csrfToken = document
                .querySelector('meta[name="csrf-token"]')
                ?.getAttribute('content');

            /*
             * -------------------------------------------------------------
             * AJAX Setup
             * -------------------------------------------------------------
             */

            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
            });

            /*
             * -------------------------------------------------------------
             * Display Alert
             * -------------------------------------------------------------
             */

            function showAlert(
                message,
                type = 'success'
            ) {
                adminAlert
                    .removeClass(
                        'd-none alert-success alert-danger alert-warning alert-info'
                    )
                    .addClass(`alert-${type}`)
                    .text(message);
            }

            /*
             * -------------------------------------------------------------
             * Hide Alert
             * -------------------------------------------------------------
             */

            function hideAlert() {
                adminAlert
                    .addClass('d-none')
                    .text('');
            }

            /*
             * -------------------------------------------------------------
             * Clear Form Errors
             * -------------------------------------------------------------
             */

            function clearFormErrors(form) {
                form.find('.form-errors')
                    .empty()
                    .addClass('d-none');

                form.find('.is-invalid')
                    .removeClass('is-invalid');

                form.find('.invalid-feedback')
                    .remove();
            }

            /*
             * -------------------------------------------------------------
             * Display Form Errors
             * -------------------------------------------------------------
             */

            function showFormErrors(
                xhr,
                form
            ) {
                const errorContainer = form.find('.form-errors');

                if (xhr.status !== 422) {
                    errorContainer
                        .removeClass('d-none')
                        .text(
                            xhr.responseJSON?.message ??
                            'Something went wrong.'
                        );

                    return;
                }

                const errors = xhr.responseJSON?.errors ?? {};
                const messages = [];
                Object.entries(errors).forEach(
                    ([field, fieldErrors]) => {

                        fieldErrors.forEach(
                            (message) => {
                                messages.push(message);
                            }
                        );

                        const input = form.find(
                            `[name="${field}"]`
                        );

                        input.addClass('is-invalid');
                    }
                );

                errorContainer
                    .removeClass('d-none')
                    .html(
                        messages.length ?
                        messages.join('<br>') :
                        'Please check the form.'
                    );
            }

            /*
             * -------------------------------------------------------------
             * Handle AJAX Error
             * -------------------------------------------------------------
             */

            function handleAjaxError(
                xhr,
                form = null
            ) {
                if (form) {
                    showFormErrors(xhr, form);
                }

                const message =
                    xhr.responseJSON?.message ??
                    'The request could not be completed.';

                if (!form) {
                    showAlert(
                        message,
                        'danger'
                    );
                }

                console.error(
                    'Admin AJAX error:',
                    xhr
                );
            }

            /*
             * -------------------------------------------------------------
             * Set Button Loading State
             * -------------------------------------------------------------
             */

            function setButtonLoading(
                button,
                loading,
                loadingText
            ) {
                if (loading) {
                    button
                        .data(
                            'original-text',
                            button.text()
                        )
                        .prop('disabled', true)
                        .text(loadingText);
                } else {
                    button
                        .prop('disabled', false)
                        .text(
                            button.data('original-text')
                        );
                }
            }

            /*
             * -------------------------------------------------------------
             * Account / Role Form Submit
             * -------------------------------------------------------------
             */

            $(document).on(
                'submit',
                '.admin-form',
                function(event) {

                    event.preventDefault();

                    const form = $(this);

                    if (form.data('submitting')) {
                        return;
                    }

                    hideAlert();
                    clearFormErrors(form);

                    const button = form.find(
                        'button[type="submit"]'
                    );

                    form.data(
                        'submitting',
                        true
                    );

                    setButtonLoading(
                        button,
                        true,
                        'Saving...'
                    );

                    $.ajax({
                            url: form.attr('action'),
                            method: 'POST',
                            data: form.serialize(),
                        })
                        .done(function(response) {

                            showAlert(
                                response.message ??
                                'Saved successfully.',
                                'success'
                            );

                            /*
                             * Reload after successful mutation.
                             *
                             * This is still an AJAX request:
                             * the mutation itself happens through AJAX.
                             *
                             * Reloading ensures:
                             * - fresh roles
                             * - fresh permissions
                             * - fresh account list
                             * - fresh pagination
                             * - fresh effective access
                             */
                            setTimeout(
                                function() {
                                    window.location.reload();
                                },
                                500
                            );
                        })
                        .fail(function(xhr) {

                            handleAjaxError(
                                xhr,
                                form
                            );
                        })
                        .always(function() {

                            form.data(
                                'submitting',
                                false
                            );

                            setButtonLoading(
                                button,
                                false
                            );
                        });
                }
            );

            /*
             * -------------------------------------------------------------
             * Delete Account / Role
             * -------------------------------------------------------------
             */

            $(document).on(
                'click',
                '.admin-delete',
                function() {

                    const button = $(this);

                    if (button.data('deleting')) {
                        return;
                    }

                    const message =
                        button.data('message') ??
                        'Delete this item permanently?';

                    if (!window.confirm(message)) {
                        return;
                    }

                    hideAlert();

                    button.data(
                        'deleting',
                        true
                    );

                    setButtonLoading(
                        button,
                        true,
                        'Deleting...'
                    );

                    $.ajax({
                            url: button.data('url'),

                            method: 'DELETE',
                        })
                        .done(function(response) {

                            showAlert(
                                response.message ??
                                'Deleted successfully.',
                                'success'
                            );

                            setTimeout(
                                function() {
                                    window.location.reload();
                                },
                                500
                            );
                        })
                        .fail(function(xhr) {

                            handleAjaxError(xhr);
                        })
                        .always(function() {

                            button.data(
                                'deleting',
                                false
                            );

                            setButtonLoading(
                                button,
                                false
                            );
                        });
                }
            );

        });
    </script>
@endpush
