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
 * Email.
 */
class Email
{
    /**
     * @var string
     * @Groups({"fullAccount", "partialAccount", "fullContact", "partialContact"})
     */
    private $email;

    /**
     * @var int
     * @Groups({"fullAccount", "partialAccount", "fullContact", "partialContact"})
     */
    private $id;

    /**
     * @var \Sulu\Bundle\ContactBundle\Entity\EmailType
     * @Groups({"fullAccount", "fullContact"})
     */
    private $emailType;

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
     * Set email.
     *
     * @param string $email
     *
     * @return Email
     */
    public function setEmail($email)
    {
        $this->email = $email;

        return $this;
    }

    /**
     * Get email.
     *
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
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
     * Set emailType.
     *
     * @param \Sulu\Bundle\ContactBundle\Entity\EmailType $emailType
     *
     * @return Email
     */
    public function setEmailType(\Sulu\Bundle\ContactBundle\Entity\EmailType $emailType)
    {
        $this->emailType = $emailType;

        return $this;
    }

    /**
     * Get emailType.
     *
     * @return \Sulu\Bundle\ContactBundle\Entity\EmailType
     */
    public function getEmailType()
    {
        return $this->emailType;
    }

    /**
     * Add contacts.
     *
     * @param \Sulu\Component\Contact\Model\ContactInterface $contacts
     *
     * @return Email
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
     * @return Email
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
