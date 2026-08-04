<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use App\Services\AktivitasLogService;

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
        Event::listen('eloquent.created: *', fn (string $event, mixed $payload) => self::logEloquentEvent($payload, 'create'));
        Event::listen('eloquent.updated: *', fn (string $event, mixed $payload) => self::logEloquentEvent($payload, 'update'));
        Event::listen('eloquent.deleted: *', fn (string $event, mixed $payload) => self::logEloquentEvent($payload, 'delete'));

        RateLimiter::for('auth', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip());
        });

        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(100)->by($request->user()?->id ?: $request->ip());
        });
    }

    private static function logEloquentEvent(mixed $payload, string $action): void
    {
        $model = is_array($payload) ? ($payload[0] ?? null) : $payload;

        if ($model instanceof Model) {
            AktivitasLogService::log($model, $action);
        }
    }
}
