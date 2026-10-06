<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1"
    >

    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Role CRUD</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >
</head>

<body class="bg-light">

<nav class="navbar navbar-dark bg-dark px-3">
    <a class="navbar-brand" href="{{ route('dashboard') }}">
        Role CRUD
    </a>

    @auth
        <div class="d-flex align-items-center gap-3 text-white">
            <span>{{ auth()->user()->name }}</span>

            <form method="POST" action="{{ route('logout') }}">
                @csrf

                <button
                    type="submit"
                    class="btn btn-outline-light btn-sm"
                >
                    Logout
                </button>
            </form>
        </div>
    @endauth
</nav>

<div class="container-fluid py-4">
    <div class="row g-4">
        @auth
            <aside class="col-md-2">
                <div class="list-group">
                    <a
                        class="list-group-item list-group-item-action"
                        href="{{ route('dashboard') }}"
                    >
                        Dashboard
                    </a>

                    <a
                        class="list-group-item list-group-item-action"
                        href="{{ route('records.page') }}"
                    >
                        Records
                    </a>

                    <a
                        class="list-group-item list-group-item-action"
                        href="{{ route('otp.phone') }}"
                    >
                        phone Text SMS
                    </a>

                    @can('manage-system')
                        <a
                            class="list-group-item list-group-item-action"
                            href="{{ route('admin.index') }}"
                        >
                            Accounts & Roles
                        </a>
                    @endcan
                </div>
            </aside>
        @endauth

        <main class="{{ auth()->check() ? 'col-md-10' : 'col-12' }}">
            <div
                id="notice"
                class="alert d-none"
                role="status"
            ></div>

            @yield('content')
        </main>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': document.querySelector(
                'meta[name="csrf-token"]'
            ).content,

            'Accept': 'application/json'
        }
    });

    function notify(message, type = 'success') {
        $('#notice')
            .removeClass('d-none alert-success alert-danger')
            .addClass('alert-' + type)
            .text(message);
    }

    function requestError(xhr, form = null) {
        const body = xhr.responseJSON || {};

        const messages = {
            401: 'Please log in again.',
            403: 'You do not have permission for this action.',
            404: 'Record not found.',
            419: 'Session expired. Refresh the page and log in again.',
            429: 'Too many requests. Please wait and try again.'
        };

        const message = messages[xhr.status]
            || (
                xhr.status >= 500
                    ? 'Server error. Please try again.'
                    : body.message
            )
            || 'Request failed.';

        if (form && xhr.status === 422) {
            const lines = Object.values(body.errors || {}).flat();

            form.find('.form-errors')
                .removeClass('d-none')
                .text(lines.join(' ') || message);
        } else {
            notify(message, 'danger');
        }
    }
</script>

@stack('scripts')

</body>
</html>
