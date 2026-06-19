<?php

declare(strict_types=1);

namespace Mavroforakis\Gisis\Search;

use Mavroforakis\Gisis\Model\Ship;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Parses the GISIS ship results grid (#..._gvShip) into {@see Ship} objects.
 *
 * Grid columns (in order): Name, Flag, Gross Tonnage, Type, Year of Build,
 * Registered Owner. Each data row also carries an onmouseup __doPostBack with a
 * row argument (e.g. "_rc0") that opens the detail view; we keep it in `extra`
 * for callers that want to drill down later.
 */
final class ResultsParser
{
    private const GRID_ID = 'ctl00_bodyPlaceHolder_ShipSelector_gvShip';

    /** @return list<Ship> */
    public static function parse(string $html): array
    {
        $crawler = new Crawler($html);
        $grid = $crawler->filter('#' . self::GRID_ID);
        if ($grid->count() === 0) {
            return [];
        }

        $ships = [];
        $grid->filter('tr.gridviewer_row')->each(function (Crawler $row) use (&$ships): void {
            $cells = $row->filter('td');
            if ($cells->count() < 6) {
                return;
            }

            $owner = self::clean($cells->eq(5)->text());
            [$ownerName, $ownerImo] = self::splitOwner($owner);

            $ships[] = new Ship(
                name:            self::clean($cells->eq(0)->text()),
                flag:            self::clean($cells->eq(1)->text()),
                grossTonnage:    self::clean($cells->eq(2)->text()),
                shipType:        self::clean($cells->eq(3)->text()),
                yearOfBuild:     self::clean($cells->eq(4)->text()),
                registeredOwner: $ownerName,
                extra:           array_filter([
                    'registeredOwnerImoCompany' => $ownerImo,
                    'detailPostbackArgument'    => self::rowArgument($row),
                ], static fn ($v) => $v !== null && $v !== ''),
            );
        });

        return $ships;
    }

    private static function clean(string $text): string
    {
        return trim(preg_replace('/\s+/', ' ', $text) ?? '');
    }

    /** "CASSINI SHIP OWNING CO (6277131)" => ["CASSINI SHIP OWNING CO", "6277131"]. */
    private static function splitOwner(string $owner): array
    {
        if (preg_match('/^(.*?)\s*\((\d+)\)\s*$/', $owner, $m) === 1) {
            return [trim($m[1]), $m[2]];
        }

        return [$owner !== '' ? $owner : null, null];
    }

    private static function rowArgument(Crawler $row): ?string
    {
        $onmouseup = $row->attr('onmouseup') ?? '';
        if (preg_match("/__doPostBack\\('[^']*','([^']*)'\\)/", $onmouseup, $m) === 1) {
            return $m[1];
        }

        return null;
    }
}
