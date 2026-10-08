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
                    Customers
                </h1>

                <p class="text-muted-strong mb-0">
                    Customer data is loaded from Firebase Firestore through the Laravel API.
                </p>
            </div>

            <div class="d-flex align-items-start">
                <a href="{{ route('customers.create') }}" class="btn btn-primary">
                    Add Customer
                </a>
            </div>
        </div>

        <div
            id="customer-alert"
            class="alert d-none"
            role="status"
        ></div>

        <div class="customer-table-wrap">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Created</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>

                    <tbody id="customers-table-body">
                        <tr>
                            <td colspan="5" class="text-center text-muted-strong py-4">
                                Loading customers...
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </section>
@endsection

@push('scripts')
    <script src="{{ asset('js/api.js') }}"></script>
    <script src="{{ asset('js/customers.js') }}"></script>
@endpush
