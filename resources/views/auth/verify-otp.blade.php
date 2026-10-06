<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Verify OTP</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >
</head>

<body class="bg-light">

<div class="container min-vh-100 d-flex align-items-center justify-content-center py-5">

    <div class="row w-100 justify-content-center">

        <div class="col-12 col-sm-10 col-md-8 col-lg-5 col-xl-4">

            <div class="card border-0 shadow-sm">

                <div class="card-body p-4 p-md-5">

                    <div class="text-center mb-4">

                        <h2 class="fw-bold mb-2">
                            Verify OTP
                        </h2>

                        <p class="text-muted mb-1">
                            We sent a verification code to
                        </p>

                        <strong>
                            {{ $phone }}
                        </strong>

                    </div>

                    {{-- Success Message --}}
                    @if (session('success'))
                        <div
                            class="alert alert-success alert-dismissible fade show"
                            role="alert"
                        >
                            {{ session('success') }}

                            <button
                                type="button"
                                class="btn-close"
                                data-bs-dismiss="alert"
                                aria-label="Close"
                            ></button>
                        </div>
                    @endif

                    {{-- Error Message --}}
                    @if (session('error'))
                        <div
                            class="alert alert-danger alert-dismissible fade show"
                            role="alert"
                        >
                            {{ session('error') }}

                            <button
                                type="button"
                                class="btn-close"
                                data-bs-dismiss="alert"
                                aria-label="Close"
                            ></button>
                        </div>
                    @endif

                    {{-- Validation Errors --}}
                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0 ps-3">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    {{-- Verify OTP --}}
                    <form
                        method="POST"
                        action="{{ route('otp.verify.submit') }}"
                    >

                        @csrf

                        <input
                            type="hidden"
                            name="phone"
                            value="{{ $phone }}"
                        >

                        <div class="mb-4">

                            <label
                                for="otp"
                                class="form-label fw-semibold"
                            >
                                Enter OTP
                            </label>

                            <input
                                type="text"
                                id="otp"
                                name="otp"
                                value="{{ old('otp') }}"
                                class="form-control form-control-lg text-center @error('otp') is-invalid @enderror"
                                placeholder="000000"
                                maxlength="6"
                                inputmode="numeric"
                                autocomplete="one-time-code"
                                pattern="[0-9]{6}"
                                required
                                autofocus
                            >

                            @error('otp')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror

                        </div>

                        <button
                            type="submit"
                            class="btn btn-primary btn-lg w-100"
                        >
                            Verify OTP
                        </button>

                    </form>

                    <div class="text-center mt-4">

                        <p class="text-muted mb-2">
                            Didn't receive the code?
                        </p>

                        <form
                            method="POST"
                            action="{{ route('otp.resend') }}"
                            class="d-inline"
                        >

                            @csrf

                            <button
                                type="submit"
                                class="btn btn-link text-decoration-none p-0"
                            >
                                Resend OTP
                            </button>

                        </form>

                    </div>

                    <div class="text-center mt-3">

                        <a
                            href="{{ route('otp.phone') }}"
                            class="text-decoration-none"
                        >
                            Change phone number
                        </a>

                    </div>

                </div>

            </div>

        </div>

    </div>

</div>

<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
></script>

</body>
</html>
