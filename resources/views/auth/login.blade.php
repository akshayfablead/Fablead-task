@extends('layouts.app')

@section('content')
    <div
        class="card mx-auto shadow-sm"
        style="max-width: 420px"
    >
        <div class="card-body p-4">
            <h1 class="h4 mb-4">Login</h1>

            <form method="POST" action="{{ url('/login') }}">
                @csrf

                <label for="email" class="form-label">
                    Email
                </label>

                <input
                    id="email"
                    name="email"
                    type="email"
                    class="form-control mb-3"
                    value="{{ old('email') }}"
                    autocomplete="username"
                    required
                >

                <label for="password" class="form-label">
                    Password
                </label>

                <input
                    id="password"
                    name="password"
                    type="password"
                    class="form-control mb-3"
                    autocomplete="current-password"
                    required
                >

                @if($errors->any())
                    <div class="alert alert-danger" role="alert">
                        {{ $errors->first() }}
                    </div>
                @endif

                <div class="d-grid gap-2">
    <button
        type="submit"
        class="btn btn-primary"
    >
        Login
    </button>

    <div class="text-center text-muted my-2">
        <span>OR</span>
    </div>

    <a
        href="{{ route('google.redirect') }}"
        class="btn btn-outline-dark d-flex align-items-center justify-content-center gap-2"
    >
        <i class="bi bi-google"></i>
        Continue with Google
    </a>
</div>

            </form>
        </div>
    </div>
@endsection
