<?php

namespace Tests\Unit\Services;

use App\Contracts\Purchasable;
use App\Models\Order;
use App\Models\User;
use App\Repositories\OrderRepository;
use App\Services\PaymentService;
use Exception;
use Mockery;
use Mockery\MockInterface;
use Stripe\Event;
use Stripe\Exception\SignatureVerificationException;
use Stripe\StripeClient;
use Tests\TestCase;

class PaymentServiceTest extends TestCase {

    private MockInterface $orderRepository;
    private MockInterface $stripeClient;
    private MockInterface $paymentIntents;
    private PaymentService $paymentService;

    protected function setUp(): void {
        parent::setUp();

        $this->orderRepository = Mockery::mock(OrderRepository::class);
        $this->paymentIntents = Mockery::mock();
        $this->stripeClient = Mockery::mock(StripeClient::class);

        $this->stripeClient->paymentIntents = $this->paymentIntents;

        $this->paymentService = new PaymentService(
            $this->orderRepository,
            $this->stripeClient
        );
    }

    protected function tearDown(): void {
        Mockery::close();
        parent::tearDown();
    }

    public function test_createOrder_returns_order_and_client_secret_on_success(): void {
        $user    = $this->mockUser(1);
        $product = $this->mockProduct(500, 'Test Product');
        $order   = new Order();

        $fakeCustomer      = (object) ['id' => 'cus_test'];
        $fakePaymentIntent = (object) ['id' => 'pi_test', 'client_secret' => 'secret_test'];

        $user->shouldReceive('createOrGetStripeCustomer')->once()->andReturn($fakeCustomer);

        $this->paymentIntents
            ->shouldReceive('create')
            ->once()
            ->andReturn($fakePaymentIntent);

        $this->orderRepository
            ->shouldReceive('create')
            ->once()
            ->andReturn($order);

        $result = $this->paymentService->createOrder($user, $product, 'usd');

        $this->assertSame($order, $result['order']);
        $this->assertSame('secret_test', $result['client_secret']);
        $this->assertFalse($this->paymentService->hasErrors());
    }

    public function test_createOrder_adds_error_when_stripe_throws_exception(): void {
        $user    = $this->mockUser(1);
        $product = $this->mockProduct(500, 'Test Product');

        $user->shouldReceive('createOrGetStripeCustomer')
            ->once()
            ->andThrow(new Exception('Stripe error'));

        $result = $this->paymentService->createOrder($user, $product, 'usd');

        $this->assertNull($result['order']);
        $this->assertNull($result['client_secret']);
        $this->assertTrue($this->paymentService->hasErrors());
        $this->assertTrue($this->paymentService->getErrors()->has('create-order'));
    }

    public function test_confirmOrder_adds_error_when_order_not_found(): void {
        $user = $this->mockUser(1);

        $this->orderRepository
            ->shouldReceive('findById')
            ->once()
            ->with(99)
            ->andReturn(null);

        $result = $this->paymentService->confirmOrder($user, 99);

        $this->assertNull($result);
        $this->assertTrue($this->paymentService->getErrors()->has('not-found'));
    }

    public function test_confirmOrder_adds_error_when_order_belongs_to_different_user(): void {
        $user  = $this->mockUser(1);
        $order = $this->makeOrder(['user_id' => 2, 'status' => Order::PENDING]);

        $this->orderRepository->shouldReceive('findById')->once()->with(10)->andReturn($order);

        $this->paymentService->confirmOrder($user, 10);

        $this->assertTrue($this->paymentService->getErrors()->has('unauthorized'));
    }

    public function test_confirmOrder_adds_error_when_order_status_is_not_confirmable(): void {
        $user  = $this->mockUser(1);
        $order = $this->makeOrder(['user_id' => 1, 'status' => Order::PAID]);

        $this->orderRepository->shouldReceive('findById')->once()->with(10)->andReturn($order);

        $this->paymentService->confirmOrder($user, 10);

        $this->assertTrue($this->paymentService->getErrors()->has('invalid-status'));
    }

    public function test_confirmOrder_marks_as_paid_when_payment_intent_succeeded(): void {
        $user  = $this->mockUser(1);
        $order = $this->mockOrder(['user_id' => 1, 'status' => Order::PENDING, 'stripe_payment_intent_id' => 'pi_test']);

        $fakePaymentIntent = (object) ['status' => 'succeeded'];

        $this->orderRepository->shouldReceive('findById')->once()->andReturn($order);
        $this->paymentIntents->shouldReceive('retrieve')->once()->with('pi_test')->andReturn($fakePaymentIntent);
        $this->orderRepository->shouldReceive('markAsPaid')->once()->with($order);
        $order->shouldReceive('refresh')->once();

        $this->paymentService->confirmOrder($user, 10);

        $this->assertFalse($this->paymentService->hasErrors());
    }

    public function test_confirmOrder_updates_to_processing_when_payment_intent_is_processing(): void {
        $user  = $this->mockUser(1);
        $order = $this->mockOrder(['user_id' => 1, 'status' => Order::PENDING, 'stripe_payment_intent_id' => 'pi_test']);

        $fakePaymentIntent = (object) ['status' => 'processing'];

        $this->orderRepository->shouldReceive('findById')->once()->andReturn($order);
        $this->paymentIntents->shouldReceive('retrieve')->once()->andReturn($fakePaymentIntent);
        $this->orderRepository->shouldReceive('updateStatus')->once()->with($order, Order::PROCESSING);
        $order->shouldReceive('refresh')->once();

        $this->paymentService->confirmOrder($user, 10);

        $this->assertFalse($this->paymentService->hasErrors());
    }

    public function test_confirmOrder_adds_error_when_stripe_throws_exception(): void {
        $user  = $this->mockUser(1);
        $order = $this->mockOrder(['user_id' => 1, 'status' => Order::PENDING, 'stripe_payment_intent_id' => 'pi_test']);

        $this->orderRepository->shouldReceive('findById')->once()->andReturn($order);
        $this->paymentIntents->shouldReceive('retrieve')->once()->andThrow(new Exception('Stripe error'));
        $order->shouldReceive('refresh')->never();

        $this->paymentService->confirmOrder($user, 10);

        $this->assertTrue($this->paymentService->getErrors()->has('confirm-order'));
    }

    public function test_handleWebhookEvent_adds_error_when_signature_is_invalid(): void {
        $service = Mockery::mock(PaymentService::class, [$this->orderRepository, $this->stripeClient])
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $service->shouldReceive('verifyWebhookSignature')
            ->once()
            ->andThrow(Mockery::mock(SignatureVerificationException::class));

        $service->handleWebhookEvent('payload', 'bad_signature');

        $this->assertTrue($service->getErrors()->has('invalid-signature'));
    }

    public function test_handleWebhookEvent_handles_payment_intent_succeeded(): void {
        $paymentIntent = (object) ['id' => 'pi_test'];
        $event         = $this->fakeEvent('payment_intent.succeeded', $paymentIntent);
        $order         = $this->makeOrder(['status' => Order::PENDING]);

        $service = $this->makePartialPaymentService($event);

        $this->orderRepository->shouldReceive('findByPaymentIntentId')->once()->with('pi_test')->andReturn($order);
        $this->orderRepository->shouldReceive('markAsPaid')->once()->with($order);

        $service->handleWebhookEvent('payload', 'signature');

        $this->assertFalse($service->hasErrors());
    }

    public function test_handleWebhookEvent_handles_payment_intent_failed(): void {
        $paymentIntent = (object) ['id' => 'pi_test'];
        $event         = $this->fakeEvent('payment_intent.payment_failed', $paymentIntent);
        $order         = $this->makeOrder(['status' => Order::PENDING]);

        $service = $this->makePartialPaymentService($event);

        $this->orderRepository->shouldReceive('findByPaymentIntentId')->once()->with('pi_test')->andReturn($order);
        $this->orderRepository->shouldReceive('updateStatus')->once()->with($order, Order::FAILED);

        $service->handleWebhookEvent('payload', 'signature');

        $this->assertFalse($service->hasErrors());
    }

    public function test_handleWebhookEvent_ignores_unknown_event_types(): void {
        $event   = $this->fakeEvent('customer.created', (object) []);
        $service = $this->makePartialPaymentService($event);

        $this->orderRepository->shouldReceive('findByPaymentIntentId')->never();

        $service->handleWebhookEvent('payload', 'signature');

        $this->assertFalse($service->hasErrors());
    }

    private function mockUser(int $id): MockInterface {
        $user = Mockery::mock(User::class)->makePartial();
        $user->forceFill(['id' => $id]);
        return $user;
    }

    private function mockProduct(int $priceInCents, string $name): MockInterface {
        $product = Mockery::mock(Purchasable::class);
        $product->id = 1;
        $product->shouldReceive('getPriceInCents')->andReturn($priceInCents);
        $product->shouldReceive('getDisplayName')->andReturn($name);
        $product->shouldReceive('getMetadata')->andReturn([]);
        return $product;
    }

    private function makeOrder(array $attributes): Order {
        $order = new Order();
        foreach ($attributes as $key => $value) {
            $order->$key = $value;
        }
        return $order;
    }

    private function mockOrder(array $attributes): MockInterface {
        $order = Mockery::mock(Order::class)->makePartial();
        foreach ($attributes as $key => $value) {
            $order->$key = $value;
        }
        return $order;
    }

    private function fakeEvent(string $type, object $dataObject): Event {
        return Event::constructFrom([
            'id'   => 'evt_test',
            'type' => $type,
            'data' => ['object' => (array) $dataObject],
        ]);
    }

    private function makePartialPaymentService(object $event): MockInterface {
        $service = Mockery::mock(PaymentService::class, [$this->orderRepository, $this->stripeClient])
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $service->shouldReceive('verifyWebhookSignature')->once()->andReturn($event);

        return $service;
    }
}
