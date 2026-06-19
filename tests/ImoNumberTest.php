<?php

declare(strict_types=1);

namespace Mavroforakis\Gisis\Tests;

use Mavroforakis\Gisis\Exception\InvalidImoNumberException;
use Mavroforakis\Gisis\Imo\ImoNumber;
use PHPUnit\Framework\TestCase;

final class ImoNumberTest extends TestCase
{
    public function testValidNumbers(): void
    {
        $this->assertTrue(ImoNumber::isValid('9074729'));
        $this->assertTrue(ImoNumber::isValid('IMO 9074729'));
        $this->assertTrue(ImoNumber::isValid('imo9074729'));
        $this->assertTrue(ImoNumber::isValid('9811000')); // Ever Given
    }

    public function testInvalidNumbers(): void
    {
        $this->assertFalse(ImoNumber::isValid('9074728')); // wrong check digit
        $this->assertFalse(ImoNumber::isValid('123'));     // too short
        $this->assertFalse(ImoNumber::isValid('12345678'));// too long
        $this->assertFalse(ImoNumber::isValid('ABCDEFG')); // non-numeric
    }

    public function testNormalizationAndToString(): void
    {
        $imo = ImoNumber::fromString('IMO 9074729');
        $this->assertSame('9074729', (string) $imo);
        $this->assertSame('9074729', $imo->value);
    }

    public function testThrowsOnInvalid(): void
    {
        $this->expectException(InvalidImoNumberException::class);
        ImoNumber::fromString('9074728');
    }
}
