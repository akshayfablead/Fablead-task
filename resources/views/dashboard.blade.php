@extends('layouts.app')

@section('content')
    @php
        $googleCalendarToken = auth()->user()->googleCalendarToken;
        $googleCalendarConnected = (bool) $googleCalendarToken;
    @endphp

    <section class="workspace-surface rounded-3 p-4 p-lg-5">
        <div class="d-flex flex-column flex-lg-row justify-content-between gap-4">
            <div>
                <p class="text-uppercase text-muted-strong fw-semibold small mb-2">
                    {{ auth()->user()->getRoleNames()->implode(', ') ?: 'Account' }}
                </p>

                <h1 class="h3 mb-2">
                    Welcome back, {{ auth()->user()->name }}.
                </h1>

                <p class="text-muted-strong mb-0">
                    Use the records workspace to review local records and the
                    synced Google Sheet rows from one place.
                </p>
            </div>

            <div class="d-flex flex-wrap align-items-start gap-2">
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
                        Manage Accounts
                    </a>
                @endcan
            </div>
        </div>

        <hr class="my-4">

        <div class="row g-3">
            <div class="col-md-4">
                <div class="border rounded-3 p-3 h-100">
                    <div class="fw-semibold mb-1">Records Workspace</div>
                    <div class="text-muted-strong small">
                        Search, filter, create, edit, and review records in a
                        dense operational table.
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="border rounded-3 p-3 h-100">
                    <div class="fw-semibold mb-1">Google Sheet View</div>
                    <div class="text-muted-strong small">
                        Check the latest sheet rows directly from the records
                        screen without changing sections.
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="border rounded-3 p-3 h-100">
                    <div class="fw-semibold mb-1">Account Capability</div>
                    <div class="text-muted-strong small">
                        @if(auth()->user()->isAdmin())
                            You can manage records, accounts, roles, and system
                            access.
                        @else
                            You can work with records according to your assigned
                            role permissions.
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <hr class="my-4">

        <section
            class="border rounded-3 p-3 p-lg-4"
            aria-labelledby="google-calendar-event-title"
        >
            <div class="d-flex flex-column flex-lg-row justify-content-between gap-3 mb-3">
                <div>
                    <p class="text-uppercase text-muted-strong fw-semibold small mb-2">
                        Scheduling
                    </p>

                    <h2
                        id="google-calendar-event-title"
                        class="h5 mb-1"
                    >
                        Google Calendar Event
                    </h2>

                    <p class="text-muted-strong small mb-0">
                        Create a calendar event from the records dashboard.
                    </p>
                </div>

                <div class="d-flex flex-column align-items-start align-items-lg-end gap-2">
                    @if ($googleCalendarConnected)
                        <span class="badge text-bg-success">
                            Calendar connected
                        </span>
                    @else
                        <span class="badge text-bg-warning">
                            Connection required
                        </span>

                        <a
                            href="{{ route('google.calendar.connect') }}"
                            class="btn btn-outline-primary btn-sm"
                        >
                            Connect Google Calendar
                        </a>
                    @endif
                </div>
            </div>

            @unless ($googleCalendarConnected)
                <div class="alert alert-warning py-2 small mb-3" role="status">
                    Connect Google Calendar before creating events. The form is visible so you can review the required details.
                </div>
            @endunless

            <form id="google-calendar-event-form" novalidate>
                <div
                    class="alert alert-danger form-errors d-none"
                    role="alert"
                ></div>

                <div
                    class="alert alert-success d-none"
                    id="calendar-event-success"
                    role="status"
                ></div>

                <fieldset @disabled(! $googleCalendarConnected)>
                    <div class="row g-3">
                        <div class="col-12">
                            <label for="calendar-summary" class="form-label fw-semibold">
                                Event title
                            </label>

                            <input
                                type="text"
                                class="form-control"
                                id="calendar-summary"
                                name="summary"
                                maxlength="255"
                                required
                                placeholder="Follow up on client records"
                            >
                        </div>

                        <div class="col-md-6">
                            <label for="calendar-start-at" class="form-label fw-semibold">
                                Start
                            </label>

                            <input
                                type="datetime-local"
                                class="form-control"
                                id="calendar-start-at"
                                name="start_at"
                                required
                            >
                        </div>

                        <div class="col-md-6">
                            <label for="calendar-end-at" class="form-label fw-semibold">
                                End
                            </label>

                            <input
                                type="datetime-local"
                                class="form-control"
                                id="calendar-end-at"
                                name="end_at"
                                required
                            >
                        </div>

                        <div class="col-md-6">
                            <label for="calendar-location" class="form-label fw-semibold">
                                Location
                            </label>

                            <input
                                type="text"
                                class="form-control"
                                id="calendar-location"
                                name="location"
                                maxlength="255"
                                placeholder="Office, Meet link, or client site"
                            >
                        </div>

                        <div class="col-md-6">
                            <label for="calendar-attendees" class="form-label fw-semibold">
                                Attendees
                            </label>

                            <input
                                type="text"
                                class="form-control"
                                id="calendar-attendees"
                                name="attendees"
                                placeholder="alex@example.com, priya@example.com"
                            >

                            <div class="form-text">
                                Separate multiple email addresses with commas.
                            </div>
                        </div>

                        <div class="col-12">
                            <label for="calendar-description" class="form-label fw-semibold">
                                Description
                            </label>

                            <textarea
                                class="form-control"
                                id="calendar-description"
                                name="description"
                                rows="4"
                                maxlength="5000"
                                placeholder="Add agenda notes, record context, or next steps."
                            ></textarea>
                        </div>
                    </div>

                    <div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-3 mt-4">
                        <p class="text-muted-strong small mb-0">
                            Events are created on your primary Google Calendar.
                        </p>

                        <button
                            type="submit"
                            class="btn btn-primary"
                            id="calendar-event-save"
                        >
                            Create Event
                        </button>
                    </div>
                </fieldset>
            </form>
        </section>
    </section>
@endsection

@push('scripts')
    <script>
        $(function () {
            const form = $('#google-calendar-event-form');
            const submitButton = $('#calendar-event-save');
            const successBox = $('#calendar-event-success');

            form.on('submit', function (event) {
                event.preventDefault();

                form.find('.form-errors')
                    .addClass('d-none')
                    .text('');

                successBox
                    .addClass('d-none')
                    .empty();

                const attendees = $('#calendar-attendees')
                    .val()
                    .split(',')
                    .map(function (email) {
                        return email.trim();
                    })
                    .filter(Boolean);

                const payload = {
                    summary: $('#calendar-summary').val(),
                    start_at: $('#calendar-start-at').val(),
                    end_at: $('#calendar-end-at').val(),
                    timezone: 'Asia/Kolkata',
                    location: $('#calendar-location').val(),
                    attendees: attendees,
                    description: $('#calendar-description').val()
                };

                submitButton
                    .prop('disabled', true)
                    .text('Creating...');

                $.ajax({
                    method: 'POST',
                    url: '{{ route('google.calendar.events.store') }}',
                    data: payload
                })
                    .done(function (response) {
                        const eventData = response.event || {};
                        const summary = eventData.summary || payload.summary;

                        successBox
                            .removeClass('d-none')
                            .text(response.message || 'Google Calendar event created.');

                        if (eventData.html_link) {
                            $('<a>', {
                                href: eventData.html_link,
                                target: '_blank',
                                rel: 'noopener',
                                class: 'alert-link ms-1',
                                text: 'Open "' + summary + '" in Google Calendar.'
                            }).appendTo(successBox);
                        }

                        form[0].reset();
                    })
                    .fail(function (xhr) {
                        requestError(xhr, form);
                    })
                    .always(function () {
                        submitButton
                            .prop('disabled', false)
                            .text('Create Event');
                    });
            });
        });
    </script>
@endpush
