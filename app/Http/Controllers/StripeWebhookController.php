<?php

namespace App\Http\Controllers;

use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class StripeWebhookController extends Controller {

    public function handle(Request $request, PaymentService $paymentService): Response {
        $payload   = $request->getContent();
        $signature = $request->header('Stripe-Signature', '');

        $paymentService->handleWebhookEvent($payload, $signature);

        if ($paymentService->hasErrors()) {
            Log::warning('Stripe webhook error', ['errors' => $paymentService->getErrors()]);

            if ($paymentService->getErrors()->has('invalid-signature')) {
                return response('Webhook signature verification failed', 400);
            }
        }

        return response('Webhook received', 200);
    }
}
