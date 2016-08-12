<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContactBundle\Entity;

use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\Expose;
use JMS\Serializer\Annotation\Groups;

/**
 * Address.
 *
 * @ExclusionPolicy("all");
 */
class Address
{
    /**
     * @var string
     * @Groups({"fullAccount", "partialAccount", "fullContact", "partialContact"})
     * @Expose
     */
    private $street;

    /**
     * @var string
     * @Groups({"fullAccount", "partialAccount", "fullContact", "partialContact"})
     * @Expose
     */
    private $number;

    /**
     * @var string
     * @Groups({"fullAccount", "partialAccount", "fullContact", "partialContact"})
     * @Expose
     */
    private $addition;

    /**
     * @var string
     * @Groups({"fullAccount", "partialAccount", "fullContact", "partialContact"})
     * @Expose
     */
    private $zip;

    /**
     * @var string
     * @Groups({"fullAccount", "partialAccount", "fullContact", "partialContact"})
     * @Expose
     */
    private $city;

    /**
     * @var string
     * @Groups({"fullAccount", "partialAccount", "fullContact", "partialContact"})
     * @Expose
     */
    private $state;

    /**
     * @var int
     * @Groups({"fullAccount", "partialAccount", "fullContact", "partialContact"})
     * @Expose
     */
    private $id;

    /**
     * @var \Sulu\Bundle\ContactBundle\Entity\AddressType
     * @Groups({"fullAccount", "fullContact"})
     * @Expose
     */
    private $addressType;

    /**
     * @var \Sulu\Bundle\ContactBundle\Entity\Country
     * @Groups({"fullAccount", "partialAccount", "fullContact", "partialContact"})
     * @Expose
     */
    private $country;

    /**
     * @var bool
     * @Groups({"fullAccount", "partialAccount", "fullContact", "partialContact"})
     * @Expose
     */
    private $primaryAddress;

    /**
     * @var bool
     * @Groups({"fullAccount", "partialAccount", "fullContact", "partialContact"})
     * @Expose
     */
    private $deliveryAddress;

    /**
     * @var bool
     * @Groups({"fullAccount", "partialAccount", "fullContact", "partialContact"})
     * @Expose
     */
    private $billingAddress;

    /**
     * @var string
     * @Groups({"fullAccount", "partialAccount", "fullContact", "partialContact"})
     * @Expose
     */
    private $postboxNumber;

    /**
     * @var string
     * @Groups({"fullAccount", "partialAccount", "fullContact", "partialContact"})
     * @Expose
     */
    private $postboxPostcode;

    /**
     * @var string
     * @Groups({"fullAccount", "partialAccount", "fullContact", "partialContact"})
     * @Expose
     */
    private $postboxCity;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $contactAddresses;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $accountAddresses;

    /**
     * @var string
     * @Groups({"fullAccount", "partialAccount", "fullContact", "partialContact"})
     * @Expose
     */
    private $note;

    /**
     * @var string
     * @Groups({"fullAccount", "partialAccount", "fullContact", "partialContact"})
     * @Expose
     */
    private $title;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->accountAddresses = new \Doctrine\Common\Collections\ArrayCollection();
        $this->contactAddresses = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Set street.
     *
     * @param string $street
     *
     * @return Address
     */
    public function setStreet($street)
    {
        $this->street = $street;

        return $this;
    }

    /**
     * Get street.
     *
     * @return string
     */
    public function getStreet()
    {
        return $this->street;
    }

    /**
     * Set number.
     *
     * @param string $number
     *
     * @return Address
     */
    public function setNumber($number)
    {
        $this->number = $number;

        return $this;
    }

    /**
     * Get number.
     *
     * @return string
     */
    public function getNumber()
    {
        return $this->number;
    }

    /**
     * Set addition.
     *
     * @param string $addition
     *
     * @return Address
     */
    public function setAddition($addition)
    {
        $this->addition = $addition;

        return $this;
    }

    /**
     * Get addition.
     *
     * @return string
     */
    public function getAddition()
    {
        return $this->addition;
    }

    /**
     * Set zip.
     *
     * @param string $zip
     *
     * @return Address
     */
    public function setZip($zip)
    {
        $this->zip = $zip;

        return $this;
    }

    /**
     * Get zip.
     *
     * @return string
     */
    public function getZip()
    {
        return $this->zip;
    }

    /**
     * Set city.
     *
     * @param string $city
     *
     * @return Address
     */
    public function setCity($city)
    {
        $this->city = $city;

        return $this;
    }

    /**
     * Get city.
     *
     * @return string
     */
    public function getCity()
    {
        return $this->city;
    }

    /**
     * Set state.
     *
     * @param string $state
     *
     * @return Address
     */
    public function setState($state)
    {
        $this->state = $state;

        return $this;
    }

    /**
     * Get state.
     *
     * @return string
     */
    public function getState()
    {
        return $this->state;
    }

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set addressType.
     *
     * @param \Sulu\Bundle\ContactBundle\Entity\AddressType $addressType
     *
     * @return Address
     */
    public function setAddressType(\Sulu\Bundle\ContactBundle\Entity\AddressType $addressType)
    {
        $this->addressType = $addressType;

        return $this;
    }

    /**
     * Get addressType.
     *
     * @return \Sulu\Bundle\ContactBundle\Entity\AddressType
     */
    public function getAddressType()
    {
        return $this->addressType;
    }

    /**
     * Set country.
     *
     * @param \Sulu\Bundle\ContactBundle\Entity\Country $country
     *
     * @return Address
     */
    public function setCountry(\Sulu\Bundle\ContactBundle\Entity\Country $country = null)
    {
        $this->country = $country;

        return $this;
    }

    /**
     * Get country.
     *
     * @return \Sulu\Bundle\ContactBundle\Entity\Country
     */
    public function getCountry()
    {
        return $this->country;
    }

    /**
     * Set primaryAddress.
     *
     * @param bool $primaryAddress
     *
     * @return Address
     */
    public function setPrimaryAddress($primaryAddress)
    {
        $this->primaryAddress = $primaryAddress;

        return $this;
    }

    /**
     * Get primaryAddress.
     *
     * @return bool
     */
    public function getPrimaryAddress()
    {
        return $this->primaryAddress;
    }

    /**
     * Set deliveryAddress.
     *
     * @param bool $deliveryAddress
     *
     * @return Address
     */
    public function setDeliveryAddress($deliveryAddress)
    {
        $this->deliveryAddress = $deliveryAddress;

        return $this;
    }

    /**
     * Get deliveryAddress.
     *
     * @return bool
     */
    public function getDeliveryAddress()
    {
        return $this->deliveryAddress;
    }

    /**
     * Set billingAddress.
     *
     * @param bool $billingAddress
     *
     * @return Address
     */
    public function setBillingAddress($billingAddress)
    {
        $this->billingAddress = $billingAddress;

        return $this;
    }

    /**
     * Get billingAddress.
     *
     * @return bool
     */
    public function getBillingAddress()
    {
        return $this->billingAddress;
    }

    /**
     * Set postboxNumber.
     *
     * @param string $postboxNumber
     *
     * @return Address
     */
    public function setPostboxNumber($postboxNumber)
    {
        $this->postboxNumber = $postboxNumber;

        return $this;
    }

    /**
     * Get postboxNumber.
     *
     * @return string
     */
    public function getPostboxNumber()
    {
        return $this->postboxNumber;
    }

    /**
     * Set postboxPostcode.
     *
     * @param string $postboxPostcode
     *
     * @return Address
     */
    public function setPostboxPostcode($postboxPostcode)
    {
        $this->postboxPostcode = $postboxPostcode;

        return $this;
    }

    /**
     * Get postboxPostcode.
     *
     * @return string
     */
    public function getPostboxPostcode()
    {
        return $this->postboxPostcode;
    }

    /**
     * Set postboxCity.
     *
     * @param string $postboxCity
     *
     * @return Address
     */
    public function setPostboxCity($postboxCity)
    {
        $this->postboxCity = $postboxCity;

        return $this;
    }

    /**
     * Get postboxCity.
     *
     * @return string
     */
    public function getPostboxCity()
    {
        return $this->postboxCity;
    }

    /**
     * Add contactAddresses.
     *
     * @param \Sulu\Bundle\ContactBundle\Entity\ContactAddress $contactAddresses
     *
     * @return Address
     */
    public function addContactAddress(\Sulu\Bundle\ContactBundle\Entity\ContactAddress $contactAddresses)
    {
        $this->contactAddresses[] = $contactAddresses;

        return $this;
    }

    /**
     * Remove contactAddresses.
     *
     * @param \Sulu\Bundle\ContactBundle\Entity\ContactAddress $contactAddresses
     */
    public function removeContactAddress(\Sulu\Bundle\ContactBundle\Entity\ContactAddress $contactAddresses)
    {
        $this->contactAddresses->removeElement($contactAddresses);
    }

    /**
     * Get contactAddresses.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getContactAddresses()
    {
        return $this->contactAddresses;
    }

    /**
     * Add accountAddresses.
     *
     * @param \Sulu\Bundle\ContactBundle\Entity\AccountAddress $accountAddresses
     *
     * @return Address
     */
    public function addAccountAddress(\Sulu\Bundle\ContactBundle\Entity\AccountAddress $accountAddresses)
    {
        $this->accountAddresses[] = $accountAddresses;

        return $this;
    }

    /**
     * Remove accountAddresses.
     *
     * @param \Sulu\Bundle\ContactBundle\Entity\AccountAddress $accountAddresses
     */
    public function removeAccountAddress(\Sulu\Bundle\ContactBundle\Entity\AccountAddress $accountAddresses)
    {
        $this->accountAddresses->removeElement($accountAddresses);
    }

    /**
     * Get accountAddresses.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getAccountAddresses()
    {
        return $this->accountAddresses;
    }

    /**
     * returns if address has at least one relation to another entity.
     *
     * @return bool
     */
    public function hasRelations()
    {
        if (!$this->getContactAddresses()->isEmpty() ||
            !$this->getAccountAddresses()->isEmpty()
        ) {
            return true;
        }

        return false;
    }

    /**
     * Set note.
     *
     * @param string $note
     *
     * @return Address
     */
    public function setNote($note)
    {
        $this->note = $note;

        return $this;
    }

    /**
     * Get note.
     *
     * @return string
     */
    public function getNote()
    {
        return $this->note;
    }

    /**
     * Set title.
     *
     * @param string $title
     *
     * @return Address
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get title.
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }
}
