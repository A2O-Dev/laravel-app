<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\SubscriptionService;
use App\Models\User;
use Mockery;
use Exception;

class SubscriptionServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
    }

    public function test_it_returns_false_if_user_already_subscribed()
    {
        // given
        $user = Mockery::mock(User::class);
        $user->shouldReceive('subscribed')
            ->with('default')
            ->once()
            ->andReturn(true);

        $service = new SubscriptionService();

        // when
        $result = $service->subscribe($user, 'pm_test');

        // then
        $this->assertFalse($result);
    }

    public function test_it_subscribes_user_successfully()
    {
        // given
        $user = Mockery::mock(User::class);
        $subscriptionBuilder = Mockery::mock();

        $user->shouldReceive('subscribed')
            ->with('default')
            ->once()
            ->andReturn(false);

        $user->shouldReceive('newSubscription')
            ->with('default', config('cashier.price_id'))
            ->once()
            ->andReturn($subscriptionBuilder);

        $subscriptionBuilder->shouldReceive('create')
            ->with('pm_test')
            ->once();

        $service = new SubscriptionService();

        // when
        $result = $service->subscribe($user, 'pm_test');

        // then
        $this->assertTrue($result);
    }

    public function test_it_returns_false_if_subscription_creation_fails()
    {
        // given
        $user = Mockery::mock(User::class);
        $subscriptionBuilder = Mockery::mock();

        $user->shouldReceive('subscribed')
            ->andReturn(false);

        $user->shouldReceive('newSubscription')
            ->andReturn($subscriptionBuilder);

        $subscriptionBuilder->shouldReceive('create')
            ->andThrow(new Exception('Payment failed'));

        $service = new SubscriptionService();

        // when
        $result = $service->subscribe($user, 'pm_test');

        // then
        $this->assertFalse($result);
    }

    public function test_it_returns_false_if_user_is_not_subscribed_on_cancel()
    {
        // given
        $user = Mockery::mock(User::class);
        $user->shouldReceive('subscribed')
            ->with('default')
            ->once()
            ->andReturn(false);

        $service = new SubscriptionService();

        // when
        $result = $service->cancel($user);

        // then
        $this->assertFalse($result);
    }

    public function test_it_cancels_subscription_successfully()
    {
        // given
        $user = Mockery::mock(User::class);
        $subscription = Mockery::mock();

        $user->shouldReceive('subscribed')
            ->with('default')
            ->once()
            ->andReturn(true);

        $user->shouldReceive('subscription')
            ->with('default')
            ->once()
            ->andReturn($subscription);

        $subscription->shouldReceive('cancel')
            ->once();

        $service = new SubscriptionService();

        // when
        $result = $service->cancel($user);

        // then
        $this->assertTrue($result);
    }

    public function test_it_returns_false_if_cancel_fails()
    {
        // given
        $user = Mockery::mock(User::class);
        $subscription = Mockery::mock();

        $user->shouldReceive('subscribed')
            ->andReturn(true);

        $user->shouldReceive('subscription')
            ->andReturn($subscription);

        $subscription->shouldReceive('cancel')
            ->andThrow(new Exception('Cancel failed'));

        $service = new SubscriptionService();

        // when
        $result = $service->cancel($user);

        // then
        $this->assertFalse($result);
    }
}