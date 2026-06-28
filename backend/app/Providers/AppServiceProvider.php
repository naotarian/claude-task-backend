<?php

namespace App\Providers;

use App\Support\CurrentOrganization;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Request-scoped tenant context.
        $this->app->scoped(CurrentOrganization::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Point the email verification link at the SPA, carrying the signed
        // backend URL. The /verify-email page calls the API (authenticated)
        // to complete verification.
        VerifyEmail::createUrlUsing(function (MustVerifyEmail $notifiable): string {
            $signedUrl = URL::temporarySignedRoute(
                'verification.verify',
                now()->addMinutes((int) config('auth.verification.expire', 60)),
                [
                    'id' => $notifiable->getKey(),
                    'hash' => sha1($notifiable->getEmailForVerification()),
                ],
            );

            $frontend = rtrim((string) config('app.frontend_url'), '/');

            return "{$frontend}/verify-email?target=".urlencode($signedUrl);
        });
    }
}
