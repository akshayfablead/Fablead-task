<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\RecordController;
use Illuminate\Support\Facades\Route;

// Redirect root URL to dashboard.
Route::redirect('/', '/dashboard');

// Guest routes.
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'create'])->name('login');
    Route::post('/login', [AuthController::class, 'store'])->middleware('throttle:login')->name('login.store');
});

// Authenticated routes.
Route::middleware('auth')->group(function () {
    // Authentication.
    Route::post('/logout', [AuthController::class, 'destroy'])->name('logout');

    // Dashboard.
    Route::view('/dashboard', 'dashboard')->name('dashboard');

    // Records.
    // Route::get('/records', [RecordController::class, 'page'])->name('records.page');
    // Route::get('/records/data', [RecordController::class, 'index'])->name('records.data');
    // Route::post('/records', [RecordController::class, 'store'])->name('records.store');
    // Route::get('/records/{record}', [RecordController::class, 'show'])->whereNumber('record')->name('records.show');
    // Route::put('/records/{record}', [RecordController::class, 'update'])->whereNumber('record')->name('records.update');
    // Route::delete('/records/{record}', [RecordController::class, 'destroy'])->whereNumber('record')->name('records.destroy');


    Route::get('/records', [RecordController::class, 'page'])->name('records.page');
    Route::get('/records/data', [RecordController::class,'index',])->name('records.data');
    Route::post('/records', [RecordController::class,'store'])->name('records.store');
    Route::get('/records/{record}', [RecordController::class, 'show'])->name('records.show');
    Route::put('/records/{record}', [RecordController::class,'update',])->name('records.update');
    Route::delete('/records/{record}', [RecordController::class,'destroy',])->name('records.destroy');

    // Admin routes.
    Route::prefix('admin')->name('admin.')->middleware('admin')->group(function () {
        // Admin dashboard.
        Route::get('/', [AdminController::class, 'index'])->name('index');

        // Account management.
        Route::post('/accounts', [AdminController::class, 'storeAccount'])->name('accounts.store');
        Route::put('/accounts/{user}', [AdminController::class, 'updateAccount'])->name('accounts.update');
        Route::delete('/accounts/{user}', [AdminController::class, 'deleteAccount'])->name('accounts.destroy');

        // Role management.
        Route::post('/roles', [AdminController::class, 'storeRole'])->name('roles.store');
        Route::put('/roles/{role}', [AdminController::class, 'updateRole'])->name('roles.update');
        Route::delete('/roles/{role}', [AdminController::class, 'deleteRole'])->name('roles.destroy');
    });
});
