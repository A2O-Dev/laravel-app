<?php

namespace App\Services;

use App\Models\User;
use Exception;
use Laravel\Cashier\Exceptions\IncompletePayment;
use Laravel\Cashier\Subscription;

class SubscriptionService extends BaseService {

    public function subscribe(User $user): ?string {
        if ($user->subscribed('default')) {
            $this->errors->add('already-subscribed', 'User already has an active subscription');
            return null;
        }

        try {
            $user->newSubscription('default', config('cashier.price_id'))
                ->create(null, [], ['payment_behavior' => 'default_incomplete']);
        } catch (IncompletePayment $e) {
            return $e->payment->clientSecret();
        } catch (Exception $e) {
            $this->errors->add('subscribe', $e->getMessage());
            return null;
        }

        return null;
    }

    public function confirm(User $user): ?Subscription {
        $subscription = $user->subscription('default');

        if (is_null($subscription)) {
            $this->errors->add('not-found', 'No subscription found');
            return null;
        }

        try {
            $subscription->syncStripeStatus();
        } catch (Exception $e) {
            $this->errors->add('confirm', $e->getMessage());
            return null;
        }

        return $subscription->refresh();
    }

    public function cancel(User $user): bool {
        if (!$user->subscribed('default')) {
            $this->errors->add('not-subscribed', 'User does not have an active subscription');
            return false;
        }

        try {
            $user->subscription('default')->cancel();
        } catch (Exception $e) {
            $this->errors->add('cancel', $e->getMessage());
            return false;
        }

        return true;
    }
}