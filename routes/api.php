<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/
// Public authentication routes
Route::group(['prefix' => 'auth'], function () {
    Route::post('/register', [AuthController::class, 'store'])->name('register');
    Route::post('/login', [AuthController::class, 'login'])->name('login');
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])->name('password.forgot');
    Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('password.reset');
});

// Protected routes - require authentication
Route::middleware(['auth:sanctum'])->group(function () {
    // Auth routes
    Route::group(['prefix' => 'auth'], function () {
        Route::post('/logout', [AuthController::class, 'logoutUser'])->name('logout');
        Route::get('/me', [AuthController::class, 'me'])->name('user.me');
        Route::post('/change-password', [AuthController::class, 'changePassword'])->name('password.change');
    });
});

Route::middleware('client')->group(function () {
    Route::apiResources([
        'products' => ProductController::class
    ]);
});
