<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Laravel\Cashier\Cashier;
use Stripe\StripeClient;

class AppServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton(StripeClient::class, fn() => Cashier::stripe());
    }

    public function boot()
    {
        //
    }
}
