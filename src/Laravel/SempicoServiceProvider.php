<?php

declare(strict_types=1);

namespace Sempico\RestApi\Laravel;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Notifications\ChannelManager;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\ServiceProvider;
use Sempico\RestApi\ApiClient;
use Sempico\RestApi\Sempico;

class SempicoServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../../config/sempico.php', 'sempico');

        $this->app->singleton(ApiClient::class, static function (Application $app): ApiClient {
            $config = $app['config']['sempico'];

            return new ApiClient((string) $config['token'], [
                'base_url'        => $config['base_url'],
                'timeout'         => $config['timeout'],
                'connect_timeout' => $config['connect_timeout'],
                'verify_ssl'      => $config['verify_ssl'],
            ]);
        });

        $this->app->singleton(Sempico::class, static fn (Application $app) => new Sempico($app->make(ApiClient::class)));
        $this->app->alias(Sempico::class, 'sempico');
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../../config/sempico.php' => $this->app->configPath('sempico.php'),
            ], 'sempico-config');
        }

        Notification::resolved(static function (ChannelManager $service): void {
            $service->extend('sempico', static fn ($app) => new SempicoChannel(
                $app->make(Sempico::class),
                (string) $app['config']['sempico.sender_id']
            ));
        });
    }
}
