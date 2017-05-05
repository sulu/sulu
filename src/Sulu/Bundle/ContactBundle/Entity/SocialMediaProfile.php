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

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use JMS\Serializer\Annotation\Exclude;
use JMS\Serializer\Annotation\Groups;
use Sulu\Component\Contact\Model\ContactInterface;

/**
 * SocialMediaProfile.
 */
class SocialMediaProfile
{
    /**
     * @var int
     * @Groups({"fullAccount", "partialAccount", "fullContact", "partialContact"})
     */
    private $id;

    /**
     * @var string
     * @Groups({"fullAccount", "partialAccount", "fullContact", "partialContact"})
     */
    private $name;

    /**
     * @var SocialMediaProfileType
     * @Groups({"fullAccount", "fullContact"})
     */
    private $socialMediaProfileType;

    /**
     * @var Collection
     * @Exclude
     */
    private $contacts;

    /**
     * @var Collection
     * @Exclude
     */
    private $accounts;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->contacts = new ArrayCollection();
        $this->accounts = new ArrayCollection();
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
     * Set name.
     *
     * @param string $name
     *
     * @return SocialMediaProfile
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set social media profile type.
     *
     * @param SocialMediaProfileType $socialMediaProfileType
     *
     * @return SocialMediaProfile
     */
    public function setSocialMediaProfileType(SocialMediaProfileType $socialMediaProfileType)
    {
        $this->socialMediaProfileType = $socialMediaProfileType;

        return $this;
    }

    /**
     * Get social media profile type.
     *
     * @return SocialMediaProfileType
     */
    public function getSocialMediaProfileType()
    {
        return $this->socialMediaProfileType;
    }

    /**
     * Add contact.
     *
     * @param ContactInterface $contacts
     *
     * @return SocialMediaProfile
     */
    public function addContact(ContactInterface $contacts)
    {
        $this->contacts[] = $contacts;

        return $this;
    }

    /**
     * Remove contact.
     *
     * @param ContactInterface $contacts
     */
    public function removeContact(ContactInterface $contacts)
    {
        $this->contacts->removeElement($contacts);
    }

    /**
     * Get contacts.
     *
     * @return Collection
     */
    public function getContacts()
    {
        return $this->contacts;
    }

    /**
     * Add account.
     *
     * @param AccountInterface $account
     *
     * @return SocialMediaProfile
     */
    public function addAccount(AccountInterface $account)
    {
        $this->accounts[] = $account;

        return $this;
    }

    /**
     * Remove account.
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
     * @return Collection
     */
    public function getAccounts()
    {
        return $this->accounts;
    }
}
