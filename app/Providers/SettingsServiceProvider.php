<?php

namespace App\Providers;

use App\Support\SettingsRepository;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\ServiceProvider;

/**
 * Lays operator-managed settings over the config, once per request.
 *
 * This is what lets the panel change gateway keys, SMTP credentials and the
 * supported currencies without a deploy, while every call site carries on
 * reading plain config(). .env remains the fallback for anything the panel has
 * not set.
 *
 * It must never be the reason the app fails to boot: before the tables exist,
 * or if the query throws, the overlay is simply empty and .env wins.
 */
class SettingsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(SettingsRepository::class);
    }

    public function boot(): void
    {
        try {
            $overlay = $this->app->make(SettingsRepository::class)->overlay();
        } catch (\Throwable $e) {
            report($e);

            return;
        }

        foreach ($overlay as $key => $value) {
            Config::set($key, $value);
        }

        // The mail manager caches a mailer the first time one is resolved, so
        // changing the credentials mid-request would otherwise be ignored.
        if ($overlay !== [] && $this->app->resolved('mail.manager')) {
            $this->app->forgetInstance('mail.manager');
        }
    }
}
