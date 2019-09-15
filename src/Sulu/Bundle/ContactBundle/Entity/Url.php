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
 * @ExclusionPolicy("All")
 *
 * Url.
 */
class Url
{
    /**
     * @var string
     */
    private $url;

    /**
     * @var int
     * @Expose
     * @Groups({"fullAccount", "partialAccount", "fullContact", "partialContact"})
     */
    private $id;

    /**
     * @var \Sulu\Bundle\ContactBundle\Entity\UrlType
     */
    private $urlType;

    /**
     * @var \Doctrine\Common\Collections\Collection
     * @Exclude
     */
    private $accounts;

    /**
     * @var \Doctrine\Common\Collections\Collection
     * @Exclude
     */
    private $contacts;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->accounts = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Set url.
     *
     * @param string $url
     *
     * @return Url
     */
    public function setUrl($url)
    {
        $this->url = $url;

        return $this;
    }

    /**
     * @VirtualProperty
     * @SerializedName("website")
     * @Groups({"fullAccount", "partialAccount", "fullContact", "partialContact"})
     *
     * Get url.
     *
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
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
     * Set urlType.
     *
     * @param \Sulu\Bundle\ContactBundle\Entity\UrlType $urlType
     *
     * @return Url
     */
    public function setUrlType(\Sulu\Bundle\ContactBundle\Entity\UrlType $urlType)
    {
        $this->urlType = $urlType;

        return $this;
    }

    /**
     * Get urlType.
     *
     * @return \Sulu\Bundle\ContactBundle\Entity\UrlType
     */
    public function getUrlType()
    {
        return $this->urlType;
    }

    /**
     * @VirtualProperty
     * @SerializedName("websiteType")
     * @Groups({"fullContact", "fullAccount"})
     */
    public function getUrlTypeId(): ?int
    {
        return $this->urlType->getId();
    }

    /**
     * Add accounts.
     *
     * @param AccountInterface $account
     *
     * @return Url
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

    /**
     * Add contacts.
     *
     * @param \Sulu\Bundle\ContactBundle\Entity\ContactInterface $contacts
     *
     * @return Url
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
}
