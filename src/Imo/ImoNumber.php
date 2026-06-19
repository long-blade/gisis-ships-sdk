<?php

declare(strict_types=1);

namespace Mavroforakis\Gisis\Imo;

use Mavroforakis\Gisis\Exception\InvalidImoNumberException;

/**
 * A validated IMO ship identification number.
 *
 * IMO ship numbers are 7 digits where the 7th digit is a check digit:
 * multiply the first 6 digits by 7,6,5,4,3,2, sum them, and the last digit
 * of the sum must equal the 7th digit.
 *
 * Note: company / registered-owner IMO numbers use a different scheme and are
 * NOT validated by this class.
 */
final class ImoNumber
{
    private function __construct(public readonly string $value)
    {
    }

    /** @throws InvalidImoNumberException */
    public static function fromString(string $raw): self
    {
        $normalized = self::normalize($raw);

        if (!self::isValid($normalized)) {
            throw new InvalidImoNumberException(sprintf('"%s" is not a valid IMO ship number.', $raw));
        }

        return new self($normalized);
    }

    /** Strip an "IMO" prefix, whitespace and separators, returning the bare digits. */
    public static function normalize(string $raw): string
    {
        $raw = strtoupper(trim($raw));
        if (str_starts_with($raw, 'IMO')) {
            $raw = trim(substr($raw, 3));
        }

        return preg_replace('/\D+/', '', $raw) ?? '';
    }

    public static function isValid(string $raw): bool
    {
        $digits = self::normalize($raw);
        if (strlen($digits) !== 7 || !ctype_digit($digits)) {
            return false;
        }

        $sum = 0;
        foreach (str_split(substr($digits, 0, 6)) as $i => $digit) {
            $sum += (int) $digit * (7 - $i);
        }

        return $sum % 10 === (int) $digits[6];
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
