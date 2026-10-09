<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\GoogleCalendarController;
use App\Http\Controllers\GoogleSheetsController;
use App\Http\Controllers\OtpController;
use App\Http\Controllers\RecordController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');

Route::get('/.well-known/appspecific/com.chrome.devtools.json', function () {
    return response()->noContent();
});

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthController::class, 'create'])
        ->name('login');

    Route::post('/login', [AuthController::class, 'store'])
        ->middleware('throttle:login')
        ->name('login.store');

    Route::get('/register', [AuthController::class, 'register'])
        ->name('register');

    Route::post('/register/send-otp', [AuthController::class, 'sendRegisterOtp'])
        ->middleware('throttle:6,1')
        ->name('register.send-otp');

    Route::get('/register/verify-otp', [AuthController::class, 'showRegisterVerify'])
        ->name('register.verify');

    Route::post('/register/verify-otp', [AuthController::class, 'verifyRegisterOtp'])
        ->middleware('throttle:6,1')
        ->name('register.verify.submit');

    Route::post('/register/resend-otp', [AuthController::class, 'resendRegisterOtp'])
        ->middleware('throttle:3,1')
        ->name('register.resend-otp');

    Route::get('/auth/google', [AuthController::class, 'redirectToGoogle'])
        ->name('google.redirect');

    Route::get('/auth/google/callback', [AuthController::class, 'handleGoogleCallback'])
        ->name('google.callback');
});

Route::middleware('auth')->group(function (): void {
    Route::post('/logout', [AuthController::class, 'destroy'])
        ->name('logout');

    Route::view('/dashboard', 'dashboard')
        ->name('dashboard');

    Route::get('/phone', [OtpController::class, 'create'])
        ->name('otp.phone');

    Route::post('/phone/send-otp', [OtpController::class, 'send'])
        ->middleware('throttle:6,1')
        ->name('otp.send');

    Route::get('/verify-otp', [OtpController::class, 'showVerify'])
        ->name('otp.verify');

    Route::post('/verify-otp', [OtpController::class, 'verify'])
        ->middleware('throttle:6,1')
        ->name('otp.verify.submit');

    Route::post('/verify-otp/resend', [OtpController::class, 'resend'])
        ->middleware('throttle:3,1')
        ->name('otp.resend');

    Route::view('/whatsapp', 'whatsapp.index')
        ->name('whatsapp.index');

    Route::get('/records', [RecordController::class, 'page'])
        ->name('records.page');

    Route::get('/records/data', [RecordController::class, 'index'])
        ->name('records.data');

    Route::post('/records', [RecordController::class, 'store'])
        ->name('records.store');

    Route::get('/records/{record}', [RecordController::class, 'show'])
        ->name('records.show');

    Route::put('/records/{record}', [RecordController::class, 'update'])
        ->name('records.update');

    Route::delete('/records/{record}', [RecordController::class, 'destroy'])
        ->name('records.destroy');

    Route::view('/customers', 'customers.index')
        ->name('customers.index');

    Route::view('/customers/create', 'customers.create')
        ->name('customers.create');

    Route::view('/customers/{id}', 'customers.show')
        ->name('customers.show');

    Route::view('/customers/{id}/edit', 'customers.edit')
        ->name('customers.edit');

    Route::get('/google-sheets', [GoogleSheetsController::class, 'index'])
        ->name('google-sheets.index');

    Route::get('/auth/google/calendar/connect', [GoogleCalendarController::class, 'connect'])
        ->name('google.calendar.connect');

    Route::get('/auth/google/calendar/callback', [GoogleCalendarController::class, 'callback'])
        ->name('google.calendar.callback');

    Route::post('/google-calendar/events', [GoogleCalendarController::class, 'storeEvent'])
        ->name('google.calendar.events.store');

    Route::prefix('admin')
        ->name('admin.')
        ->middleware('admin')
        ->group(function (): void {
            Route::get('/', [AdminController::class, 'index'])
                ->name('index');

            Route::post('/accounts', [AdminController::class, 'storeAccount'])
                ->name('accounts.store');

            Route::put('/accounts/{user}', [AdminController::class, 'updateAccount'])
                ->name('accounts.update');

            Route::delete('/accounts/{user}', [AdminController::class, 'deleteAccount'])
                ->name('accounts.destroy');

            Route::post('/roles', [AdminController::class, 'storeRole'])
                ->name('roles.store');

            Route::put('/roles/{role}', [AdminController::class, 'updateRole'])
                ->name('roles.update');

            Route::delete('/roles/{role}', [AdminController::class, 'deleteRole'])
                ->name('roles.destroy');
        });
});
