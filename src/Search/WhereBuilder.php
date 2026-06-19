<?php

declare(strict_types=1);

namespace Mavroforakis\Gisis\Search;

/**
 * Assembles the GISIS `conditionsXml` value from one or more {@see Condition}s.
 */
final class WhereBuilder
{
    /** Build the plain (un-encoded) conditionsXml string. */
    public static function toXml(Condition ...$conditions): string
    {
        $fragments = '';
        foreach (array_values($conditions) as $i => $condition) {
            $fragments .= $condition->toXml($i + 1);
        }

        return '<conditions>' . $fragments . '</conditions>';
    }
}
