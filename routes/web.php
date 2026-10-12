<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AdminImportedUsersController;
use App\Http\Controllers\AdminUserImportController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CallController;
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

    Route::view('/ai-tools', 'ai-tools.index')
        ->name('ai-tools.index');

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

    Route::get('/calls', [CallController::class, 'index'])->name('calls.index');
    Route::post('/calls/start', [CallController::class, 'start'])
        ->middleware('throttle:10,1')
        ->name('calls.start');
    Route::post('/calls/signal', [CallController::class, 'signal'])
        ->middleware('throttle:120,1')
        ->name('calls.signal');

    Route::prefix('admin')
        ->name('admin.')
        ->middleware('admin')
        ->group(function (): void {
            Route::get('/imported-users', [AdminImportedUsersController::class, 'index'])
                ->name('imported-users.index');

            Route::get('/imported-users/data', [AdminImportedUsersController::class, 'data'])
                ->name('imported-users.data');

            Route::put('/imported-users/{user}', [AdminImportedUsersController::class, 'update'])
                ->name('imported-users.update');

            Route::delete('/imported-users/{user}', [AdminImportedUsersController::class, 'destroy'])
                ->name('imported-users.destroy');

            Route::get('/user-imports', [AdminUserImportController::class, 'index'])
                ->name('user-imports.index');

            Route::post('/user-imports', [AdminUserImportController::class, 'upload'])
                ->middleware('throttle:5,1')
                ->name('user-imports.upload');

            Route::get('/user-imports/{userImport}', [AdminUserImportController::class, 'show'])
                ->name('user-imports.show');

            Route::post('/user-imports/{userImport}/confirm', [AdminUserImportController::class, 'confirm'])
                ->middleware('throttle:5,1')
                ->name('user-imports.confirm');

            Route::get('/user-imports/{userImport}/status', [AdminUserImportController::class, 'status'])
                ->name('user-imports.status');

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
