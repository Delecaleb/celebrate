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
use Illuminate\Support\Facades\URL;
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

        // Behind a load balancer the app often sees plain HTTP, which would put
        // http:// canonicals and og:url tags on an https site — search engines
        // treat those as a different, competing page.
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        /*
         * File size errors in megabytes.
         *
         * Laravel's default reads "must not be greater than 10240 kilobytes",
         * which nobody sizes a photo by. A custom replacer takes over from the
         * built-in one entirely, so it still has to fill :max for strings,
         * numbers and arrays itself.
         */
        \Illuminate\Support\Facades\Validator::replacer('max', function ($message, $attribute, $rule, $parameters) {
            $max = $parameters[0] ?? '';

            if (str_contains($message, ':max kilobytes') && is_numeric($max)) {
                return str_replace(':max kilobytes', \App\Support\UploadLimits::label((int) $max), $message);
            }

            return str_replace(':max', (string) $max, $message);
        });
    }
}
