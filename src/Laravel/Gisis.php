<?php

declare(strict_types=1);

namespace Mavroforakis\Gisis\Laravel;

use Illuminate\Support\Facades\Facade;

/**
 * Facade for the GISIS ship search client.
 *
 * @method static \Mavroforakis\Gisis\Model\Ship|null findByImo(string $imo)
 * @method static array<int,\Mavroforakis\Gisis\Model\Ship> findByName(string $name)
 * @method static array<int,\Mavroforakis\Gisis\Model\Ship> search(\Mavroforakis\Gisis\Search\Condition ...$conditions)
 *
 * @see \Mavroforakis\Gisis\GisisShips
 */
final class Gisis extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'gisis';
    }
}
