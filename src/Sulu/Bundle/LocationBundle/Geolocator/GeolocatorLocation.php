<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\LocationBundle\Geolocator;

use Sulu\Component\Util\TextUtils;

/**
 * Data object representing a location returned
 * by a geolocator.
 */
class GeolocatorLocation
{
    /**
     * ID of this location (according to the geolocation vendor).
     *
     * @var mixed
     */
    protected $id;

    /**
     * Title to display.
     *
     * @var string
     */
    protected $displayTitle;

    /**
     * @var string
     */
    protected $street;

    /**
     * @var string
     */
    protected $number;

    /**
     * @var string
     */
    protected $code;

    /**
     * @var string
     */
    protected $town;

    /**
     * @var string
     */
    protected $country;

    /**
     * @var float
     */
    protected $longitude;

    /**
     * @var float
     */
    protected $latitude;

    public function getDisplayTitle()
    {
        return $this->displayTitle;
    }

    public function setDisplayTitle($displaytitle)
    {
        $this->displayTitle = $displaytitle;
    }

    public function getStreet()
    {
        return $this->street;
    }

    public function setStreet($street)
    {
        $this->street = $street;
    }

    public function getNumber()
    {
        return $this->number;
    }

    public function setNumber($number)
    {
        $this->number = $number;
    }

    public function getCode()
    {
        return $this->code;
    }

    public function setCode($code)
    {
        $this->code = $code;
    }

    public function getTown()
    {
        return $this->town;
    }

    public function setTown($town)
    {
        $this->town = $town;
    }

    public function getCountry()
    {
        return $this->country;
    }

    public function setCountry($country)
    {
        $this->country = $country;
    }

    public function getLongitude()
    {
        return $this->longitude;
    }

    public function setLongitude($longitude)
    {
        $this->longitude = $longitude;
    }

    public function getLatitude()
    {
        return $this->latitude;
    }

    public function setLatitude($latitude)
    {
        $this->latitude = $latitude;
    }

    public function getId()
    {
        return $this->id;
    }

    /**
     * This is a hack for the husky component which is now
     * hard coded to use the "name" property.
     */
    public function setName($name)
    {
        $this->setDisplayName($name);
    }

    public function setDisplayName($displayName)
    {
        $this->displayName = $displayName;
    }

    public function setId($id)
    {
        $this->id = $id;
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
