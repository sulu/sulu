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
use JMS\Serializer\Annotation\Groups;

/**
 * Url.
 */
class Url
{
    /**
     * @var string
     * @Groups({"fullAccount", "partialAccount", "fullContact", "partialContact"})
     */
    private $url;

    /**
     * @var int
     * @Groups({"fullAccount", "partialAccount", "fullContact", "partialContact"})
     */
    private $id;

    /**
     * @var \Sulu\Bundle\ContactBundle\Entity\UrlType
     * @Groups({"fullAccount", "fullContact"})
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
     * @return Url
     */
    public function setUrlType(UrlType $urlType)
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
     * Add accounts.
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
     * @return Url
     */
    public function addContact(ContactInterface $contacts)
    {
        $this->contacts[] = $contacts;

        return $this;
    }

    /**
     * Remove contacts.
     */
    public function removeContact(ContactInterface $contacts)
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
