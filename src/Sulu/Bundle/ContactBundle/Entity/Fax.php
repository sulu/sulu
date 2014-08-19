<?php

namespace Sulu\Bundle\ContactBundle\Entity;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\Exclude;
use JMS\Serializer\Annotation\Groups;

/**
 * Fax
 */
class Fax
{
    /**
     * @var string
     * @Groups({"fullAccount", "partialAccount", "fullContact", "partialContact"})
     */
    private $fax;

    /**
     * @var integer
     * @Groups({"fullAccount", "partialAccount", "fullContact", "partialContact"})
     */
    private $id;

    /**
     * @var FaxType
     * @Groups({"fullAccount", "fullContact"})
     */
    private $faxType;

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
     * Constructor
     */
    public function __construct()
    {
        $this->contacts = new \Doctrine\Common\Collections\ArrayCollection();
        $this->accounts = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Set fax
     *
     * @param string $fax
     * @return Fax
     */
    public function setFax($fax)
    {
        $this->fax = $fax;

        return $this;
    }

    /**
     * Get fax
     *
     * @return string
     */
    public function getFax()
    {
        return $this->fax;
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
     * Set faxType
     *
     * @param FaxType $faxType
     * @return Fax
     */
    public function setFaxType(FaxType $faxType)
    {
        $this->faxType = $faxType;

        return $this;
    }

    /**
     * Get faxType
     *
     * @return FaxType
     */
    public function getFaxType()
    {
        return $this->faxType;
    }

    /**
     * Add contacts
     *
     * @param \Sulu\Bundle\ContactBundle\Entity\Contact $contacts
     * @return Fax
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
     * @return Collection
     */
    public function getContacts()
    {
        return $this->contacts;
    }

    /**
     * Add accounts
     *
     * @param \Sulu\Bundle\ContactBundle\Entity\Account $accounts
     * @return Fax
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
     * @return Collection
     */
    public function getAccounts()
    {
        return $this->accounts;
    }
}
