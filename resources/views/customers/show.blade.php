@extends('layouts.app')

@section('content')
    <link rel="stylesheet" href="{{ asset('css/customers.css') }}">

    <section
        class="workspace-surface customer-workspace rounded-3 p-3 p-lg-4"
        data-customer-id="{{ request()->route('id') }}"
        data-customer-show-url="{{ route('api.customers.show', ['id' => '__CUSTOMER_ID__']) }}"
    >
        <div class="d-flex flex-column flex-md-row justify-content-between gap-3 mb-4">
            <div>
                <p class="text-uppercase text-muted-strong fw-semibold small mb-1">
                    CRM
                </p>

                <h1 class="h3 mb-1">
                    Customer Details
                </h1>

                <p class="text-muted-strong mb-0">
                    Loaded from Firestore through the Laravel API.
                </p>
            </div>

            <div class="d-flex flex-wrap gap-2">
                <a
                    href="{{ route('customers.edit', request()->route('id')) }}"
                    class="btn btn-primary"
                >
                    Edit
                </a>

                <a href="{{ route('customers.index') }}" class="btn btn-outline-secondary">
                    Back
                </a>
            </div>
        </div>

        <div
            id="customer-alert"
            class="alert d-none"
            role="status"
        ></div>

        <dl id="customer-details" class="customer-details">
            <div>
                <dt>Name</dt>
                <dd data-field="name">Loading...</dd>
            </div>

            <div>
                <dt>Email</dt>
                <dd data-field="email">Loading...</dd>
            </div>

            <div>
                <dt>Phone</dt>
                <dd data-field="phone">Loading...</dd>
            </div>

            <div>
                <dt>Created</dt>
                <dd data-field="created_at">Loading...</dd>
            </div>

            <div>
                <dt>Updated</dt>
                <dd data-field="updated_at">Loading...</dd>
            </div>
        </dl>
    </section>
@endsection

@push('scripts')
    <script src="{{ asset('js/customers.js') }}"></script>
@endpush
