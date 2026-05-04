<?php

<<<<<<< HEAD
use App\Http\Controllers\AuthenticationController;
=======
use App\Http\Controllers\OrderController;
use App\Http\Controllers\StripeWebhookController;
>>>>>>> b9a1da7 (feature/RI-468: Add one time payment with Cashier and fixes)
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
Route::prefix('auth')->group(function () {

    Route::post('/register', [AuthenticationController::class, 'store']);
    Route::post('/login', [AuthenticationController::class, 'login']);
    Route::post('/forgot-password', [AuthenticationController::class, 'forgotPassword']);
    Route::post('/reset-password', [AuthenticationController::class, 'resetPassword']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthenticationController::class, 'logoutUser']);
        Route::get('/me', [AuthenticationController::class, 'me']);
        Route::post('/change-password', [AuthenticationController::class, 'changePassword']);
    });

});

Route::middleware('auth:sanctum')->group(function () {
    Route::apiResources([
        'products' => ProductController::class
    ]);
    Route::get    ('orders',             [OrderController::class, 'index']);
    Route::post   ('orders',             [OrderController::class, 'store']);
    Route::get    ('orders/{id}',        [OrderController::class, 'show']);
    Route::post   ('orders/{id}/confirm',[OrderController::class, 'confirm']);
});

Route::post('webhooks/stripe', [StripeWebhookController::class, 'handle'])
    ->withoutMiddleware(['throttle:api']);
