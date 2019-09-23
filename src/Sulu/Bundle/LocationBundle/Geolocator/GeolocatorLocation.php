<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\LocationBundle\Geolocator;

use Sulu\Component\Util\TextUtils;

/**
 * Data object representing a location returned by a geolocator.
 */
class GeolocatorLocation
{
    /**
     * ID of this location (according to the geolocation vendor).
     *
     * @var string
     */
    protected $id;

    /**
     * @var string|null
     */
    protected $displayTitle;

    /**
     * @var string|null
     */
    protected $street;

    /**
     * @var string|null
     */
    protected $number;

    /**
     * @var string|null
     */
    protected $code;

    /**
     * @var string|null
     */
    protected $town;

    /**
     * @var string|null
     */
    protected $country;

    /**
     * @var float|null
     */
    protected $longitude;

    /**
     * @var float|null
     */
    protected $latitude;

    /**
     * @var string|null
     */
    private $displayName;

    public function getDisplayTitle(): ?string
    {
        return $this->displayTitle;
    }

    public function setDisplayTitle(?string $displayTitle): self
    {
        $this->displayTitle = $displayTitle;

        return $this;
    }

    public function getStreet(): ?string
    {
        return $this->street;
    }

    public function setStreet(?string $street): self
    {
        $this->street = $street;

        return $this;
    }

    public function getNumber(): ?string
    {
        return $this->number;
    }

    public function setNumber(?string $number): self
    {
        $this->number = $number;

        return $this;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(?string $code): self
    {
        $this->code = $code;

        return $this;
    }

    public function getTown(): ?string
    {
        return $this->town;
    }

    public function setTown(?string $town): self
    {
        $this->town = $town;

        return $this;
    }

    public function getCountry(): ?string
    {
        return $this->country;
    }

    public function setCountry(?string $country): self
    {
        $this->country = mb_strtoupper($country);

        return $this;
    }

    public function getLongitude(): ?float
    {
        return $this->longitude;
    }

    public function setLongitude(?float $longitude): self
    {
        $this->longitude = $longitude;

        return $this;
    }

    public function getLatitude(): ?float
    {
        return $this->latitude;
    }

    public function setLatitude(?float $latitude): self
    {
        $this->latitude = $latitude;

        return $this;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function setDisplayName(?string $displayName): self
    {
        $this->displayName = $displayName;

        return $this;
    }

    public function setId(string $id): self
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Serialize the location to an array.
     *
     * @return array
     */
    public function toArray()
    {
        $res = [];
        foreach ([
            'id',
            'displayTitle',
            'street',
            'number',
            'code',
            'town',
            'country',
            'longitude',
            'latitude',
        ] as $propertyName) {
            $res[$propertyName] = $this->{'get' . ucfirst($propertyName)}();
        }

        $res['name'] = TextUtils::truncate($this->getDisplayTitle(), 75);

        return $res;
    }
}
