<?php

declare(strict_types=1);

namespace Mavroforakis\Gisis\Search;

/**
 * Searchable fields of the GISIS ship where-builder.
 *
 * The integer value is the field index used in the conditionsXml `field="N"`
 * attribute; the operator prefix is the field "type" GISIS uses to validate the
 * operator (e.g. field 0 accepts `imoNumber_is` / `imoNumber_contains`).
 */
enum ShipField: int
{
    case ImoNumber                  = 0;
    case ShipName                   = 1;
    case FormerShipNames            = 2;
    case Flag                       = 3;
    case CallSign                   = 4;
    case Mmsi                       = 5;
    case ShipTypeGeneral            = 6;
    case ShipTypeDetailed           = 7;
    case GrossTonnage               = 8;
    case YearOfBuild                = 9;
    case RegisteredOwnerImoCompany  = 10;
    case Status                     = 11;

    /** The operator-list key GISIS associates with this field. */
    public function operatorPrefix(): string
    {
        return match ($this) {
            self::ImoNumber, self::RegisteredOwnerImoCompany => 'imoNumber',
            self::ShipName, self::FormerShipNames,
            self::CallSign, self::Mmsi                       => 'name',
            self::Flag                                       => 'flag',
            self::ShipTypeGeneral                            => 'shipType2',
            self::ShipTypeDetailed                           => 'shipType5',
            self::GrossTonnage                               => 'metric',
            self::YearOfBuild                                => 'yearBuild',
            self::Status                                     => 'status',
        };
    }

    /**
     * Whether this field is queried with a simple free-text value
     * (value control "onetext"). Fields backed by drop-downs (flag, ship type,
     * status) are not supported by the free-text condition builder.
     */
    public function isFreeText(): bool
    {
        return in_array($this->operatorPrefix(), ['imoNumber', 'name', 'yearBuild'], true);
    }
}
