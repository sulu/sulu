<?php

namespace Sulu\Bundle\LocationBundle\Geolocator;

class GeolocatorLocation
{
    protected $id;
    protected $displayTitle;
    protected $street;
    protected $number;
    protected $code;
    protected $town;
    protected $country;
    protected $longitude;
    protected $latitude;

    public function setId($id)
    {
        $this->id = $id;
    }

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
     * hard coded to use the "name" property
     */
    public function setName($name)
    {
        $this->setDisplayName($name);
    }

    public function setDisplayName($displayName)
    {
        $this->displayName = $displayName;
    }
    

    public function toArray()
    {
        $res = array();
        foreach (array(
            'id',
            'displayTitle',
            'street',
            'number',
            'code',
            'town',
            'country',
            'longitude',
            'latitude'
        ) as $propertyName)
        {
            $res[$propertyName] = $this->{'get' . ucfirst($propertyName)}();
        }

        $res['name'] = mb_strlen($this->getDisplayTitle(), 'UTF-8') > 100 ?
            mb_substr($this->getDisplayTitle(), 0, 47, 'UTF-8') . '...' :
            $this->getDisplayTitle();

        return $res;
    }
}
