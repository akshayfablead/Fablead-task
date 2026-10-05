<?php

namespace App\Providers;

use App\Models\Record;
use App\Models\User;
use App\Policies\RecordPolicy;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Gate::policy(Record::class, RecordPolicy::class);

        Gate::define(
            'manage-system',
            fn (User $user) => $user->isAdmin()
        );

        RateLimiter::for('login', function (Request $request) {
            return [
                Limit::perMinute(20)->by(
                    'ip:'.$request->ip()
                ),

                Limit::perMinute(5)->by(
                    'login:'
                    .Str::lower((string) $request->input('email'))
                    .'|'
                    .$request->ip()
                ),
            ];
        });
    }
}