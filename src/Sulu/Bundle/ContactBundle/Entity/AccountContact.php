<?php

namespace Sulu\Bundle\ContactBundle\Entity;

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
     * @var \Sulu\Bundle\ContactBundle\Entity\Contact
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
     * @param \Sulu\Bundle\ContactBundle\Entity\Contact $contact
     *
     * @return AccountContact
     */
    public function setContact(\Sulu\Bundle\ContactBundle\Entity\Contact $contact)
    {
        $this->contact = $contact;

        return $this;
    }

    /**
     * Get contact.
     *
     * @return \Sulu\Bundle\ContactBundle\Entity\Contact
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
