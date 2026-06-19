<?php

declare(strict_types=1);

namespace Mavroforakis\Gisis\Tests\Laravel;

use Illuminate\Config\Repository;
use Illuminate\Container\Container;
use Mavroforakis\Gisis\GisisShips;
use Mavroforakis\Gisis\Laravel\Gisis;
use Mavroforakis\Gisis\Laravel\GisisServiceProvider;
use PHPUnit\Framework\TestCase;

final class GisisServiceProviderTest extends TestCase
{
    public function testResolvesGisisShipsFromConfig(): void
    {
        $app = new Container();
        $app->instance('config', new Repository([
            'gisis' => ['imowebacc' => 'FAKE_COOKIE_VALUE'],
        ]));

        (new GisisServiceProvider($app))->register();

        $this->assertInstanceOf(GisisShips::class, $app->make(GisisShips::class));
        $this->assertSame($app->make(GisisShips::class), $app->make('gisis'), 'should be a shared singleton');
    }

    public function testFacadeAccessorPointsAtTheAlias(): void
    {
        $ref = new \ReflectionMethod(Gisis::class, 'getFacadeAccessor');
        $this->assertSame('gisis', $ref->invoke(null));
    }
}
