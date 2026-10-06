<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\OtpController;
use App\Http\Controllers\RecordController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');

// Guest routes.
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'create'])
        ->name('login');

    Route::post('/login', [AuthController::class, 'store'])
        ->middleware('throttle:login')
        ->name('login.store');

    Route::get('/auth/google', [AuthController::class, 'redirectToGoogle'])
        ->name('google.redirect');

    Route::get('/auth/google/callback', [AuthController::class, 'handleGoogleCallback'])
        ->name('google.callback');
});

// Authenticated routes.
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'destroy'])
        ->name('logout');

    Route::view('/dashboard', 'dashboard')
        ->name('dashboard');

    // OTP routes.
    Route::get('/phone', [OtpController::class, 'create'])
        ->name('otp.phone');

    Route::post('/phone/send-otp', [OtpController::class, 'send'])
        ->name('otp.send');

    Route::get('/verify-otp', [OtpController::class, 'showVerify'])
        ->name('otp.verify');

    Route::post('/verify-otp', [OtpController::class, 'verify'])
        ->name('otp.verify.submit');

    Route::post('/verify-otp/resend', [OtpController::class, 'resend'])
        ->name('otp.resend');

    // Records.
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

    // Admin routes.
    Route::prefix('admin')
        ->name('admin.')
        ->middleware('admin')
        ->group(function () {
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
