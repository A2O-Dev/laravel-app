<?php

use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn() => redirect('/login'));

Route::middleware(['guest.sanctum'])->group(function () {
    Route::get('/login',                  [UserController::class, 'login'])->name('login');
    Route::get('/register',               [UserController::class, 'register'])->name('register');
    Route::get('/forgot-password',        [UserController::class, 'forgotPassword'])->name('password.request');
    Route::get('/reset-password/{token}', [UserController::class, 'resetPassword'])->name('password.reset');
});

Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/dashboard',       [UserController::class, 'dashboard'])->name('dashboard');
    Route::get('/change-password', [UserController::class, 'changePassword'])->name('password.change');
});
