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
use JMS\Serializer\Annotation\Accessor;
use JMS\Serializer\Annotation\Exclude;
use Sulu\Component\Persistence\Model\AuditableInterface;

/**
 * Account.
 */
class AbstractAccount extends BaseAccount implements AuditableInterface, AccountInterface
{
    /**
     * @var int
     */
    protected $lft;

    /**
     * @var int
     */
    protected $rgt;

    /**
     * @var int
     */
    protected $depth;

    /**
     * @var \Doctrine\Common\Collections\Collection
     * @Exclude
     */
    protected $children;

    /**
     * @var AccountInterface
     */
    protected $parent;

    /**
     * main account.
     *
     * @Accessor(getter="getAddresses")
     *
     * @var string
     */
    protected $addresses;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    protected $urls;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    protected $phones;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    protected $emails;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    protected $notes;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    protected $faxes;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    protected $bankAccounts;

    /**
     * @var \Doctrine\Common\Collections\Collection
     * @Accessor(getter="getTagNameArray")
     */
    protected $tags;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    protected $accountContacts;

    /**
     * @var \Doctrine\Common\Collections\Collection
     * @Exclude
     */
    protected $accountAddresses;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    protected $medias;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    protected $categories;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->children = new ArrayCollection();
        $this->urls = new ArrayCollection();
        $this->addresses = new ArrayCollection();
        $this->phones = new ArrayCollection();
        $this->emails = new ArrayCollection();
        $this->notes = new ArrayCollection();
        $this->faxes = new ArrayCollection();
        $this->tags = new ArrayCollection();
        $this->categories = new ArrayCollection();
        $this->accountContacts = new ArrayCollection();
        $this->accountAddresses = new ArrayCollection();
    }

    /**
     * Set lft.
     *
     * @param int $lft
     *
     * @return Account
     */
    public function setLft($lft)
    {
        $this->lft = $lft;

        return $this;
    }

    /**
     * Get lft.
     *
     * @return int
     */
    public function getLft()
    {
        return $this->lft;
    }

    /**
     * Set rgt.
     *
     * @param int $rgt
     *
     * @return Account
     */
    public function setRgt($rgt)
    {
        $this->rgt = $rgt;

        return $this;
    }

    /**
     * Get rgt.
     *
     * @return int
     */
    public function getRgt()
    {
        return $this->rgt;
    }

    /**
     * Set depth.
     *
     * @param int $depth
     *
     * @return Account
     */
    public function setDepth($depth)
    {
        $this->depth = $depth;

        return $this;
    }

    /**
     * Get depth.
     *
     * @return int
     */
    public function getDepth()
    {
        return $this->depth;
    }

    /**
     * Set parent.
     *
     * @param AccountInterface $parent
     *
     * @return Account
     */
    public function setParent(AccountInterface $parent = null)
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * Get parent.
     *
     * @return AccountInterface
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * Add urls.
     *
     * @param \Sulu\Bundle\ContactBundle\Entity\Url $urls
     *
     * @return Account
     */
    public function addUrl(\Sulu\Bundle\ContactBundle\Entity\Url $urls)
    {
        $this->urls[] = $urls;

        return $this;
    }

    /**
     * Remove urls.
     *
     * @param \Sulu\Bundle\ContactBundle\Entity\Url $urls
     */
    public function removeUrl(\Sulu\Bundle\ContactBundle\Entity\Url $urls)
    {
        $this->urls->removeElement($urls);
    }

    /**
     * Get urls.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getUrls()
    {
        return $this->urls;
    }

    /**
     * Add phones.
     *
     * @param \Sulu\Bundle\ContactBundle\Entity\Phone $phones
     *
     * @return Account
     */
    public function addPhone(\Sulu\Bundle\ContactBundle\Entity\Phone $phones)
    {
        $this->phones[] = $phones;

        return $this;
    }

    /**
     * Remove phones.
     *
     * @param \Sulu\Bundle\ContactBundle\Entity\Phone $phones
     */
    public function removePhone(\Sulu\Bundle\ContactBundle\Entity\Phone $phones)
    {
        $this->phones->removeElement($phones);
    }

    /**
     * Get phones.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getPhones()
    {
        return $this->phones;
    }

    /**
     * Add emails.
     *
     * @param \Sulu\Bundle\ContactBundle\Entity\Email $emails
     *
     * @return Account
     */
    public function addEmail(\Sulu\Bundle\ContactBundle\Entity\Email $emails)
    {
        $this->emails[] = $emails;

        return $this;
    }

    /**
     * Remove emails.
     *
     * @param \Sulu\Bundle\ContactBundle\Entity\Email $emails
     */
    public function removeEmail(\Sulu\Bundle\ContactBundle\Entity\Email $emails)
    {
        $this->emails->removeElement($emails);
    }

    /**
     * Get emails.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getEmails()
    {
        return $this->emails;
    }

    /**
     * Add notes.
     *
     * @param \Sulu\Bundle\ContactBundle\Entity\Note $notes
     *
     * @return Account
     */
    public function addNote(\Sulu\Bundle\ContactBundle\Entity\Note $notes)
    {
        $this->notes[] = $notes;

        return $this;
    }

    /**
     * Remove notes.
     *
     * @param \Sulu\Bundle\ContactBundle\Entity\Note $notes
     */
    public function removeNote(\Sulu\Bundle\ContactBundle\Entity\Note $notes)
    {
        $this->notes->removeElement($notes);
    }

    /**
     * Get notes.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getNotes()
    {
        return $this->notes;
    }

    /**
     * Add children.
     *
     * @param AccountInterface $children
     *
     * @return Account
     */
    public function addChildren(AccountInterface $children)
    {
        $this->children[] = $children;

        return $this;
    }

    /**
     * Remove children.
     *
     * @param AccountInterface $children
     */
    public function removeChildren(AccountInterface $children)
    {
        $this->children->removeElement($children);
    }

    /**
     * Get children.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getChildren()
    {
        return $this->children;
    }

    /**
     * Add faxes.
     *
     * @param \Sulu\Bundle\ContactBundle\Entity\Fax $faxes
     *
     * @return Account
     */
    public function addFax(\Sulu\Bundle\ContactBundle\Entity\Fax $faxes)
    {
        $this->faxes[] = $faxes;

        return $this;
    }

    /**
     * Remove faxes.
     *
     * @param \Sulu\Bundle\ContactBundle\Entity\Fax $faxes
     */
    public function removeFax(\Sulu\Bundle\ContactBundle\Entity\Fax $faxes)
    {
        $this->faxes->removeElement($faxes);
    }

    /**
     * Get faxes.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getFaxes()
    {
        return $this->faxes;
    }

    /**
     * Add faxes.
     *
     * @param \Sulu\Bundle\ContactBundle\Entity\Fax $faxes
     *
     * @return Account
     */
    public function addFaxe(\Sulu\Bundle\ContactBundle\Entity\Fax $faxes)
    {
        $this->faxes[] = $faxes;

        return $this;
    }

    /**
     * Remove faxes.
     *
     * @param \Sulu\Bundle\ContactBundle\Entity\Fax $faxes
     */
    public function removeFaxe(\Sulu\Bundle\ContactBundle\Entity\Fax $faxes)
    {
        $this->faxes->removeElement($faxes);
    }

    /**
     * Add bankAccounts.
     *
     * @param \Sulu\Bundle\ContactBundle\Entity\BankAccount $bankAccounts
     *
     * @return Account
     */
    public function addBankAccount(\Sulu\Bundle\ContactBundle\Entity\BankAccount $bankAccounts)
    {
        $this->bankAccounts[] = $bankAccounts;

        return $this;
    }

    /**
     * Remove bankAccounts.
     *
     * @param \Sulu\Bundle\ContactBundle\Entity\BankAccount $bankAccounts
     */
    public function removeBankAccount(\Sulu\Bundle\ContactBundle\Entity\BankAccount $bankAccounts)
    {
        $this->bankAccounts->removeElement($bankAccounts);
    }

    /**
     * Get bankAccounts.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getBankAccounts()
    {
        return $this->bankAccounts;
    }

    /**
     * Add tags.
     *
     * @param \Sulu\Bundle\TagBundle\Entity\Tag $tags
     *
     * @return Account
     */
    public function addTag(\Sulu\Bundle\TagBundle\Entity\Tag $tags)
    {
        $this->tags[] = $tags;

        return $this;
    }

    /**
     * Remove tags.
     *
     * @param \Sulu\Bundle\TagBundle\Entity\Tag $tags
     */
    public function removeTag(\Sulu\Bundle\TagBundle\Entity\Tag $tags)
    {
        $this->tags->removeElement($tags);
    }

    /**
     * Get tags.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getTags()
    {
        return $this->tags;
    }

    /**
     * parses tags to array containing tag names.
     *
     * @return array
     */
    public function getTagNameArray()
    {
        $tags = [];
        if (!is_null($this->getTags())) {
            foreach ($this->getTags() as $tag) {
                $tags[] = $tag->getName();
            }
        }

        return $tags;
    }

    /**
     * Add accountContacts.
     *
     * @param \Sulu\Bundle\ContactBundle\Entity\AccountContact $accountContacts
     *
     * @return Account
     */
    public function addAccountContact(\Sulu\Bundle\ContactBundle\Entity\AccountContact $accountContacts)
    {
        $this->accountContacts[] = $accountContacts;

        return $this;
    }

    /**
     * Remove accountContacts.
     *
     * @param \Sulu\Bundle\ContactBundle\Entity\AccountContact $accountContacts
     */
    public function removeAccountContact(\Sulu\Bundle\ContactBundle\Entity\AccountContact $accountContacts)
    {
        $this->accountContacts->removeElement($accountContacts);
    }

    /**
     * Get accountContacts.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getAccountContacts()
    {
        return $this->accountContacts;
    }

    /**
     * Add accountAddresses.
     *
     * @param \Sulu\Bundle\ContactBundle\Entity\AccountAddress $accountAddress
     *
     * @return Account
     */
    public function addAccountAddress(\Sulu\Bundle\ContactBundle\Entity\AccountAddress $accountAddress)
    {
        $this->accountAddresses[] = $accountAddress;

        return $this;
    }

    /**
     * Remove accountAddresses.
     *
     * @param \Sulu\Bundle\ContactBundle\Entity\AccountAddress $accountAddress
     */
    public function removeAccountAddress(\Sulu\Bundle\ContactBundle\Entity\AccountAddress $accountAddress)
    {
        $this->accountAddresses->removeElement($accountAddress);
    }

    /**
     * Get accountAddresses.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getAccountAddresses()
    {
        return $this->accountAddresses;
    }

    /**
     * returns main account.
     */
    public function getAddresses()
    {
        $accountAddresses = $this->getAccountAddresses();
        $addresses = [];

        if (!is_null($accountAddresses)) {
            /* @var ContactAddress $contactAddress */
            foreach ($accountAddresses as $accountAddress) {
                $address = $accountAddress->getAddress();
                $address->setPrimaryAddress($accountAddress->getMain());
                $addresses[] = $address;
            }
        }

        return $addresses;
    }

    /**
     * Returns the main address.
     *
     * @return mixed
     */
    public function getMainAddress()
    {
        $accountAddresses = $this->getAccountAddresses();

        if (!is_null($accountAddresses)) {
            /** @var AccountAddress $accountAddress */
            foreach ($accountAddresses as $accountAddress) {
                if ($accountAddress->getMain()) {
                    return $accountAddress->getAddress();
                }
            }
        }

        return;
    }

    /**
     * Get contacts.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getContacts()
    {
        $accountContacts = $this->getAccountContacts();
        $contacts = [];

        if (!is_null($accountContacts)) {
            /** @var AccountContact $accountContact */
            foreach ($accountContacts as $accountContact) {
                $contacts[] = $accountContact->getContact();
            }
        }

        return $contacts;
    }

    /**
     * Add medias.
     *
     * @param \Sulu\Bundle\MediaBundle\Entity\Media $medias
     *
     * @return Account
     */
    public function addMedia(\Sulu\Bundle\MediaBundle\Entity\Media $medias)
    {
        $this->medias[] = $medias;

        return $this;
    }

    /**
     * Remove medias.
     *
     * @param \Sulu\Bundle\MediaBundle\Entity\Media $medias
     */
    public function removeMedia(\Sulu\Bundle\MediaBundle\Entity\Media $medias)
    {
        $this->medias->removeElement($medias);
    }

    /**
     * Get medias.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getMedias()
    {
        return $this->medias;
    }

    /**
     * Add children.
     *
     * @param AccountInterface $children
     *
     * @return Account
     */
    public function addChild(AccountInterface $children)
    {
        $this->children[] = $children;

        return $this;
    }

    /**
     * Remove children.
     *
     * @param AccountInterface $children
     */
    public function removeChild(AccountInterface $children)
    {
        $this->children->removeElement($children);
    }

    /**
     * Add categories.
     *
     * @param \Sulu\Bundle\CategoryBundle\Entity\Category $categories
     *
     * @return Account
     */
    public function addCategory(\Sulu\Bundle\CategoryBundle\Entity\Category $categories)
    {
        $this->categories[] = $categories;

        return $this;
    }

    /**
     * Remove categories.
     *
     * @param \Sulu\Bundle\CategoryBundle\Entity\Category $categories
     */
    public function removeCategory(\Sulu\Bundle\CategoryBundle\Entity\Category $categories)
    {
        $this->categories->removeElement($categories);
    }

    /**
     * Get categories.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getCategories()
    {
        return $this->categories;
    }
}
