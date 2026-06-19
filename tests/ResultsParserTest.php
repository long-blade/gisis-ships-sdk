<?php

declare(strict_types=1);

namespace Mavroforakis\Gisis\Tests;

use Mavroforakis\Gisis\Search\ResultsParser;
use PHPUnit\Framework\TestCase;

final class ResultsParserTest extends TestCase
{
    public function testParsesGridFixture(): void
    {
        $html = file_get_contents(__DIR__ . '/fixtures/results_grid.html');
        $ships = ResultsParser::parse($html);

        $this->assertCount(1, $ships);

        $ship = $ships[0];
        $this->assertSame('KAVITA', $ship->name);
        $this->assertSame('Palau', $ship->flag);
        $this->assertSame('15,899', $ship->grossTonnage);
        $this->assertSame('General Cargo Ship (General Cargo)', $ship->shipType);
        $this->assertSame('1995', $ship->yearOfBuild);
        $this->assertSame('CASSINI SHIP OWNING CO', $ship->registeredOwner);
        $this->assertSame('6277131', $ship->extra['registeredOwnerImoCompany']);
        $this->assertSame('_rc0', $ship->extra['detailPostbackArgument']);
    }

    public function testReturnsEmptyArrayWhenNoGrid(): void
    {
        $this->assertSame([], ResultsParser::parse('<html><body>no results</body></html>'));
    }
}
