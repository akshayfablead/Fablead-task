<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">

    <meta name="viewport" content="width=device-width, initial-scale=1">

    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Fablead Task</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    @vite('resources/js/app.js')

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
                <div class="dropdown">
                    <button class="btn btn-outline-light btn-sm position-relative" type="button" id="notificationDropdown"
                        data-bs-toggle="dropdown" aria-expanded="false" aria-label="Notifications">
                        <span aria-hidden="true">&#128276;</span>
                        <span id="notification-count"
                            class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger d-none">0</span>
                    </button>

                    <div class="dropdown-menu dropdown-menu-end p-0 shadow" style="width: min(360px, 90vw);"
                        aria-labelledby="notificationDropdown">
                        <div class="d-flex justify-content-between align-items-center p-3 border-bottom">
                            <strong>Notifications</strong>

                            <button type="button" class="btn btn-link btn-sm text-decoration-none p-0"
                                id="mark-all-notifications-read">
                                Mark all read
                            </button>
                        </div>

                        <div id="notification-list" class="list-group list-group-flush"
                            style="max-height: 350px; overflow-y: auto;">
                            <div class="p-3 text-muted small">
                                Loading notifications...
                            </div>
                        </div>

                        <div class="p-2 border-top text-center">
                            <button type="button" class="btn btn-sm btn-outline-primary" id="refresh-notifications">
                                Refresh
                            </button>
                        </div>
                    </div>
                </div>

                <span>{{ auth()->user()->name }}</span>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf

                    <button type="submit" class="btn btn-outline-light btn-sm">
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
                        <a class="list-group-item list-group-item-action {{ request()->routeIs('dashboard') ? 'active' : '' }}"
                            href="{{ route('dashboard') }}">
                            Dashboard
                        </a>

                        <a class="list-group-item list-group-item-action {{ request()->routeIs('records.*') ? 'active' : '' }}"
                            href="{{ route('records.page') }}">
                            Records
                        </a>

                        <a class="list-group-item list-group-item-action {{ request()->routeIs('customers.*') ? 'active' : '' }}"
                            href="{{ route('customers.index') }}">
                            Customers
                        </a>

                        <a class="list-group-item list-group-item-action {{ request()->routeIs('whatsapp.*') ? 'active' : '' }}"
                            href="{{ route('whatsapp.index') }}">
                            WhatsApp
                        </a>

                        <a class="list-group-item list-group-item-action {{ request()->routeIs('calls.*') ? 'active' : '' }}"
                            href="{{ route('calls.index') }}">
                            Audio &amp; Video Calls
                        </a>

                        @can('manage-system')
                            <a class="list-group-item list-group-item-action {{ request()->routeIs('admin.*') ? 'active' : '' }}"
                                href="{{ route('admin.index') }}">
                                Accounts & Roles
                            </a>
                        @endcan
                    </div>
                </aside>
            @endauth

            <main class="{{ auth()->check() ? 'col-md-10' : 'col-12' }}">
                <div id="notice" class="alert d-none" role="status"></div>

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
        // Global AJAX configuration.
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': document.querySelector(
                    'meta[name="csrf-token"]'
                ).content,
                'Accept': 'application/json'
            }
        });

        const notificationRoutes = {
            index: @json(route('api.notifications.index')),
            unreadCount: @json(route('api.notifications.unread-count')),
            read: @json(route('api.notifications.read', ['id' => '__NOTIFICATION_ID__'])),
            readAll: @json(route('api.notifications.read-all'))
        };

        // Display success or error messages.
        function notify(message, type = 'success') {
            $('#notice')
                .removeClass('d-none alert-success alert-danger')
                .addClass('alert-' + type)
                .text(message);
        }

        // Handle common AJAX errors.
        function requestError(xhr, form = null) {
            const body = xhr.responseJSON || {};

            const messages = {
                401: 'Please log in again.',
                403: 'You do not have permission for this action.',
                404: 'Record not found.',
                419: 'Session expired. Refresh the page and log in again.',
                429: 'Too many requests. Please wait and try again.'
            };

            const message =
                messages[xhr.status] ||
                (xhr.status >= 500 ?
                    'Server error. Please try again.' :
                    body.message) ||
                'Request failed.';

            if (form && xhr.status === 422) {
                const errors = Object.values(body.errors || {}).flat();

                form.find('.form-errors')
                    .removeClass('d-none')
                    .text(errors.join(' ') || message);

                return;
            }

            notify(message, 'danger');
        }

        // Notification system.
        $(function() {
            const $notificationDropdown = $('#notificationDropdown');

            // Do not run notification code on pages without the bell.
            if (!$notificationDropdown.length) {
                return;
            }

            const $notificationCount = $('#notification-count');
            const $notificationList = $('#notification-list');
            const $markAllButton = $('#mark-all-notifications-read');
            const $refreshButton = $('#refresh-notifications');

            // Update unread notification badge.
            function loadUnreadCount() {
                return $.ajax({
                        url: notificationRoutes.unreadCount,
                        method: 'GET'
                    })
                    .done(function(response) {
                        const count = Number(response.unread_count || 0);

                        $notificationCount
                            .text(count > 99 ? '99+' : count)
                            .toggleClass('d-none', count === 0);
                    })
                    .fail(function(xhr) {
                        requestError(xhr);
                    });
            }

            // Render the empty notification state.
            function showEmptyNotifications() {
                $notificationList.empty().append(
                    $('<div>', {
                        class: 'p-3 text-muted small text-center',
                        text: "You're all caught up."
                    })
                );
            }

            // Render notification loading errors.
            function showNotificationError() {
                $notificationList.empty().append(
                    $('<div>', {
                        class: 'p-3 text-danger small text-center',
                        text: 'Could not load notifications. Please try again.'
                    })
                );
            }

            // Open only URLs belonging to the current site.
            function openNotificationUrl(targetUrl) {
                if (!targetUrl) {
                    return;
                }

                try {
                    const url = new URL(targetUrl, window.location.origin);

                    if (url.origin !== window.location.origin) {
                        notify('This notification URL is not allowed.', 'danger');
                        return;
                    }

                    window.location.assign(url.href);
                } catch (error) {
                    console.error('Invalid notification URL.', error);
                    notify('Invalid notification URL.', 'danger');
                }
            }

            // Mark one notification as read.
            function markNotificationAsRead(notification) {
                return $.ajax({
                    url: notificationRoutes.read.replace(
                        '__NOTIFICATION_ID__',
                        encodeURIComponent(notification.id)
                    ),
                    method: 'PATCH'
                });
            }

            // Load notifications from the API.
            function loadNotifications() {
                $notificationList.empty().append(
                    $('<div>', {
                        class: 'p-3 text-muted small text-center',
                        text: 'Loading notifications...'
                    })
                );

                return $.ajax({
                        url: notificationRoutes.index,
                        method: 'GET'
                    })
                    .done(function(response) {
                        const notifications = response.data || [];

                        $notificationList.empty();

                        if (notifications.length === 0) {
                            showEmptyNotifications();
                            loadUnreadCount();
                            return;
                        }

                        notifications.forEach(function(notification) {
                            const data = notification.data || {};
                            const isUnread = !notification.read_at;

                            const $item = $('<button>', {
                                type: 'button',
                                class: 'list-group-item list-group-item-action text-start'
                            });

                            if (isUnread) {
                                $item.addClass('list-group-item-light');
                            }

                            const $title = $('<div>', {
                                class: 'fw-semibold mb-1',
                                text: data.title || 'Notification'
                            });

                            const $message = $('<div>', {
                                class: 'small text-muted',
                                text: data.message || ''
                            });

                            const $date = $('<div>', {
                                class: 'small text-secondary mt-2',
                                text: notification.created_at ?
                                    new Date(
                                        notification.created_at
                                    ).toLocaleString() :
                                    ''
                            });

                            $item.append($title, $message, $date);

                            // Handle notification click.
                            $item.on('click', function() {
                                if (!isUnread) {
                                    openNotificationUrl(data.url);
                                    return;
                                }

                                $item.prop('disabled', true);

                                markNotificationAsRead(notification)
                                    .done(function() {
                                        notification.read_at = new Date().toISOString();

                                        $item.removeClass('list-group-item-light');
                                        $item.prop('disabled', false);

                                        loadUnreadCount();
                                        openNotificationUrl(data.url);
                                    })
                                    .fail(function(xhr) {
                                        $item.prop('disabled', false);
                                        requestError(xhr);
                                    });
                            });

                            $notificationList.append($item);
                        });

                        loadUnreadCount();
                    })
                    .fail(function(xhr) {
                        showNotificationError();
                        requestError(xhr);
                    });
            }

            // Load notifications when the dropdown opens.
            $notificationDropdown.on(
                'show.bs.dropdown',
                function() {
                    loadNotifications();
                }
            );

            // Refresh button.
            $refreshButton.on('click', function(event) {
                event.preventDefault();
                event.stopPropagation();

                loadNotifications();
            });

            // Mark all notifications as read.
            $markAllButton.on('click', function(event) {
                event.preventDefault();
                event.stopPropagation();

                $markAllButton.prop('disabled', true);

                $.ajax({
                        url: notificationRoutes.readAll,
                        method: 'PATCH'
                    })
                    .done(function() {
                        notify('All notifications marked as read.');

                        loadUnreadCount();
                        loadNotifications();
                    })
                    .fail(function(xhr) {
                        requestError(xhr);
                    })
                    .always(function() {
                        $markAllButton.prop('disabled', false);
                    });
            });

            // Initial unread count when the page loads.
            loadUnreadCount();
        });
    </script>

    @stack('scripts')

</body>

</html>
