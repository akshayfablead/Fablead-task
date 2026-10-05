@extends('layouts.app')

@section('content')
    <div class="card">
        <div class="card-body">
            <h1 class="h3">
                {{ auth()->user()->getRoleNames()->implode(', ') ?: 'Account' }}
                Dashboard
            </h1>

            <p>
                Welcome, {{ auth()->user()->name }}.
            </p>

            @if(auth()->user()->isAdmin())
                <p>
                    You can manage all records, accounts, and roles.
                </p>
            @else
                <p>
                    You can access your own records according to
                    your assigned permissions.
                </p>
            @endif

            <a
                href="{{ route('records.page') }}"
                class="btn btn-primary"
            >
                Open Records
            </a>

            @can('manage-system')
                <a
                    href="{{ route('admin.index') }}"
                    class="btn btn-outline-primary"
                >
                    Manage Accounts & Roles
                </a>
            @endcan
        </div>
    </div>
@endsection