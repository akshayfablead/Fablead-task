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
            width: min(100%, 1060px);
            display: grid;
            grid-template-columns: minmax(270px, .82fr) minmax(360px, 1fr);
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
            min-height: 660px;
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

        .auth-steps {
            display: grid;
            gap: .85rem;
            margin: 0;
            padding: 0;
            list-style: none;
            counter-reset: auth-step;
        }

        .auth-steps li {
            display: grid;
            grid-template-columns: 2rem 1fr;
            gap: .75rem;
            align-items: start;
            color: #2e3a4d;
            font-size: .95rem;
            line-height: 1.45;
        }

        .auth-steps li::before {
            counter-increment: auth-step;
            content: counter(auth-step);
            display: grid;
            width: 2rem;
            height: 2rem;
            place-items: center;
            border: 1px solid rgba(36, 87, 197, .22);
            border-radius: 7px;
            background: #fff;
            color: var(--auth-blue);
            font-size: .85rem;
            font-weight: 750;
        }

        .auth-panel {
            display: flex;
            align-items: center;
            padding: 2.5rem;
        }

        .auth-form-wrap {
            width: 100%;
            max-width: 520px;
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
            min-height: 46px;
            border-color: #cfd7e3;
            border-radius: 7px;
        }

        .auth-form .form-control:focus {
            border-color: var(--auth-blue);
            box-shadow: 0 0 0 .2rem rgba(36, 87, 197, .14);
        }

        .auth-form .form-text {
            color: var(--auth-muted);
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

            .auth-steps {
                display: none;
            }

            .auth-panel {
                padding: 1.5rem;
            }
        }
    </style>

    <section class="auth-page" aria-labelledby="register-heading">
        <div class="auth-shell">
            <div class="auth-intro">
                <div>
                    <div class="auth-kicker">Create account</div>

                    <h1 class="auth-title">
                        Set up access with phone verification.
                    </h1>

                    <p class="auth-copy">
                        Register once, verify your phone with an OTP, and enter the workspace with the right account details in place.
                    </p>
                </div>

                <ol class="auth-steps" aria-label="Registration steps">
                    <li>Enter your profile and contact details.</li>
                    <li>Receive a one-time code on your phone.</li>
                    <li>Verify the code to finish account creation.</li>
                </ol>
            </div>

            <div class="auth-panel">
                <div class="auth-form-wrap">
                    <h2 id="register-heading" class="auth-form-title">Register</h2>

                    <p class="auth-form-subtitle">
                        An OTP will be sent to your phone before the account is created.
                    </p>

                    <form
                        id="register-form"
                        class="auth-form js-ajax-form"
                        method="POST"
                        action="{{ route('register.send-otp') }}"
                    >
                        @csrf

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="name" class="form-label">
                                    Name
                                </label>

                                <input
                                    id="name"
                                    name="name"
                                    type="text"
                                    class="form-control @error('name') is-invalid @enderror"
                                    value="{{ old('name') }}"
                                    maxlength="100"
                                    autocomplete="name"
                                    required
                                >

                                @error('name')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label for="email" class="form-label">
                                    Email
                                </label>

                                <input
                                    id="email"
                                    name="email"
                                    type="email"
                                    class="form-control @error('email') is-invalid @enderror"
                                    value="{{ old('email') }}"
                                    maxlength="255"
                                    autocomplete="email"
                                    required
                                >

                                @error('email')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>

                            <div class="col-12">
                                <label for="phone" class="form-label">
                                    Phone Number
                                </label>

                                <input
                                    id="phone"
                                    name="phone"
                                    type="tel"
                                    class="form-control @error('phone') is-invalid @enderror"
                                    value="{{ old('phone') }}"
                                    placeholder="+919876543210"
                                    autocomplete="tel"
                                    inputmode="tel"
                                    required
                                >

                                <div class="form-text">
                                    Enter your number with country code. Example: +919876543210
                                </div>

                                @error('phone')
                                    <div class="invalid-feedback d-block">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label for="password" class="form-label">
                                    Password
                                </label>

                                <input
                                    id="password"
                                    name="password"
                                    type="password"
                                    class="form-control @error('password') is-invalid @enderror"
                                    minlength="6"
                                    autocomplete="new-password"
                                    required
                                >

                                @error('password')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label for="password_confirmation" class="form-label">
                                    Confirm Password
                                </label>

                                <input
                                    id="password_confirmation"
                                    name="password_confirmation"
                                    type="password"
                                    class="form-control"
                                    minlength="6"
                                    autocomplete="new-password"
                                    required
                                >
                            </div>
                        </div>

                        <div
                            class="alert alert-danger d-none js-form-error mt-3"
                            role="alert"
                        ></div>

                        <div class="d-grid gap-3 mt-4">
                            <button
                                type="submit"
                                class="btn btn-primary"
                                data-loading-text="Sending OTP..."
                            >
                                Send OTP
                            </button>

                            <a
                                href="{{ route('login') }}"
                                class="btn btn-link auth-secondary text-decoration-none"
                            >
                                Already have an account? Login
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>
@endsection

@push('scripts')
    <script>
        $('#register-form').on('submit', function (event) {
            event.preventDefault();

            const form = $(this);
            const button = form.find('button[type="submit"]');
            const errorBox = form.find('.js-form-error');
            const originalText = button.text().trim();

            errorBox.addClass('d-none').text('');
            form.find('.is-invalid').removeClass('is-invalid');
            form.find('.js-ajax-error').remove();
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

                    notify(response.message || 'OTP sent successfully.');
                },
                error(xhr) {
                    const body = xhr.responseJSON || {};
                    const errors = body.errors || {};
                    const firstError = Object.values(errors).flat().shift();

                    Object.keys(errors).forEach(function (field) {
                        const input = form.find('[name="' + field + '"]');

                        input.addClass('is-invalid');
                        input.after(
                            '<div class="invalid-feedback d-block js-ajax-error">'
                            + errors[field][0]
                            + '</div>'
                        );
                    });

                    errorBox
                        .removeClass('d-none')
                        .text(firstError || body.message || 'OTP could not be sent.');
                },
                complete() {
                    button.prop('disabled', false).text(originalText);
                }
            });
        });
    </script>
@endpush
