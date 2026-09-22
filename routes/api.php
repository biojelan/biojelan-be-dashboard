<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Middleware\EnsureApiTokenIsValid;
use Illuminate\Support\Facades\Route;

Route::middleware('throttle:api-auth')->name('api.')->group(function (): void {
    Route::post('/register', [AuthController::class, 'register'])->name('register');
    Route::post('/login', [AuthController::class, 'login'])->name('login');
    Route::post('/kilang-login', [AuthController::class, 'kilangLogin'])->name('kilang-login');
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])->name('forgot-password');
});

Route::get('/user/agen', [UserController::class, 'agen'])->name('api.user.agen');

Route::middleware(['auth:sanctum', EnsureApiTokenIsValid::class])->name('api.')->group(function (): void {
    Route::delete('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::patch('/update-password', [AuthController::class, 'updatePassword'])
        ->middleware('throttle:api-auth')->name('update-password');
    Route::get('/user', [UserController::class, 'show'])->name('user.show');
    Route::patch('/user', [UserController::class, 'update'])->name('user.update');
    Route::delete('/user', [UserController::class, 'destroy'])->name('user.destroy');
});
