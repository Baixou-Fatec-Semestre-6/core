<?php

use App\Http\Controllers\Admin\AuthController;
use App\Livewire\Admin\Admins;
use App\Livewire\Admin\Dashboard;
use App\Livewire\Admin\Monitoring;
use App\Livewire\Admin\Products;
use App\Livewire\Admin\Subscriptions;
use App\Livewire\Admin\Users;
use Illuminate\Support\Facades\Route;

Route::name('admin.')->group(function () {

    // 1. Guest Authentication Routes
    Route::middleware('guest')->group(function () {
        Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
        Route::post('/login', [AuthController::class, 'login'])->name('login.submit');

        Route::get('/forgot-password', [AuthController::class, 'showForgotPassword'])
            ->name('password.request');

        Route::post('/forgot-password', [AuthController::class, 'sendResetLink'])
            ->name('password.email');

        Route::get('/reset-password/{token}', [AuthController::class, 'showResetPassword'])
            ->name('password.reset');

        Route::post('/reset-password', [AuthController::class, 'resetPassword'])
            ->name('password.update');
    });

    // 2. Protected Administrative Routes
    Route::middleware(['admin'])->group(function () {

        Route::get('/', Dashboard\Index::class)->name('dashboard');

        // Logout
        Route::get('/logout', [AuthController::class, 'logout'])->name('logout');

        // // Products Management (CRUD + Toggle Monitoring)
        Route::get('products', Products\Index::class)->name('products.index');
        // Route::patch('products/{product}/toggle-monitoring', [ProductController::class, 'toggleMonitoring'])
        //     ->name('products.toggle-monitoring');

        Route::get('monitoring', Monitoring\Index::class)->name('monitoring.index');

        // // Users Management
        Route::get('users', Users\Index::class)->name('users.index');
        // Route::get('users', [UserController::class, 'index'])->name('users.index');
        // Route::patch('users/{user}/toggle-status', [UserController::class, 'toggleStatus'])
        //     ->name('users.toggle-status');

        // // Subscriptions
        Route::get('subscriptions', Subscriptions\Index::class)->name('subscriptions.index');

        // // Audit Log
        // Route::get('audit', [AuditLogController::class, 'index'])->name('audit.index');

        // // Admin Management (RBAC, CRUD, Password Reset Trigger)
        Route::get('admins', Admins\Index::class)->name('admins.index')->middleware('can:manage-admins');
    });
});
