<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Repositories\OrderRepository;
use Laravel\Cashier\Http\Controllers\WebhookController as CashierWebhookController;
use Symfony\Component\HttpFoundation\Response;

class StripeWebhookController extends CashierWebhookController {

    public function __construct(private OrderRepository $orderRepository) {
        parent::__construct();
    }

    protected function handlePaymentIntentSucceeded(array $payload): Response {
        $order = $this->orderRepository->findByPaymentIntentId($payload['data']['object']['id']);

        if ($order && $order->status !== Order::PAID) {
            $this->orderRepository->markAsPaid($order);
        }

        return $this->successMethod();
    }

    protected function handlePaymentIntentPaymentFailed(array $payload): Response {
        $order = $this->orderRepository->findByPaymentIntentId($payload['data']['object']['id']);

        if ($order && $order->status !== Order::PAID) {
            $this->orderRepository->updateStatus($order, Order::FAILED);
        }

        return $this->successMethod();
    }
}
