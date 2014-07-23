<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContactBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\Exclude;

/**
 * Url
 */
class Url
{
    /**
     * @var string
     */
    private $url;

    /**
     * @var integer
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
     * Constructor
     */
    public function __construct()
    {
        $this->accounts = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Set url
     *
     * @param string $url
     * @return Url
     */
    public function setUrl($url)
    {
        $this->url = $url;

        return $this;
    }

    /**
     * Get url
     *
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set urlType
     *
     * @param \Sulu\Bundle\ContactBundle\Entity\UrlType $urlType
     * @return Url
     */
    public function setUrlType(\Sulu\Bundle\ContactBundle\Entity\UrlType $urlType)
    {
        $this->urlType = $urlType;

        return $this;
    }

    /**
     * Get urlType
     *
     * @return \Sulu\Bundle\ContactBundle\Entity\UrlType
     */
    public function getUrlType()
    {
        return $this->urlType;
    }

    /**
     * Add accounts
     *
     * @param \Sulu\Bundle\ContactBundle\Entity\Account $accounts
     * @return Url
     */
    public function addAccount(\Sulu\Bundle\ContactBundle\Entity\Account $accounts)
    {
        $this->accounts[] = $accounts;

        return $this;
    }

    /**
     * Remove accounts
     *
     * @param \Sulu\Bundle\ContactBundle\Entity\Account $accounts
     */
    public function removeAccount(\Sulu\Bundle\ContactBundle\Entity\Account $accounts)
    {
        $this->accounts->removeElement($accounts);
    }

    /**
     * Get accounts
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getAccounts()
    {
        return $this->accounts;
    }

    /**
     * Add contacts
     *
     * @param \Sulu\Bundle\ContactBundle\Entity\Contact $contacts
     * @return Url
     */
    public function addContact(\Sulu\Bundle\ContactBundle\Entity\Contact $contacts)
    {
        $this->contacts[] = $contacts;
    
        return $this;
    }

    /**
     * Remove contacts
     *
     * @param \Sulu\Bundle\ContactBundle\Entity\Contact $contacts
     */
    public function removeContact(\Sulu\Bundle\ContactBundle\Entity\Contact $contacts)
    {
        $this->contacts->removeElement($contacts);
    }

    /**
     * Get contacts
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getContacts()
    {
        return $this->contacts;
    }
}