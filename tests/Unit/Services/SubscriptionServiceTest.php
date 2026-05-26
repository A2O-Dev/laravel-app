<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\SubscriptionService;
use App\Models\User;
use Exception;
use Laravel\Cashier\Exceptions\IncompletePayment;
use Laravel\Cashier\Payment;
use Laravel\Cashier\Subscription;
use Mockery;

class SubscriptionServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
    }

    public function test_subscribe_returns_null_and_adds_error_if_user_already_subscribed(): void
    {
        // given
        $user = Mockery::mock(User::class);
        $user->shouldReceive('subscribed')->with('default')->once()->andReturn(true);

        $service = new SubscriptionService();

        // when
        $result = $service->subscribe($user);

        // then
        $this->assertNull($result);
        $this->assertTrue($service->hasErrors());
        $this->assertTrue($service->getErrors()->has('already-subscribed'));
    }

    public function test_subscribe_returns_client_secret_when_incomplete_payment_thrown(): void
    {
        // given
        $user                = Mockery::mock(User::class);
        $subscriptionBuilder = Mockery::mock();
        $payment             = Mockery::mock(Payment::class);
        $incompletePayment   = Mockery::mock(IncompletePayment::class)->makePartial();
        $incompletePayment->payment = $payment;

        $user->shouldReceive('subscribed')->with('default')->once()->andReturn(false);
        $user->shouldReceive('newSubscription')
            ->with('default', config('cashier.price_id'))
            ->once()
            ->andReturn($subscriptionBuilder);

        $subscriptionBuilder->shouldReceive('create')
            ->with(null, [], ['payment_behavior' => 'default_incomplete'])
            ->once()
            ->andThrow($incompletePayment);

        $payment->shouldReceive('clientSecret')->once()->andReturn('pi_test_secret');

        $service = new SubscriptionService();

        // when
        $result = $service->subscribe($user);

        // then
        $this->assertSame('pi_test_secret', $result);
        $this->assertFalse($service->hasErrors());
    }

    public function test_subscribe_returns_null_and_adds_error_if_exception_thrown(): void
    {
        // given
        $user                = Mockery::mock(User::class);
        $subscriptionBuilder = Mockery::mock();

        $user->shouldReceive('subscribed')->andReturn(false);
        $user->shouldReceive('newSubscription')->andReturn($subscriptionBuilder);
        $subscriptionBuilder->shouldReceive('create')->andThrow(new Exception('Stripe error'));

        $service = new SubscriptionService();

        // when
        $result = $service->subscribe($user);

        // then
        $this->assertNull($result);
        $this->assertTrue($service->hasErrors());
        $this->assertTrue($service->getErrors()->has('subscribe'));
    }

    public function test_confirm_returns_null_and_adds_error_when_no_subscription_found(): void
    {
        // given
        $user = Mockery::mock(User::class);
        $user->shouldReceive('subscription')->with('default')->once()->andReturn(null);

        $service = new SubscriptionService();

        // when
        $result = $service->confirm($user);

        // then
        $this->assertNull($result);
        $this->assertTrue($service->hasErrors());
        $this->assertTrue($service->getErrors()->has('not-found'));
    }

    public function test_confirm_syncs_stripe_status_and_returns_subscription(): void
    {
        // given
        $user         = Mockery::mock(User::class);
        $subscription = Mockery::mock(Subscription::class);

        $user->shouldReceive('subscription')->with('default')->once()->andReturn($subscription);
        $subscription->shouldReceive('syncStripeStatus')->once();
        $subscription->shouldReceive('refresh')->once()->andReturn($subscription);

        $service = new SubscriptionService();

        // when
        $result = $service->confirm($user);

        // then
        $this->assertSame($subscription, $result);
        $this->assertFalse($service->hasErrors());
    }

    public function test_confirm_returns_null_and_adds_error_when_stripe_throws_exception(): void
    {
        // given
        $user         = Mockery::mock(User::class);
        $subscription = Mockery::mock(Subscription::class);

        $user->shouldReceive('subscription')->with('default')->once()->andReturn($subscription);
        $subscription->shouldReceive('syncStripeStatus')->once()->andThrow(new Exception('Stripe error'));

        $service = new SubscriptionService();

        // when
        $result = $service->confirm($user);

        // then
        $this->assertNull($result);
        $this->assertTrue($service->hasErrors());
        $this->assertTrue($service->getErrors()->has('confirm'));
    }

    public function test_cancel_returns_false_and_adds_error_if_user_is_not_subscribed(): void
    {
        // given
        $user = Mockery::mock(User::class);
        $user->shouldReceive('subscribed')->with('default')->once()->andReturn(false);

        $service = new SubscriptionService();

        // when
        $result = $service->cancel($user);

        // then
        $this->assertFalse($result);
        $this->assertTrue($service->hasErrors());
        $this->assertTrue($service->getErrors()->has('not-subscribed'));
    }

    public function test_cancel_cancels_subscription_successfully(): void
    {
        // given
        $user         = Mockery::mock(User::class);
        $subscription = Mockery::mock();

        $user->shouldReceive('subscribed')->with('default')->once()->andReturn(true);
        $user->shouldReceive('subscription')->with('default')->once()->andReturn($subscription);
        $subscription->shouldReceive('cancel')->once();

        $service = new SubscriptionService();

        // when
        $result = $service->cancel($user);

        // then
        $this->assertTrue($result);
        $this->assertFalse($service->hasErrors());
    }

    public function test_cancel_returns_false_and_adds_error_if_cancel_throws_exception(): void
    {
        // given
        $user         = Mockery::mock(User::class);
        $subscription = Mockery::mock();

        $user->shouldReceive('subscribed')->andReturn(true);
        $user->shouldReceive('subscription')->andReturn($subscription);
        $subscription->shouldReceive('cancel')->andThrow(new Exception('Cancel failed'));

        $service = new SubscriptionService();

        // when
        $result = $service->cancel($user);

        // then
        $this->assertFalse($result);
        $this->assertTrue($service->hasErrors());
        $this->assertTrue($service->getErrors()->has('cancel'));
    }
}
