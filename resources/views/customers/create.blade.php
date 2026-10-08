@extends('layouts.app')

@section('content')
    <link rel="stylesheet" href="{{ asset('css/customers.css') }}">

    <section class="workspace-surface customer-workspace rounded-3 p-3 p-lg-4">
        <div class="d-flex flex-column flex-md-row justify-content-between gap-3 mb-4">
            <div>
                <p class="text-uppercase text-muted-strong fw-semibold small mb-1">
                    CRM
                </p>

                <h1 class="h3 mb-1">
                    Add Customer
                </h1>

                <p class="text-muted-strong mb-0">
                    The form submits with JavaScript to the Laravel Customer API.
                </p>
            </div>

            <div>
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

        <form id="customer-form" class="customer-form" data-mode="create">
            <div class="form-errors alert alert-danger d-none" role="alert"></div>

            <div class="mb-3">
                <label for="customer-name" class="form-label">
                    Name
                </label>

                <input
                    id="customer-name"
                    class="form-control"
                    name="name"
                    maxlength="100"
                    required
                >
            </div>

            <div class="mb-3">
                <label for="customer-email" class="form-label">
                    Email
                </label>

                <input
                    id="customer-email"
                    class="form-control"
                    name="email"
                    type="email"
                    maxlength="255"
                    required
                >
            </div>

            <div class="mb-4">
                <label for="customer-phone" class="form-label">
                    Phone
                </label>

                <input
                    id="customer-phone"
                    class="form-control"
                    name="phone"
                    maxlength="20"
                    required
                >
            </div>

            <button id="customer-save" type="submit" class="btn btn-primary">
                Add Customer
            </button>
        </form>
    </section>
@endsection

@push('scripts')
    <script src="{{ asset('js/api.js') }}"></script>
    <script src="{{ asset('js/customers.js') }}"></script>
@endpush
