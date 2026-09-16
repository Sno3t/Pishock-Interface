<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Each operator link (and the owner) gets its own bucket, so one
        // person spamming commands can't affect anyone else's ability to send them.
        RateLimiter::for('commands', function (Request $request) {
            $key = $request->route('token') ?? $request->user()?->id ?? $request->ip();

            return Limit::perMinute(config('pishock.commands_per_minute'))->by('commands:' . $key);
        });
    }
}
