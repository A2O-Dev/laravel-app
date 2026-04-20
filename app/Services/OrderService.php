<?php

namespace App\Services;

use App\Models\Order;
use App\Repositories\OrderRepository;
use Illuminate\Database\Eloquent\Collection;

class OrderService extends BaseService {

    public function __construct(
        private OrderRepository $orderRepository
    ) {
        parent::__construct();
    }

    public function getByUser(int $userId, int $pageSize, int $offset): Collection {
        return $this->orderRepository->getByUserId($userId, $pageSize, $offset);
    }

    public function countByUser(int $userId): int {
        return $this->orderRepository->countByUserId($userId);
    }

    public function getByIdForUser(int $orderId, int $userId): ?Order {
        $order = $this->orderRepository->findById($orderId);

        if ($order === null) {
            $this->errors->add('not-found', 'Order not found');
            return null;
        }

        if ($order->user_id !== $userId) {
            $this->errors->add('unauthorized', 'Access denied');
            return null;
        }

        return $order;
    }
}
