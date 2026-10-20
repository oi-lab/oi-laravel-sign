<?php

namespace OiLab\OiLaravelSign;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use InvalidArgumentException;
use OiLab\OiLaravelSign\Console\Commands\ExpireSignaturesCommand;
use OiLab\OiLaravelSign\Console\Commands\ExportEvidenceCommand;
use OiLab\OiLaravelSign\Console\Commands\InstallAiSkillCommand;
use OiLab\OiLaravelSign\Console\Commands\MakeCertificateCommand;
use OiLab\OiLaravelSign\Console\Commands\VerifySignatureCommand;
use OiLab\OiLaravelSign\Contracts\PdfSealer;
use OiLab\OiLaravelSign\Contracts\SignatureDriver;
use OiLab\OiLaravelSign\Drivers\NativeSignatureDriver;
use OiLab\OiLaravelSign\Pdf\Sealers\PadesSealer;
use OiLab\OiLaravelSign\Pdf\Sealers\UnsealedSealer;

class OiLaravelSignServiceProvider extends ServiceProvider
{
    /**
     * @var array<string, class-string<PdfSealer>>
     */
    protected const SEALERS = [
        'pades' => PadesSealer::class,
        'none' => UnsealedSealer::class,
    ];

    /**
     * @var array<string, class-string<SignatureDriver>>
     */
    protected const DRIVERS = [
        'native' => NativeSignatureDriver::class,
    ];

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/oi-laravel-sign.php', 'oi-laravel-sign');

        $this->app->bind(PdfSealer::class, fn ($app) => $app->make(
            $this->resolveClass(config('oi-laravel-sign.sealer.driver', 'pades'), self::SEALERS, PdfSealer::class)
        ));

        $this->app->bind(SignatureDriver::class, fn ($app) => $app->make(
            $this->resolveClass(config('oi-laravel-sign.driver', 'native'), self::DRIVERS, SignatureDriver::class)
        ));
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'oi-laravel-sign');
        $this->loadTranslationsFrom(__DIR__.'/../lang', 'oi-laravel-sign');

        $this->registerRoutes();

        $this->callAfterResolving(Schedule::class, function (Schedule $schedule): void {
            if (config('oi-laravel-sign.expiration.enabled', true)) {
                $schedule->command('sign:expire')->hourly()->withoutOverlapping();
            }
        });

        if ($this->app->runningInConsole()) {
            $this->commands([
                InstallAiSkillCommand::class,
                ExpireSignaturesCommand::class,
                ExportEvidenceCommand::class,
                VerifySignatureCommand::class,
                MakeCertificateCommand::class,
            ]);

            $this->publishes([
                __DIR__.'/../config/oi-laravel-sign.php' => config_path('oi-laravel-sign.php'),
            ], 'oi-laravel-sign-config');

            $this->publishes([
                __DIR__.'/../database/migrations' => database_path('migrations'),
            ], 'oi-laravel-sign-migrations');

            $this->publishes([
                __DIR__.'/../resources/views' => resource_path('views/vendor/oi-laravel-sign'),
                __DIR__.'/../lang' => $this->app->langPath('vendor/oi-laravel-sign'),
            ], 'oi-laravel-sign-stubs');

            $this->publishes([
                __DIR__.'/../resources/stubs/ai-skill.md' => base_path('.claude/skills/oilab-laravel-sign/SKILL.md'),
            ], 'oi-laravel-sign-skill');
        }
    }

    protected function registerRoutes(): void
    {
        if (! RateLimiter::limiter('oi-sign')) {
            RateLimiter::for('oi-sign', fn (Request $request) => Limit::perMinute((int) config('oi-laravel-sign.route.rate_limit', 30))
                ->by($request->ip()));
        }

        if (! config('oi-laravel-sign.route.enabled', true) || $this->app->routesAreCached()) {
            return;
        }

        $middleware = config('oi-laravel-sign.route.middleware', ['web', 'throttle:oi-sign']);

        if ($gate = config('oi-laravel-sign.route.gate')) {
            $middleware[] = 'can:'.$gate;
        }

        Route::prefix(config('oi-laravel-sign.route.prefix', 'sign'))
            ->name(config('oi-laravel-sign.route.name', 'sign.'))
            ->middleware($middleware)
            ->group(__DIR__.'/../routes/web.php');
    }

    /**
     * @template T
     *
     * @param  array<string, class-string<T>>  $aliases
     * @param  class-string<T>  $contract
     * @return class-string<T>
     */
    protected function resolveClass(?string $name, array $aliases, string $contract): string
    {
        $class = $aliases[$name] ?? $name;

        if (! is_string($class) || ! is_a($class, $contract, true)) {
            throw new InvalidArgumentException("[{$name}] is not a {$contract} implementation.");
        }

        return $class;
    }
}
