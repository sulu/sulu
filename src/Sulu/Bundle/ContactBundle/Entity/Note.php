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

use JMS\Serializer\Annotation\Exclude;
use JMS\Serializer\Annotation\Groups;

/**
 * Note.
 */
class Note
{
    /**
     * @var string
     * @Groups({"fullAccount", "fullContact"})
     */
    private $value;

    /**
     * @var int
     * @Groups({"fullAccount", "fullContact"})
     */
    private $id;

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
     * Set value.
     *
     * @param string $value
     *
     * @return Note
     */
    public function setValue($value)
    {
        $this->value = $value;

        return $this;
    }

    /**
     * Get value.
     *
     * @return string
     */
    public function getValue()
    {
        return $this->value;
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
     * Add contacts.
     *
     * @param \Sulu\Component\Contact\Model\ContactInterface $contacts
     *
     * @return Note
     */
    public function addContact(\Sulu\Component\Contact\Model\ContactInterface $contacts)
    {
        $this->contacts[] = $contacts;

        return $this;
    }

    /**
     * Remove contacts.
     *
     * @param \Sulu\Component\Contact\Model\ContactInterface $contacts
     */
    public function removeContact(\Sulu\Component\Contact\Model\ContactInterface $contacts)
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
     * @return Note
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
