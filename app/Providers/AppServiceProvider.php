<?php

namespace App\Providers;

use App\Events\ServiceRequestStatusChanged;
use App\Listeners\SendWorkflowNotifications;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(
            \Filament\Http\Responses\Auth\Contracts\LogoutResponse::class,
            \App\Http\Responses\LogoutResponse::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Wire workflow event → notification listener
        Event::listen(
            ServiceRequestStatusChanged::class,
            SendWorkflowNotifications::class,
        );

        $locale = session('app_locale', config('app.locale', 'id'));
        app()->setLocale(in_array($locale, ['id', 'en'], true) ? $locale : 'id');

        // 🔴 Register WaktuHelper sebagai view composer global
        // Agar bisa dipanggil dengan App\Helpers\WaktuHelper::formatLengkap() di Blade
        \Illuminate\Support\Facades\Blade::if('waktu', function ($date, $format = 'lengkap') {
            return $date !== null;
        });
    }
}
