<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContactBundle\Entity;

use JMS\Serializer\Annotation\Exclude;
use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\Expose;
use JMS\Serializer\Annotation\Groups;
use JMS\Serializer\Annotation\SerializedName;
use JMS\Serializer\Annotation\VirtualProperty;

/**
 * @ExclusionPolicy("all")
 *
 * Phone.
 */
class Phone
{
    /**
     * @var string
     * @Expose
     * @Groups({"fullAccount", "partialAccount", "fullContact", "partialContact"})
     */
    private $phone;

    /**
     * @var int
     * @Expose
     * @Groups({"fullAccount", "partialAccount", "fullContact", "partialContact"})
     */
    private $id;

    /**
     * @var \Sulu\Bundle\ContactBundle\Entity\PhoneType
     */
    private $phoneType;

    /**
     * @var \Doctrine\Common\Collections\Collection
     * @Exclude
     */
    private $contacts;

    /**
     * @var \Doctrine\Common\Collections\Collection
     * @Exclude
     */
    private $accounts;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->contacts = new \Doctrine\Common\Collections\ArrayCollection();
        $this->accounts = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Set phone.
     *
     * @param string $phone
     *
     * @return Phone
     */
    public function setPhone($phone)
    {
        $this->phone = $phone;

        return $this;
    }

    /**
     * Get phone.
     *
     * @return string
     */
    public function getPhone()
    {
        return $this->phone;
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
     * Set phoneType.
     *
     * @param \Sulu\Bundle\ContactBundle\Entity\PhoneType $phoneType
     *
     * @return Phone
     */
    public function setPhoneType(\Sulu\Bundle\ContactBundle\Entity\PhoneType $phoneType)
    {
        $this->phoneType = $phoneType;

        return $this;
    }

    /**
     * Get phoneType.
     *
     * @return \Sulu\Bundle\ContactBundle\Entity\PhoneType
     */
    public function getPhoneType()
    {
        return $this->phoneType;
    }

    /**
     * @VirtualProperty
     * @SerializedName("phoneType")
     * @Groups({"fullContact", "fullAccount"})
     */
    public function getPhoneTypeId(): int
    {
        return $this->phoneType->getId();
    }

    /**
     * Add contacts.
     *
     * @param \Sulu\Bundle\ContactBundle\Entity\ContactInterface $contacts
     *
     * @return Phone
     */
    public function addContact(\Sulu\Bundle\ContactBundle\Entity\ContactInterface $contacts)
    {
        $this->contacts[] = $contacts;

        return $this;
    }

    /**
     * Remove contacts.
     *
     * @param \Sulu\Bundle\ContactBundle\Entity\ContactInterface $contacts
     */
    public function removeContact(\Sulu\Bundle\ContactBundle\Entity\ContactInterface $contacts)
    {
        $this->contacts->removeElement($contacts);
    }

    /**
     * Get contacts.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getContacts()
    {
        return $this->contacts;
    }

    /**
     * Add accounts.
     *
     * @param AccountInterface $account
     *
     * @return Phone
     */
    public function addAccount(AccountInterface $account)
    {
        $this->accounts[] = $account;

        return $this;
    }

    /**
     * Remove accounts.
     *
     * @param AccountInterface $account
     */
    public function removeAccount(AccountInterface $account)
    {
        $this->accounts->removeElement($account);
    }

    /**
     * Get accounts.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getAccounts()
    {
        return $this->accounts;
    }
}
