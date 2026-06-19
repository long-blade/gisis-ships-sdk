<?php

declare(strict_types=1);

namespace Mavroforakis\Gisis\Model;

/**
 * A vessel record returned by GISIS.
 *
 * Fields are nullable because GISIS does not always populate every column for
 * every ship. The exact set of available fields will be finalised once the
 * authenticated search/detail response shape is captured.
 */
final class Ship
{
    public function __construct(
        public readonly ?string $imoNumber = null,
        public readonly ?string $name = null,
        public readonly ?string $flag = null,
        public readonly ?string $callSign = null,
        public readonly ?string $mmsi = null,
        public readonly ?string $grossTonnage = null,
        public readonly ?string $deadweight = null,
        public readonly ?string $shipType = null,
        public readonly ?string $yearOfBuild = null,
        public readonly ?string $status = null,
        public readonly ?string $registeredOwner = null,
        /** @var array<string,string> any extra raw key/value pairs scraped from the record */
        public readonly array $extra = [],
    ) {
    }

    /** @return array<string,mixed> */
    public function toArray(): array
    {
        return [
            'imoNumber'       => $this->imoNumber,
            'name'            => $this->name,
            'flag'            => $this->flag,
            'callSign'        => $this->callSign,
            'mmsi'            => $this->mmsi,
            'grossTonnage'    => $this->grossTonnage,
            'deadweight'      => $this->deadweight,
            'shipType'        => $this->shipType,
            'yearOfBuild'     => $this->yearOfBuild,
            'status'          => $this->status,
            'registeredOwner' => $this->registeredOwner,
            'extra'           => $this->extra,
        ];
    }
}
