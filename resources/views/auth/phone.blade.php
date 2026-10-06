<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Phone Verification</title>

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
                            Phone Verification
                        </h2>

                        <p class="text-muted mb-0">
                            Enter your phone number to receive an OTP.
                        </p>

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

                    <form
                        method="POST"
                        action="{{ route('otp.send') }}"
                    >

                        @csrf

                        <div class="mb-3">

                            <label
                                for="phone"
                                class="form-label fw-semibold"
                            >
                                Phone Number
                            </label>

                            <input
                                type="tel"
                                id="phone"
                                name="phone"
                                value="{{ old('phone') }}"
                                class="form-control @error('phone') is-invalid @enderror"
                                placeholder="+919876543210"
                                autocomplete="tel"
                                inputmode="tel"
                                required
                            >

                            @error('phone')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror

                            <div class="form-text">
                                Enter your number with country code.
                                Example: +919876543210
                            </div>

                        </div>

                        <button
                            type="submit"
                            class="btn btn-primary w-100"
                        >
                            Send OTP
                        </button>

                    </form>

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
