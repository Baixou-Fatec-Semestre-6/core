<?php

namespace App\Providers;

use App\Models\User;
use App\Services\RabbitMqPublisher;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // singleton so one connection is reused for all messages in a request or run
        $this->app->singleton(RabbitMqPublisher::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::define('access-admin', fn (?User $user): bool => $user?->isAdmin() ?? false);

        ResetPassword::createUrlUsing(function (User $user, string $token) {
            return route('admin.password.reset', [
                'token' => $token,
                'email' => $user->getEmailForPasswordReset(),
            ]);
        });
    }
}
