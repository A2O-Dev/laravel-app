<?php

use App\Http\Controllers\OrderController;
use App\Http\Controllers\StripeWebhookController;
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

Route::middleware('client')->group(function () {
    Route::apiResources([
        'products' => ProductController::class
    ]);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get    ('orders',             [OrderController::class, 'index']);
    Route::post   ('orders',             [OrderController::class, 'store']);
    Route::get    ('orders/{id}',        [OrderController::class, 'show']);
    Route::post   ('orders/{id}/confirm',[OrderController::class, 'confirm']);
});

Route::post('webhooks/stripe', [StripeWebhookController::class, 'handle'])
    ->withoutMiddleware(['throttle:api']);
