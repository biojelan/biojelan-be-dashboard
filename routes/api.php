<?php

use App\Http\Controllers\Api\AgentClientTransactionController;
use App\Http\Controllers\Api\AgentDriverTransactionController;
use App\Http\Controllers\Api\AgentPickupController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ClientTransactionController;
use App\Http\Controllers\Api\DriverAgentTransactionController;
use App\Http\Controllers\Api\DriverPickupController;
use App\Http\Controllers\Api\PriceController;
use App\Http\Controllers\Api\UserController;
use App\Http\Middleware\EnsureApiRole;
use App\Http\Middleware\EnsureApiTokenIsValid;
use Illuminate\Support\Facades\Route;

Route::middleware('throttle:api-auth')->name('api.')->group(function (): void {
    Route::post('/register', [AuthController::class, 'register'])->name('register');
    Route::post('/login', [AuthController::class, 'login'])->name('login');
    Route::post('/kilang-login', [AuthController::class, 'kilangLogin'])->name('kilang-login');
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])->name('forgot-password');
});

Route::get('/user/agen', [UserController::class, 'agen'])->name('api.user.agen');
Route::get('/price', [PriceController::class, 'index'])->name('api.price.index');

Route::middleware(['auth:sanctum', EnsureApiTokenIsValid::class])->name('api.')->group(function (): void {
    Route::delete('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::patch('/update-password', [AuthController::class, 'updatePassword'])
        ->middleware('throttle:api-auth')->name('update-password');
    Route::get('/user', [UserController::class, 'show'])->name('user.show');
    Route::patch('/user', [UserController::class, 'update'])->name('user.update');
    Route::delete('/user', [UserController::class, 'destroy'])->name('user.destroy');

    Route::prefix('agent')->middleware(EnsureApiRole::class.':agent')->name('agent.')->group(function (): void {
        Route::post('/transaction', [AgentClientTransactionController::class, 'store'])->name('transaction.store');
        Route::get('/clients/transactions', [AgentClientTransactionController::class, 'index'])->name('clients.transactions.index');
        Route::post('/check-clients-email', [AgentClientTransactionController::class, 'checkByEmail'])->name('clients.check-email');
        Route::post('/check-clients-phone', [AgentClientTransactionController::class, 'checkByPhone'])->name('clients.check-phone');
        Route::post('/transaction/{transactionClient}/cancel', [AgentClientTransactionController::class, 'cancel'])->name('transaction.cancel');
        Route::get('/transactions', [AgentDriverTransactionController::class, 'index'])->name('transactions.index');
        Route::get('/driver/transactions', [AgentDriverTransactionController::class, 'index'])->name('driver-transactions.index');
        Route::post('/transaction/{transactionAgent}/accept', [AgentDriverTransactionController::class, 'accept'])->name('transaction.accept');
        Route::post('/transaction/{transactionAgent}/reject', [AgentDriverTransactionController::class, 'reject'])->name('transaction.reject');
        Route::post('/transaction/{transactionAgent}/cancel-accept', [AgentDriverTransactionController::class, 'acceptCancellation'])->name('transaction.cancel-accept');
        Route::post('/transaction/{transactionAgent}/cancel-reject', [AgentDriverTransactionController::class, 'rejectCancellation'])->name('transaction.cancel-reject');
        Route::get('/pickup/status', [AgentPickupController::class, 'show'])->name('pickup.status.show');
    });

    Route::prefix('driver')->middleware(EnsureApiRole::class.':driver')->name('driver.')->group(function (): void {
        Route::post('/transaction', [DriverAgentTransactionController::class, 'store'])->name('transaction.store');
        Route::get('/transactions', [DriverAgentTransactionController::class, 'index'])->name('transactions.index');
        Route::post('/transaction/{transactionAgent}/cancel', [DriverAgentTransactionController::class, 'cancel'])->name('transaction.cancel');
        Route::get('/pickup/status', [DriverPickupController::class, 'show'])->name('pickup.status.show');
        Route::patch('/pickup/status', [DriverPickupController::class, 'update'])->name('pickup.status.update');
    });

    Route::prefix('client')->middleware(EnsureApiRole::class.':client')->name('client.')->group(function (): void {
        Route::get('/transactions', [ClientTransactionController::class, 'index'])->name('transactions.index');
        Route::post('/transaction/{transactionClient}/accept', [ClientTransactionController::class, 'accept'])->name('transaction.accept');
        Route::post('/transaction/{transactionClient}/reject', [ClientTransactionController::class, 'reject'])->name('transaction.reject');
        Route::post('/transaction/{transactionClient}/cancel-accept', [ClientTransactionController::class, 'acceptCancellation'])->name('transaction.cancel-accept');
        Route::post('/transaction/{transactionClient}/cancel-reject', [ClientTransactionController::class, 'rejectCancellation'])->name('transaction.cancel-reject');
    });
});
