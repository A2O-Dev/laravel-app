<?php

namespace Tests\Unit\Services;

use App\Models\Order;
use App\Repositories\OrderRepository;
use App\Services\OrderService;
use Illuminate\Database\Eloquent\Collection;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class OrderServiceTest extends TestCase {

    private MockInterface $orderRepository;
    private OrderService $orderService;

    protected function setUp(): void {
        parent::setUp();

        $this->orderRepository = Mockery::mock(OrderRepository::class);
        $this->orderService    = new OrderService($this->orderRepository);
    }

    protected function tearDown(): void {
        Mockery::close();
        parent::tearDown();
    }

    public function test_getByUser_returns_paginated_orders(): void {
        $userId   = 1;
        $pageSize = 10;
        $offset   = 0;
        $orders   = new Collection([new Order(), new Order()]);

        $this->orderRepository
            ->shouldReceive('getByUserId')
            ->once()
            ->with($userId, $pageSize, $offset)
            ->andReturn($orders);

        $result = $this->orderService->getByUser($userId, $pageSize, $offset);

        $this->assertSame($orders, $result);
        $this->assertCount(2, $result);
    }

    public function test_countByUser_returns_total(): void {
        $userId = 1;

        $this->orderRepository
            ->shouldReceive('countByUserId')
            ->once()
            ->with($userId)
            ->andReturn(5);

        $result = $this->orderService->countByUser($userId);

        $this->assertSame(5, $result);
    }

    public function test_getByIdForUser_returns_order_when_found_and_belongs_to_user(): void {
        $userId  = 1;
        $orderId = 10;
        $order   = new Order(['user_id' => $userId]);

        $this->orderRepository
            ->shouldReceive('findById')
            ->once()
            ->with($orderId)
            ->andReturn($order);

        $result = $this->orderService->getByIdForUser($orderId, $userId);

        $this->assertSame($order, $result);
        $this->assertFalse($this->orderService->hasErrors());
    }

    public function test_getByIdForUser_adds_not_found_error_when_order_does_not_exist(): void {
        $this->orderRepository
            ->shouldReceive('findById')
            ->once()
            ->andReturn(null);

        $result = $this->orderService->getByIdForUser(99, 1);

        $this->assertNull($result);
        $this->assertTrue($this->orderService->hasErrors());
        $this->assertTrue($this->orderService->getErrors()->has('not-found'));
    }

    public function test_getByIdForUser_adds_unauthorized_error_when_order_belongs_to_different_user(): void {
        $order          = new Order();
        $order->user_id = 2;

        $this->orderRepository
            ->shouldReceive('findById')
            ->once()
            ->andReturn($order);

        $result = $this->orderService->getByIdForUser(10, 1);

        $this->assertNull($result);
        $this->assertTrue($this->orderService->hasErrors());
        $this->assertTrue($this->orderService->getErrors()->has('unauthorized'));
    }
}
