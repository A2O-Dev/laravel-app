<?php

namespace App\Services;

use App\Models\User;
use Exception;

class SubscriptionService extends BaseService {

    public function subscribe(User $user, string $paymentMethodId): bool {
        if ($user->subscribed('default')) {
            $this->errors->add('already-subscribed', 'User already has an active subscription');
            return false;
        }

        try {
            $user->newSubscription('default', config('cashier.price_id'))
                ->create($paymentMethodId);
        } catch (Exception $e) {
            $this->errors->add('subscribe', $e->getMessage());
            return false;
        }

        return true;
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