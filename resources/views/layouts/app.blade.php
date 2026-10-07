<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1"
    >

    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Fablead Task</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

<style>
    :root {
        --app-bg: #f5f7f4;
        --app-ink: #1f2933;
        --app-muted: #64748b;
        --app-line: #d9e2dc;
        --app-accent: #246b5f;
        --app-accent-dark: #17483f;
        --app-warm: #b7791f;
    }

    body {
        background: var(--app-bg);
        color: var(--app-ink);
    }

    .app-navbar {
        background: #17201d;
        border-bottom: 3px solid var(--app-accent);
    }

    .app-shell {
        max-width: 1480px;
    }

    .app-sidebar .list-group {
        border: 1px solid var(--app-line);
        box-shadow: 0 10px 28px rgba(31, 41, 51, .06);
    }

    .app-sidebar .list-group-item {
        border-color: var(--app-line);
        color: #334155;
        font-weight: 600;
    }

    .app-sidebar .list-group-item:hover,
    .app-sidebar .list-group-item:focus {
        background: #edf5f1;
        color: var(--app-accent-dark);
    }

    .app-sidebar .list-group-item.active {
        background: var(--app-accent);
        border-color: var(--app-accent);
    }

    .workspace-surface {
        background: #fff;
        border: 1px solid var(--app-line);
        box-shadow: 0 14px 36px rgba(31, 41, 51, .07);
    }

    .text-muted-strong {
        color: var(--app-muted);
    }

    .btn-primary {
        --bs-btn-bg: var(--app-accent);
        --bs-btn-border-color: var(--app-accent);
        --bs-btn-hover-bg: var(--app-accent-dark);
        --bs-btn-hover-border-color: var(--app-accent-dark);
        --bs-btn-active-bg: var(--app-accent-dark);
        --bs-btn-active-border-color: var(--app-accent-dark);
    }

    .btn-outline-primary {
        --bs-btn-color: var(--app-accent);
        --bs-btn-border-color: var(--app-accent);
        --bs-btn-hover-bg: var(--app-accent);
        --bs-btn-hover-border-color: var(--app-accent);
        --bs-btn-active-bg: var(--app-accent-dark);
        --bs-btn-active-border-color: var(--app-accent-dark);
    }

    .app-fab {
        position: fixed;
        right: 1.5rem;
        bottom: 1.5rem;
        z-index: 1040;
        box-shadow: 0 16px 34px rgba(23, 72, 63, .28);
    }

    @media (max-width: 767.98px) {
        .app-fab {
            right: 1rem;
            bottom: 1rem;
        }
    }
</style>
</head>

<body>

<nav class="navbar navbar-dark app-navbar px-3">
    <a class="navbar-brand" href="{{ route('dashboard') }}">
        Fablead Task
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

<div class="container-fluid app-shell py-4">
    <div class="row g-4">
        @auth
            <aside class="col-md-2 app-sidebar">
                <div class="list-group">
                    <a
                        class="list-group-item list-group-item-action {{ request()->routeIs('dashboard') ? 'active' : '' }}"
                        href="{{ route('dashboard') }}"
                    >
                        Dashboard
                    </a>

                    <a
                        class="list-group-item list-group-item-action {{ request()->routeIs('records.*') ? 'active' : '' }}"
                        href="{{ route('records.page') }}"
                    >
                        Records
                    </a>

                    @can('manage-system')
                        <a
                            class="list-group-item list-group-item-action {{ request()->routeIs('admin.*') ? 'active' : '' }}"
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

            @if (session('success'))
                <div class="alert alert-success" role="status">
                    {{ session('success') }}
                </div>
            @endif

            @if (session('error'))
                <div class="alert alert-danger" role="alert">
                    {{ session('error') }}
                </div>
            @endif

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
