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

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use JMS\Serializer\Annotation as Serializer;
use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\Groups;
use JMS\Serializer\Annotation\SerializedName;
use JMS\Serializer\Annotation\VirtualProperty;

/**
 * Social media profile belonging to account or contact.
 *
 * @ExclusionPolicy("All")
 */
class SocialMediaProfile
{
    /**
     * @var int
     */
    private $id;

    private ?string $username = null;

    private ?\Sulu\Bundle\ContactBundle\Entity\SocialMediaProfileType $socialMediaProfileType = null;

    /**
     * @var Collection<int, ContactInterface>
     */
    private \Doctrine\Common\Collections\ArrayCollection|array $contacts;

    /**
     * @var Collection<int, AccountInterface>
     */
    private \Doctrine\Common\Collections\ArrayCollection|array $accounts;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->contacts = new ArrayCollection();
        $this->accounts = new ArrayCollection();
    }

    /**
     * @VirtualProperty()
     * @SerializedName("id")
     * @Groups({"fullAccount", "partialAccount", "fullContact", "partialContact"})
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $username
     *
     * @return SocialMediaProfile
     */
    public function setUsername($username)
    {
        // Limit to maximal sql column length.
        $this->username = \substr($username, 0, 255);

        return $this;
    }

    /**
     * @VirtualProperty()
     * @SerializedName("username")
     * @Groups({"fullAccount", "partialAccount", "fullContact", "partialContact"})
     *
     * @return string
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * @return SocialMediaProfile
     */
    public function setSocialMediaProfileType(SocialMediaProfileType $socialMediaProfileType)
    {
        $this->socialMediaProfileType = $socialMediaProfileType;

        return $this;
    }

    /**
     * @Serializer\VirtualProperty()
     * @Serializer\SerializedName("socialMediaProfileType")
     * @Groups({"fullAccount", "fullContact"})
     *
     * @return SocialMediaProfileType
     */
    public function getSocialMediaProfileType()
    {
        return $this->socialMediaProfileType;
    }

    /**
     * @return SocialMediaProfile
     */
    public function addContact(ContactInterface $contacts)
    {
        $this->contacts[] = $contacts;

        return $this;
    }

    public function removeContact(ContactInterface $contacts)
    {
        $this->contacts->removeElement($contacts);
    }

    /**
     * @return Collection<int, ContactInterface>
     */
    public function getContacts()
    {
        return $this->contacts;
    }

    /**
     * @return SocialMediaProfile
     */
    public function addAccount(AccountInterface $account)
    {
        $this->accounts[] = $account;

        return $this;
    }

    public function removeAccount(AccountInterface $account)
    {
        $this->accounts->removeElement($account);
    }

    /**
     * @return Collection<int, AccountInterface>
     */
    public function getAccounts()
    {
        return $this->accounts;
    }
}
