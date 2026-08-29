<?php

namespace App\Providers;

use App\Contracts\AssessmentAnalyzer;
use App\Services\NullAssessmentAnalyzer;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(AssessmentAnalyzer::class, NullAssessmentAnalyzer::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Model::shouldBeStrict(! $this->app->isProduction());

        DB::prohibitDestructiveCommands($this->app->isProduction());

        RateLimiter::for('web', function (Request $request): Limit {
            $identifier = $request->user()?->getAuthIdentifier() ?? $request->ip();

            return Limit::perMinute(config()->integer('security.rate_limits.web'))
                ->by('web:'.(string) $identifier);
        });

        RateLimiter::for('api', function (Request $request): Limit {
            $identifier = $request->user()?->getAuthIdentifier() ?? $request->ip();

            return Limit::perMinute(config()->integer('security.rate_limits.api'))
                ->by('api:'.(string) $identifier);
        });

        RateLimiter::for('auth', fn (Request $request): Limit => Limit::perMinute(
            config()->integer('security.rate_limits.auth')
        )->by('auth:'.$request->ip()));
    }
}
