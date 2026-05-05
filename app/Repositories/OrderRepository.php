<?php

namespace App\Repositories;

use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;

class OrderRepository {

    public function create(array $data): Order {
        return Order::create($data);
    }

    public function findById(int $id): ?Order {
        return Order::find($id);
    }

    public function findByPaymentIntentId(string $paymentIntentId): ?Order {
        return Order::where('stripe_payment_intent_id', $paymentIntentId)->first();
    }

    public function updateStatus(Order $order, string $status): Order {
        $order->update(['status' => $status]);
        return $order;
    }

    public function markAsPaid(Order $order): Order {
        $order->update([
            'status'  => Order::PAID,
            'paid_at' => Carbon::now(),
        ]);
        return $order;
    }

    public function getByUserId(int $userId, int $limit = 10, int $offset = 0): Collection {
        return Order::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->offset($offset)
            ->get();
    }

    public function countByUserId(int $userId): int {
        return Order::where('user_id', $userId)->count();
    }
}
