<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\ApiController;
use App\Http\Controllers\GoogleCalendarController;
use App\Http\Controllers\GoogleSheetsController;
use App\Http\Controllers\RecordController;
use App\Http\Controllers\WhatsAppController;
use Illuminate\Support\Facades\Route;

Route::middleware('web')
    ->name('api.')
    ->group(function (): void {
        Route::get('/', [ApiController::class, 'home'])
            ->name('home');

        Route::middleware('guest')->group(function (): void {
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

        Route::middleware('auth')->group(function (): void {
            Route::post('/logout', [ApiController::class, 'logout'])
                ->name('logout');

            Route::get('/dashboard', [ApiController::class, 'dashboard'])
                ->name('dashboard');

            Route::post('/phone/send-otp', [ApiController::class, 'sendOtp'])
                ->middleware('throttle:6,1')
                ->name('otp.send');

            Route::get('/phone', [ApiController::class, 'phoneForm'])
                ->name('otp.phone');

            Route::get('/verify-otp', [ApiController::class, 'showVerifyOtp'])
                ->name('otp.verify');

            Route::post('/verify-otp', [ApiController::class, 'verifyOtp'])
                ->middleware('throttle:6,1')
                ->name('otp.verify.submit');

            Route::post('/verify-otp/resend', [ApiController::class, 'resendOtp'])
                ->middleware('throttle:3,1')
                ->name('otp.resend');

            Route::get('/records', [ApiController::class, 'recordsPage'])
                ->name('records.page');

            Route::get('/records/data', [ApiController::class, 'recordsIndex'])
                ->name('records.data');

            Route::post('/records', [RecordController::class, 'store'])
                ->name('records.store');

            Route::get('/records/{record}', [RecordController::class, 'show'])
                ->name('records.show');

            Route::put('/records/{record}', [RecordController::class, 'update'])
                ->name('records.update');

            Route::delete('/records/{record}', [RecordController::class, 'destroy'])
                ->name('records.destroy');

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

            Route::post('/whatsapp/send', [WhatsAppController::class, 'send'])
                ->name('whatsapp.send');

            Route::post('/whatsapp/send-template', [WhatsAppController::class, 'sendTemplate'])
                ->name('whatsapp.send-template');

            Route::get('/notifications', [NotificationController::class, 'index'])
                ->name('notifications.index');

            Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount'])
                ->name('notifications.unread-count');

            Route::patch('/notifications/{id}/read', [NotificationController::class, 'markAsRead'])
                ->name('notifications.read');

            Route::patch('/notifications/read-all', [NotificationController::class, 'markAllAsRead'])
                ->name('notifications.read-all');

            Route::get('/google-sheets', [GoogleSheetsController::class, 'index'])
                ->name('google-sheets.index');

            Route::get('/auth/google/calendar/connect', [ApiController::class, 'googleCalendarConnect'])
                ->name('google.calendar.connect');

            Route::get('/auth/google/calendar/callback', [ApiController::class, 'googleCalendarCallback'])
                ->name('google.calendar.callback');

            Route::post('/google-calendar/events', [GoogleCalendarController::class, 'storeEvent'])
                ->name('google.calendar.events.store');

            Route::prefix('admin')
                ->name('admin.')
                ->middleware('admin')
                ->group(function (): void {
                    Route::get('/', [ApiController::class, 'adminIndex'])
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
    });
