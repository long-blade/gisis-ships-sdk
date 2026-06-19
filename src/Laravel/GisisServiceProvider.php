<?php

declare(strict_types=1);

namespace Mavroforakis\Gisis\Laravel;

use Illuminate\Contracts\Container\Container;
use Illuminate\Support\ServiceProvider;
use Mavroforakis\Gisis\Auth\CookieSessionProvider;
use Mavroforakis\Gisis\GisisShips;

/**
 * Auto-discovered Laravel integration.
 *
 * Registers {@see GisisShips} as a lazy singleton built from config/gisis.php
 * (which reads the GISIS_* env vars), and exposes it under the "gisis" alias
 * used by the {@see Gisis} facade.
 */
final class GisisServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../../config/gisis.php', 'gisis');

        $this->app->singleton(GisisShips::class, static function (Container $app): GisisShips {
            /** @var array{imowebacc?:?string,arraffinity?:?string,session_id?:?string} $config */
            $config = $app['config']->get('gisis', []);

            return new GisisShips(new CookieSessionProvider(array_filter([
                'IMOWEBACC'         => $config['imowebacc'] ?? '',
                'ARRAffinity'       => $config['arraffinity'] ?? '',
                'ASP.NET_SessionId' => $config['session_id'] ?? '',
            ])));
        });

        $this->app->alias(GisisShips::class, 'gisis');
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../../config/gisis.php' => $this->app->configPath('gisis.php'),
            ], 'gisis-config');
        }
    }

    /** @return list<string> */
    public function provides(): array
    {
        return [GisisShips::class, 'gisis'];
    }
}
