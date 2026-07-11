<?php

namespace App\Providers;

use App\Listeners\SendWelcomeEmail;
use App\Models\User;
use App\Observers\UserObserver;
use App\Services\LocationModule\LocationService;
use App\Services\PaymentSystem\CheckoutService;
use App\Services\PaymentSystem\CurrencyService;
use App\Services\PaymentSystem\PaystackService;
use App\Services\PaymentSystem\StripeService;
use App\Services\PaymentSystem\WalletService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(LocationService::class);
        $this->app->singleton(CurrencyService::class);
        $this->app->singleton(WalletService::class);
        $this->app->singleton(StripeService::class);
        $this->app->singleton(PaystackService::class);
        $this->app->singleton(CheckoutService::class);
    }

    public function boot(): void
    {
        Event::listen(Registered::class, SendWelcomeEmail::class);
        User::observe(UserObserver::class);
    }
}
