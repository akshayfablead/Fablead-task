@extends('layouts.app')

@section('content')
    <style>
        :root {
            --auth-ink: #172033;
            --auth-muted: #657083;
            --auth-line: #dce2ea;
            --auth-soft: #f7f9fc;
            --auth-blue: #2457c5;
            --auth-blue-dark: #1d459d;
            --auth-warm: #c47a2c;
        }

        .auth-page {
            min-height: calc(100vh - 128px);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem 0;
            color: var(--auth-ink);
        }

        .auth-shell {
            width: min(100%, 920px);
            display: grid;
            grid-template-columns: minmax(260px, .82fr) minmax(320px, 1fr);
            overflow: hidden;
            border: 1px solid var(--auth-line);
            border-radius: 8px;
            background: #fff;
            box-shadow: 0 22px 55px rgba(23, 32, 51, .08);
        }

        .auth-intro {
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            gap: 3rem;
            min-height: 520px;
            padding: 2.25rem;
            background:
                linear-gradient(135deg, rgba(36, 87, 197, .08), rgba(196, 122, 44, .08)),
                var(--auth-soft);
            border-right: 1px solid var(--auth-line);
        }

        .auth-kicker {
            display: inline-flex;
            align-items: center;
            gap: .5rem;
            margin-bottom: 1.25rem;
            color: var(--auth-warm);
            font-size: .75rem;
            font-weight: 700;
            letter-spacing: .08em;
            text-transform: uppercase;
        }

        .auth-kicker::before {
            content: "";
            width: .65rem;
            height: .65rem;
            border-radius: 2px;
            background: var(--auth-warm);
        }

        .auth-title {
            margin: 0 0 1rem;
            font-size: clamp(2rem, 4vw, 3rem);
            line-height: 1.02;
            font-weight: 750;
            letter-spacing: 0;
        }

        .auth-copy {
            max-width: 29rem;
            margin: 0;
            color: var(--auth-muted);
            font-size: 1rem;
            line-height: 1.65;
        }

        .auth-status {
            padding: 1rem;
            border: 1px solid rgba(36, 87, 197, .18);
            border-radius: 8px;
            background: #fff;
        }

        .auth-status-label {
            margin-bottom: .25rem;
            color: var(--auth-muted);
            font-size: .78rem;
            font-weight: 700;
            letter-spacing: .08em;
            text-transform: uppercase;
        }

        .auth-status-value {
            margin: 0;
            color: #253149;
            font-weight: 750;
            overflow-wrap: anywhere;
        }

        .auth-panel {
            display: flex;
            align-items: center;
            padding: 2.5rem;
        }

        .auth-form-wrap {
            width: 100%;
            max-width: 390px;
            margin: 0 auto;
        }

        .auth-form-title {
            margin-bottom: .45rem;
            font-size: 1.55rem;
            font-weight: 720;
        }

        .auth-form-subtitle {
            margin-bottom: 1.75rem;
            color: var(--auth-muted);
            line-height: 1.55;
        }

        .auth-form .form-label {
            margin-bottom: .45rem;
            color: #2f3a4b;
            font-size: .9rem;
            font-weight: 650;
        }

        .auth-form .form-control {
            min-height: 50px;
            border-color: #cfd7e3;
            border-radius: 7px;
        }

        .auth-form .form-control:focus {
            border-color: var(--auth-blue);
            box-shadow: 0 0 0 .2rem rgba(36, 87, 197, .14);
        }

        .auth-otp-input {
            font-size: 1.4rem;
            font-weight: 750;
            letter-spacing: .28em;
            text-indent: .28em;
        }

        .auth-form .btn {
            min-height: 46px;
            border-radius: 7px;
            font-weight: 650;
        }

        .auth-form .btn-primary {
            background: var(--auth-blue);
            border-color: var(--auth-blue);
        }

        .auth-form .btn-primary:hover,
        .auth-form .btn-primary:focus {
            background: var(--auth-blue-dark);
            border-color: var(--auth-blue-dark);
        }

        .auth-action-row {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: .75rem 1rem;
            margin-top: 1.25rem;
        }

        .auth-secondary {
            color: var(--auth-blue);
            font-weight: 650;
        }

        @media (max-width: 767.98px) {
            .auth-page {
                min-height: auto;
                padding: .25rem 0 1rem;
            }

            .auth-shell {
                grid-template-columns: 1fr;
            }

            .auth-intro {
                min-height: auto;
                gap: 1.5rem;
                padding: 1.5rem;
                border-right: 0;
                border-bottom: 1px solid var(--auth-line);
            }

            .auth-panel {
                padding: 1.5rem;
            }
        }
    </style>

    <section class="auth-page" aria-labelledby="verify-heading">
        <div class="auth-shell">
            <div class="auth-intro">
                <div>
                    <div class="auth-kicker">Phone verification</div>

                    <h1 class="auth-title">
                        Confirm the code to finish registration.
                    </h1>

                    <p class="auth-copy">
                        This final check keeps account setup tied to the phone number provided during registration.
                    </p>
                </div>

                <div class="auth-status">
                    <div class="auth-status-label">Code sent to</div>
                    <p class="auth-status-value">{{ $phone }}</p>
                </div>
            </div>

            <div class="auth-panel">
                <div class="auth-form-wrap">
                    <h2 id="verify-heading" class="auth-form-title">Verify OTP</h2>

                    <p class="auth-form-subtitle">
                        Enter the six-digit code sent to your phone.
                    </p>

                    <form
                        id="register-verify-form"
                        class="auth-form js-ajax-form"
                        method="POST"
                        action="{{ route('register.verify.submit') }}"
                    >
                        @csrf

                        <input
                            type="hidden"
                            name="phone"
                            value="{{ $phone }}"
                        >

                        <div class="mb-3">
                            <label for="otp" class="form-label">
                                OTP
                            </label>

                            <input
                                id="otp"
                                name="otp"
                                type="text"
                                class="form-control form-control-lg text-center auth-otp-input @error('otp') is-invalid @enderror"
                                value="{{ old('otp') }}"
                                placeholder="000000"
                                maxlength="6"
                                inputmode="numeric"
                                autocomplete="one-time-code"
                                pattern="[0-9]{6}"
                                required
                                autofocus
                            >

                            @error('otp')
                                <div class="invalid-feedback d-block">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>

                        @error('phone')
                            <div class="alert alert-danger" role="alert">
                                {{ $message }}
                            </div>
                        @enderror

                        <div
                            class="alert alert-danger d-none js-form-error"
                            role="alert"
                        ></div>

                        <button
                            type="submit"
                            class="btn btn-primary w-100"
                            data-loading-text="Verifying..."
                        >
                            Verify & Register
                        </button>
                    </form>

                    <div class="auth-action-row">
                        <form
                            id="register-resend-form"
                            method="POST"
                            action="{{ route('register.resend-otp') }}"
                        >
                            @csrf

                            <button
                                type="submit"
                                class="btn btn-link auth-secondary text-decoration-none p-0"
                                data-loading-text="Sending..."
                            >
                                Resend OTP
                            </button>
                        </form>

                        <a
                            href="{{ route('register') }}"
                            class="auth-secondary text-decoration-none"
                        >
                            Edit details
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

@push('scripts')
    <script>
        function showAjaxErrors(form, xhr, fallbackMessage) {
            const body = xhr.responseJSON || {};
            const errors = body.errors || {};
            const firstError = Object.values(errors).flat().shift();
            const errorBox = form.find('.js-form-error');

            form.find('.is-invalid').removeClass('is-invalid');
            form.find('.js-ajax-error').remove();

            Object.keys(errors).forEach(function (field) {
                const input = form.find('[name="' + field + '"]');

                input.addClass('is-invalid');
                input.after(
                    '<div class="invalid-feedback d-block js-ajax-error">'
                    + errors[field][0]
                    + '</div>'
                );
            });

            if (body.redirect) {
                window.location.href = body.redirect;
                return;
            }

            errorBox
                .removeClass('d-none')
                .text(firstError || body.message || fallbackMessage);
        }

        $('#register-verify-form').on('submit', function (event) {
            event.preventDefault();

            const form = $(this);
            const button = form.find('button[type="submit"]');
            const originalText = button.text().trim();

            form.find('.js-form-error').addClass('d-none').text('');
            button.prop('disabled', true).text(button.data('loading-text'));

            $.ajax({
                method: form.attr('method'),
                url: form.attr('action'),
                data: form.serialize(),
                success(response) {
                    if (response.redirect) {
                        window.location.href = response.redirect;
                        return;
                    }

                    notify(response.message || 'Registration completed successfully.');
                },
                error(xhr) {
                    showAjaxErrors(form, xhr, 'OTP verification failed.');
                },
                complete() {
                    button.prop('disabled', false).text(originalText);
                }
            });
        });

        $('#register-resend-form').on('submit', function (event) {
            event.preventDefault();

            const form = $(this);
            const button = form.find('button[type="submit"]');
            const originalText = button.text().trim();

            button.prop('disabled', true).text(button.data('loading-text'));

            $.ajax({
                method: form.attr('method'),
                url: form.attr('action'),
                data: form.serialize(),
                success(response) {
                    notify(response.message || 'A new OTP has been sent.');
                },
                error(xhr) {
                    const body = xhr.responseJSON || {};

                    if (body.redirect) {
                        window.location.href = body.redirect;
                        return;
                    }

                    notify(body.message || 'OTP could not be resent.', 'danger');
                },
                complete() {
                    button.prop('disabled', false).text(originalText);
                }
            });
        });
    </script>
@endpush
