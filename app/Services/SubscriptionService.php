<?php

namespace App\Services;

use App\Models\User;
use Exception;
use Laravel\Cashier\Exceptions\IncompletePayment;
use Laravel\Cashier\Subscription;

class SubscriptionService extends BaseService {

    public function subscribe(User $user): ?string {
        $clientSecret = null;

        if ($user->subscribed('default')) {
            $this->errors->add('already-subscribed', 'User already has an active subscription');
        } else {
            try {
                $user->newSubscription('default', config('cashier.price_id'))
                    ->create(null, [], ['payment_behavior' => 'default_incomplete']);
            } catch (IncompletePayment $e) {
                $clientSecret = $e->payment->clientSecret();
            } catch (Exception $e) {
                $this->errors->add('subscribe', $e->getMessage());
            }
        }

        return $clientSecret;
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
        $result = true;
        if (!$user->subscribed('default')) {
            $this->errors->add('not-subscribed', 'User does not have an active subscription');
            $result = false;
        }

        try {
            $user->subscription('default')->cancel();
        } catch (Exception $e) {
            $this->errors->add('cancel', $e->getMessage());
            $result = false;
        }

        return $result;
    }
}