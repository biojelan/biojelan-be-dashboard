<?php

use App\Http\Controllers\Kilang\DashboardController;
use App\Http\Controllers\Kilang\FinanceController;
use App\Http\Controllers\Kilang\OperationsController;
use App\Http\Controllers\Kilang\UserManagementController;
use App\Http\Middleware\EnsureKilangAccess;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified', EnsureKilangAccess::class])->group(function (): void {
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::prefix('kilang')->name('kilang.')->group(function (): void {
        Route::middleware('can:kilang-users')->group(function (): void {
            Route::get('users', [UserManagementController::class, 'index'])->name('users.index');
            Route::post('users', [UserManagementController::class, 'store'])->name('users.store');
            Route::patch('users/{user}', [UserManagementController::class, 'update'])->name('users.update');
            Route::delete('users/{user}', [UserManagementController::class, 'destroy'])->name('users.destroy');
            Route::post('agent-requests/{application}/review', [UserManagementController::class, 'review'])->name('applications.review');
        });
        Route::middleware('can:kilang-operations')->group(function (): void {
            Route::get('operations', [OperationsController::class, 'index'])->name('operations.index');
            Route::post('pickups', [OperationsController::class, 'store'])->name('pickups.store');
            Route::patch('operations/threshold', [OperationsController::class, 'threshold'])->name('operations.threshold');
        });
        Route::middleware('can:kilang-finance')->group(function (): void {
            Route::get('finance', [FinanceController::class, 'index'])->name('finance.index');
            Route::post('finance/entries', [FinanceController::class, 'store'])->name('finance.store');
            Route::post('finance/prices', [FinanceController::class, 'price'])->name('finance.price');
            Route::get('finance/report.pdf', [FinanceController::class, 'pdf'])->name('finance.pdf');
        });
    });
});

require __DIR__.'/settings.php';
