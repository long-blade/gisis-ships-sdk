<?php

declare(strict_types=1);

namespace Mavroforakis\Gisis\Search;

use Mavroforakis\Gisis\Exception\GisisException;

/**
 * A single free-text search condition (field + operator + value).
 *
 * Mirrors one `<condition>` element of the GISIS where-builder conditionsXml.
 */
final class Condition
{
    public function __construct(
        public readonly ShipField $field,
        public readonly string $operator,
        public readonly string $value,
    ) {
        if (!$field->isFreeText()) {
            throw new GisisException(sprintf(
                'Field %s is drop-down backed and not supported by free-text conditions.',
                $field->name
            ));
        }

        $expectedPrefix = $field->operatorPrefix() . '_';
        if (!str_starts_with($operator, $expectedPrefix)) {
            throw new GisisException(sprintf(
                'Operator "%s" is not valid for field %s (expected prefix "%s").',
                $operator,
                $field->name,
                $expectedPrefix
            ));
        }
    }

    public static function imoIs(string $imo): self
    {
        return new self(ShipField::ImoNumber, 'imoNumber_is', $imo);
    }

    public static function nameContains(string $name): self
    {
        return new self(ShipField::ShipName, 'name_contains', $name);
    }

    public static function callSignIs(string $callSign): self
    {
        return new self(ShipField::CallSign, 'name_is', $callSign);
    }

    public static function mmsiIs(string $mmsi): self
    {
        return new self(ShipField::Mmsi, 'name_is', $mmsi);
    }

    /** Render this condition as a conditionsXml `<condition>` fragment. */
    public function toXml(int $valueIndex = 1): string
    {
        $value = htmlspecialchars($this->value, ENT_QUOTES | ENT_XML1, 'UTF-8');

        return sprintf(
            '<condition field="%d" operator="%s" andOr="" openBracket="" closeBracket="">'
            . '<values><value name="onetext_%d" value="%s" friendlyValue="%s" /></values>'
            . '</condition>',
            $this->field->value,
            $this->operator,
            $valueIndex,
            $value,
            $value
        );
    }
}
