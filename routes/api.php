<?php

use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\ApiController;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Support\Facades\Route;

Route::middleware('web')->withoutMiddleware([ValidateCsrfToken::class])->name('api.')->group(function () {
    Route::get('/customers', [CustomerController::class, 'index'])
        ->name('customers.index');

    Route::post('/customers', [CustomerController::class, 'store'])
        ->name('customers.store');

    Route::get('/customers/{id}', [CustomerController::class, 'show'])
        ->name('customers.show');

    Route::put('/customers/{id}', [CustomerController::class, 'update'])
        ->name('customers.update');

    Route::delete('/customers/{id}', [CustomerController::class, 'destroy'])
        ->name('customers.destroy');

    Route::get('/', [ApiController::class, 'home'])
        ->name('home');

    Route::middleware('guest')->group(function () {
        Route::get('/login', [ApiController::class, 'loginForm'])
            ->name('login');

        Route::post('/login', [ApiController::class, 'login'])
            ->middleware('throttle:login')
            ->name('login.store');

        Route::get('/register', [ApiController::class, 'registerForm'])
            ->name('register');

        Route::post('/register/send-otp', [ApiController::class, 'sendRegisterOtp'])
            ->middleware('throttle:6,1')
            ->name('register.send-otp');

        Route::get('/register/verify-otp', [ApiController::class, 'showRegisterVerify'])
            ->name('register.verify');

        Route::post('/register/verify-otp', [ApiController::class, 'verifyRegisterOtp'])
            ->middleware('throttle:6,1')
            ->name('register.verify.submit');

        Route::post('/register/resend-otp', [ApiController::class, 'resendRegisterOtp'])
            ->middleware('throttle:3,1')
            ->name('register.resend-otp');

        Route::get('/auth/google', [ApiController::class, 'googleRedirect'])
            ->name('google.redirect');

        Route::get('/auth/google/callback', [ApiController::class, 'googleCallback'])
            ->name('google.callback');
    });

    Route::middleware('auth')->group(function () {
        Route::post('/logout', [ApiController::class, 'logout'])
            ->name('logout');

        Route::get('/dashboard', [ApiController::class, 'dashboard'])
            ->name('dashboard');

        Route::get('/phone', [ApiController::class, 'phoneForm'])
            ->name('otp.phone');

        Route::post('/phone/send-otp', [ApiController::class, 'sendOtp'])
            ->name('otp.send');

        Route::get('/verify-otp', [ApiController::class, 'showVerifyOtp'])
            ->name('otp.verify');

        Route::post('/verify-otp', [ApiController::class, 'verifyOtp'])
            ->name('otp.verify.submit');

        Route::post('/verify-otp/resend', [ApiController::class, 'resendOtp'])
            ->name('otp.resend');

        Route::get('/records', [ApiController::class, 'recordsPage'])
            ->name('records.page');

        Route::get('/records/data', [ApiController::class, 'recordsIndex'])
            ->name('records.data');

        Route::post('/records', [ApiController::class, 'recordsStore'])
            ->name('records.store');

        Route::get('/records/{record}', [ApiController::class, 'recordsShow'])
            ->name('records.show');

        Route::put('/records/{record}', [ApiController::class, 'recordsUpdate'])
            ->name('records.update');

        Route::delete('/records/{record}', [ApiController::class, 'recordsDestroy'])
            ->name('records.destroy');

        Route::get('/google-sheets', [ApiController::class, 'googleSheets'])
            ->name('google-sheets.index');

        Route::get('/auth/google/calendar/connect', [ApiController::class, 'googleCalendarConnect'])
            ->name('google.calendar.connect');

        Route::get('/auth/google/calendar/callback', [ApiController::class, 'googleCalendarCallback'])
            ->name('google.calendar.callback');

        Route::post('/google-calendar/events', [ApiController::class, 'googleCalendarStoreEvent'])
            ->name('google.calendar.events.store');

        Route::prefix('admin')
            ->name('admin.')
            ->middleware('admin')
            ->group(function () {
                Route::get('/', [ApiController::class, 'adminIndex'])
                    ->name('index');

                Route::post('/accounts', [ApiController::class, 'adminStoreAccount'])
                    ->name('accounts.store');

                Route::put('/accounts/{user}', [ApiController::class, 'adminUpdateAccount'])
                    ->name('accounts.update');

                Route::delete('/accounts/{user}', [ApiController::class, 'adminDeleteAccount'])
                    ->name('accounts.destroy');

                Route::post('/roles', [ApiController::class, 'adminStoreRole'])
                    ->name('roles.store');

                Route::put('/roles/{role}', [ApiController::class, 'adminUpdateRole'])
                    ->name('roles.update');

                Route::delete('/roles/{role}', [ApiController::class, 'adminDeleteRole'])
                    ->name('roles.destroy');
            });
    });
});
