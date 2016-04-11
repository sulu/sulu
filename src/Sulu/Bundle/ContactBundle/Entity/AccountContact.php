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

use Sulu\Component\Contact\Model\ContactInterface;

/**
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
     * @var string
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
     * Get main.
     *
     * @return bool
     */
    public function getMain()
    {
        return $this->main;
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
     * Set position.
     *
     * @param string $position
     *
     * @return AccountContact
     */
    public function setPosition($position)
    {
        $this->position = $position;

        return $this;
    }

    /**
     * Get position.
     *
     * @return string
     */
    public function getPosition()
    {
        return $this->position;
    }
}
