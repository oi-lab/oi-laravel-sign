<?php

namespace OiLab\OiLaravelSign\Tests;

use Illuminate\Foundation\Application;
use OiLab\OiLaravelSign\OiLaravelSignServiceProvider;
use OiLab\OiLaravelSign\Pdf\SealCertificateGenerator;
use OiLab\OiLaravelSign\Tests\Fixtures\User;
use Orchestra\Testbench\TestCase as Orchestra;
use Spatie\LaravelData\LaravelDataServiceProvider;

abstract class TestCase extends Orchestra
{
    /**
     * @var array{certificate: string, private_key: string}|null
     */
    protected static ?array $sealCertificate = null;

    /**
     * @param  Application  $app
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [
            LaravelDataServiceProvider::class,
            OiLaravelSignServiceProvider::class,
        ];
    }

    /**
     * @param  Application  $app
     */
    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);

        $app['config']->set('app.key', 'base64:'.base64_encode(str_repeat('k', 32)));
        $app['config']->set('auth.providers.users.model', User::class);
        $app['config']->set('oi-laravel-sign.user_model', User::class);
        $app['config']->set('oi-laravel-sign.disk', 'sign');
        $app['config']->set('filesystems.disks.sign', ['driver' => 'local', 'root' => storage_path('framework/testing/disks/sign')]);

        static::$sealCertificate ??= (new SealCertificateGenerator)->write(
            sys_get_temp_dir().'/oi-laravel-sign-tests',
            'OI Sign test seal',
            'OI Lab',
        );

        $app['config']->set('oi-laravel-sign.sealer.certificate', static::$sealCertificate['certificate']);
        $app['config']->set('oi-laravel-sign.sealer.private_key', static::$sealCertificate['private_key']);
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/Fixtures/database/migrations');
    }
}
