<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\StripeWebhookController;
use App\Http\Controllers\SubscriptionController;

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

    Route::post('/register', [AuthController::class, 'store']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logoutUser']);
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/change-password', [AuthController::class, 'changePassword']);
    });

});

Route::middleware('auth:sanctum')->group(function () {
    Route::post  ('subscriptions', [SubscriptionController::class, 'store']);
    Route::delete('subscriptions', [SubscriptionController::class, 'destroy']);
});

Route::middleware('client')->group(function () {
    Route::apiResources([
        'products' => ProductController::class,
    ]);

    Route::get('orders', [OrderController::class, 'index']);
    Route::post('orders', [OrderController::class, 'store']);
    Route::get('orders/{id}', [OrderController::class, 'show']);
    Route::post('orders/{id}/confirm', [OrderController::class, 'confirm']);
});

Route::post('webhooks/stripe', [StripeWebhookController::class, 'handle'])
    ->withoutMiddleware(['throttle:api']);

Route::post('webhooks/stripe/subscriptions', '\Laravel\Cashier\Http\Controllers\WebhookController@handleWebhook')
    ->withoutMiddleware(['throttle:api']);
