<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\StripeWebhookController;
use App\Http\Controllers\SubscriptionController;
use Illuminate\Support\Facades\Route;

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

Route::group(['prefix' => 'auth'], function () {
    Route::post('/register', [AuthController::class, 'store'])->name('register');
    Route::post('/login', [AuthController::class, 'login'])->name('login');
});

Route::middleware('auth:sanctum')->group(function () {
    Route::post  ('subscriptions', [SubscriptionController::class, 'store']);
    Route::delete('subscriptions', [SubscriptionController::class, 'destroy']);
});

Route::middleware(['auth:sanctum', 'subscribed'])->group(function () {
    Route::apiResources([
        'products' => ProductController::class,
    ]);

    Route::get    ('orders',              [OrderController::class, 'index']);
    Route::post   ('orders',              [OrderController::class, 'store']);
    Route::get    ('orders/{id}',         [OrderController::class, 'show']);
    Route::post   ('orders/{id}/confirm', [OrderController::class, 'confirm']);
});

Route::post('webhooks/stripe', [StripeWebhookController::class, 'handle'])
    ->withoutMiddleware(['throttle:api']);

Route::post('webhooks/stripe/subscriptions', '\Laravel\Cashier\Http\Controllers\WebhookController@handleWebhook')
    ->withoutMiddleware(['throttle:api']);
