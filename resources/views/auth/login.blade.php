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

                <button
                    type="submit"
                    class="btn btn-primary w-100"
                >
                    Login
                </button>
            </form>
        </div>
    </div>
@endsection