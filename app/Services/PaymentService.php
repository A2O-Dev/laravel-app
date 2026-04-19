<?php

namespace App\Services;

use App\Contracts\Purchasable;
use App\Models\Order;
use App\Models\User;
use App\Repositories\OrderRepository;
use Exception;
use Laravel\Cashier\Cashier;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;

class PaymentService extends BaseService {

    public function __construct(
        private OrderRepository $orderRepository
    ) {
        parent::__construct();
    }

    public function createOrder(User $user, Purchasable $product, string $currency = 'usd'): array {
        $order        = null;
        $clientSecret = null;

        try {
            $customer = $user->createOrGetStripeCustomer();

            $stripe        = Cashier::stripe();
            $paymentIntent = $stripe->paymentIntents->create([
                'amount'                    => $product->getPriceInCents(),
                'currency'                  => $currency,
                'customer'                  => $customer->id,
                'automatic_payment_methods' => ['enabled' => true],
                'metadata'                  => array_merge($product->getMetadata(), [
                    'user_id' => $user->id,
                ]),
            ]);

            $order = $this->orderRepository->create([
                'user_id'                  => $user->id,
                'orderable_type'           => get_class($product),
                'orderable_id'             => $product->id,
                'stripe_payment_intent_id' => $paymentIntent->id,
                'amount'                   => $product->getPriceInCents(),
                'currency'                 => $currency,
                'status'                   => Order::STATUS_PENDING,
                'metadata'                 => $product->getMetadata(),
            ]);

            $clientSecret = $paymentIntent->client_secret;

        } catch (Exception $e) {
            $this->errors->add('create-order', $e->getMessage());
        }

        return ['order' => $order, 'client_secret' => $clientSecret];
    }

    public function confirmOrder(User $user, int $orderId): ?Order {
        $order = $this->orderRepository->findById($orderId);

        if (is_null($order)) {
            $this->errors->add('not-found', 'Order not found');
            return null;
        }

        if ($order->user_id !== $user->id) {
            $this->errors->add('unauthorized', 'Order does not belong to this user');
            return $order;
        }

        if (!in_array($order->status, [Order::STATUS_PENDING, Order::STATUS_PROCESSING])) {
            $this->errors->add('invalid-status', 'Order cannot be confirmed in its current status');
            return $order;
        }

        try {
            $stripe        = Cashier::stripe();
            $paymentIntent = $stripe->paymentIntents->retrieve($order->stripe_payment_intent_id);

            match ($paymentIntent->status) {
                'succeeded'                => $this->orderRepository->markAsPaid($order),
                'processing'               => $this->orderRepository->updateStatus($order, Order::STATUS_PROCESSING),
                default                    => $this->orderRepository->updateStatus($order, Order::STATUS_FAILED),
            };

            $order->refresh();

        } catch (Exception $e) {
            $this->errors->add('confirm-order', $e->getMessage());
        }

        return $order;
    }

    public function handleWebhookEvent(string $payload, string $signature): void {
        try {
            $event = Webhook::constructEvent(
                $payload,
                $signature,
                config('cashier.webhook.secret')
            );
        } catch (SignatureVerificationException $e) {
            $this->errors->add('invalid-signature', 'Webhook signature verification failed');
            return;
        }

        try {
            match ($event->type) {
                'payment_intent.succeeded'       => $this->handlePaymentIntentSucceeded($event->data->object),
                'payment_intent.payment_failed'  => $this->handlePaymentIntentFailed($event->data->object),
                default                          => null,
            };
        } catch (Exception $e) {
            $this->errors->add('webhook', $e->getMessage());
        }
    }

    private function handlePaymentIntentSucceeded(object $paymentIntent): void {
        $order = $this->orderRepository->findByPaymentIntentId($paymentIntent->id);
        if ($order && $order->status !== Order::STATUS_PAID) {
            $this->orderRepository->markAsPaid($order);
        }
    }

    private function handlePaymentIntentFailed(object $paymentIntent): void {
        $order = $this->orderRepository->findByPaymentIntentId($paymentIntent->id);
        if ($order && $order->status !== Order::STATUS_PAID) {
            $this->orderRepository->updateStatus($order, Order::STATUS_FAILED);
        }
    }
}
