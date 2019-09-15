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

use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\Groups;
use JMS\Serializer\Annotation\SerializedName;
use JMS\Serializer\Annotation\VirtualProperty;

/**
 * @ExclusionPolicy("all")
 *
 * AccountContact.
 */
class AccountContact
{
    /**
     * @var bool
     */
    private $main;

    /**
     * @var int
     */
    private $id;

    /**
     * @var ContactInterface
     */
    private $contact;

    /**
     * @var AccountInterface
     */
    private $account;

    /**
     * @var Position|null
     */
    private $position;

    /**
     * Set main.
     *
     * @param bool $main
     *
     * @return AccountContact
     */
    public function setMain($main)
    {
        $this->main = $main;

        return $this;
    }

    /**
     * @VirtualProperty
     * @SerializedName("main")
     * @Groups({"fullAccount"})
     *
     * Get main.
     *
     * @return bool
     */
    public function getMain()
    {
        return $this->main;
    }

    /**
     * @VirtualProperty
     * @SerializedName("id")
     * @Groups({"fullAccount"})
     *
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set contact.
     *
     * @param ContactInterface $contact
     *
     * @return AccountContact
     */
    public function setContact(ContactInterface $contact)
    {
        $this->contact = $contact;

        return $this;
    }

    /**
     * Get contact.
     *
     * @return ContactInterface
     */
    public function getContact()
    {
        return $this->contact;
    }

    /**
     * @VirtualProperty
     * @SerializedName("contact")
     * @Groups({"fullAccount"})
     */
    public function getContactData(): array
    {
        return [
            'id' => $this->contact->getId(),
            'fullName' => $this->contact->getFullName(),
        ];
    }

    /**
     * Set account.
     *
     * @param AccountInterface $account
     *
     * @return AccountContact
     */
    public function setAccount(AccountInterface $account)
    {
        $this->account = $account;

        return $this;
    }

    /**
     * Get account.
     *
     * @return AccountInterface
     */
    public function getAccount()
    {
        return $this->account;
    }

    /**
     * @VirtualProperty
     * @SerializedName("account")
     * @Groups({"fullAccount"})
     */
    public function getAccountData(): array
    {
        return [
            'id' => $this->account->getId(),
            'name' => $this->account->getName(),
        ];
    }

    /**
     * Set position.
     *
     * @param Position|null $position
     *
     * @return AccountContact
     */
    public function setPosition(Position $position = null)
    {
        $this->position = $position;

        return $this;
    }

    /**
     * @VirtualProperty
     * @SerializedName("position")
     * @Groups({"fullAccount"})
     *
     * Get position.
     *
     * @return Position|null
     */
    public function getPosition()
    {
        return $this->position;
    }
}
