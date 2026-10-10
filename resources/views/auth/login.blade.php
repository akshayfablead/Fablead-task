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
            width: min(100%, 960px);
            display: grid;
            grid-template-columns: minmax(260px, .86fr) minmax(320px, 1fr);
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
            min-height: 540px;
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

        .auth-meta {
            display: grid;
            gap: .75rem;
            margin: 0;
            padding: 0;
            list-style: none;
            color: #2e3a4d;
            font-size: .95rem;
        }

        .auth-meta li {
            display: flex;
            align-items: center;
            gap: .7rem;
        }

        .auth-meta li::before {
            content: "";
            width: .5rem;
            height: .5rem;
            flex: 0 0 .5rem;
            border-radius: 50%;
            background: var(--auth-blue);
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
            min-height: 46px;
            border-color: #cfd7e3;
            border-radius: 7px;
        }

        .auth-form .form-control:focus {
            border-color: var(--auth-blue);
            box-shadow: 0 0 0 .2rem rgba(36, 87, 197, .14);
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

        .auth-divider {
            display: flex;
            align-items: center;
            gap: .75rem;
            color: var(--auth-muted);
            font-size: .78rem;
            font-weight: 700;
            letter-spacing: .08em;
            text-transform: uppercase;
        }

        .auth-divider::before,
        .auth-divider::after {
            content: "";
            height: 1px;
            flex: 1;
            background: var(--auth-line);
        }

        .auth-google-mark {
            display: inline-grid;
            width: 1.35rem;
            height: 1.35rem;
            place-items: center;
            border: 1px solid var(--auth-line);
            border-radius: 50%;
            color: #d34b35;
            font-size: .82rem;
            font-weight: 800;
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

            .auth-meta {
                display: none;
            }

            .auth-panel {
                padding: 1.5rem;
            }
        }
    </style>

    <section class="auth-page" aria-labelledby="login-heading">
        <div class="auth-shell">
            <div class="auth-intro">
                <div>
                    <div class="auth-kicker">Fablead Task</div>

                    <h1 class="auth-title">
                        Secure access for records and roles.
                    </h1>

                    <p class="auth-copy">
                        Sign in to manage records, account permissions, and daily operational work from one focused workspace.
                    </p>
                </div>

                <ul class="auth-meta" aria-label="Workspace highlights">
                    <li>Role-aware access controls</li>
                    <li>Business records in one place</li>
                    <li>Connected team workflow</li>
                </ul>
            </div>

            <div class="auth-panel">
                <div class="auth-form-wrap">
                    <h2 id="login-heading" class="auth-form-title">Welcome back</h2>

                    <p class="auth-form-subtitle">
                        Use your work email or continue with Google.
                    </p>

                    <form
                        id="login-form"
                        class="auth-form js-ajax-form"
                        method="POST"
                        action="{{ route('login.store') }}"
                    >
                        @csrf

                        <div class="mb-3">
                            <label for="email" class="form-label">
                                Email
                            </label>

                            <input
                                id="email"
                                name="email"
                                type="email"
                                class="form-control"
                                value="{{ old('email') }}"
                                autocomplete="username"
                                required
                            >
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label">
                                Password
                            </label>

                            <input
                                id="password"
                                name="password"
                                type="password"
                                class="form-control"
                                autocomplete="current-password"
                                required
                            >
                        </div>

                        <div
                            class="alert alert-danger d-none js-form-error"
                            role="alert"
                        ></div>

                        @if($errors->any())
                            <div class="alert alert-danger" role="alert">
                                {{ $errors->first() }}
                            </div>
                        @endif

                        <div class="d-grid gap-3">
                            <button
                                type="submit"
                                class="btn btn-primary"
                                data-loading-text="Logging in..."
                            >
                                Login
                            </button>

                            <div class="auth-divider">
                                <span>OR</span>
                            </div>

                            <a
                                href="{{ route('google.redirect') }}"
                                class="btn btn-outline-dark d-flex align-items-center justify-content-center gap-2"
                            >
                                <span class="auth-google-mark" aria-hidden="true">G</span>
                                <span>Continue with Google</span>
                            </a>

                            <a
                                href="{{ route('register') }}"
                                class="btn btn-link auth-secondary text-decoration-none"
                            >
                                Create new account
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
        $('#login-form').on('submit', function (event) {
            event.preventDefault();

            const form = $(this);
            const button = form.find('button[type="submit"]');
            const errorBox = form.find('.js-form-error');
            const originalText = button.text().trim();

            errorBox.addClass('d-none').text('');
            button.prop('disabled', true).text(button.data('loading-text'));

            $.ajax({
                method: 'POST',
                url: @json(route('login.store')),
                data: form.serialize(),
                success(response) {
                    if (response.redirect) {
                        window.location.href = response.redirect;
                        return;
                    }

                    notify(response.message || 'Login successful.');
                },
                error(xhr) {
                    const body = xhr.responseJSON || {};
                    const firstError = Object.values(body.errors || {})
                        .flat()
                        .shift();

                    errorBox
                        .removeClass('d-none')
                        .text(firstError || body.message || 'Login failed.');
                },
                complete() {
                    button.prop('disabled', false).text(originalText);
                }
            });
        });
    </script>
@endpush
