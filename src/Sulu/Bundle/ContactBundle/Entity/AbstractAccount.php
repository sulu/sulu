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
use JMS\Serializer\Annotation\Accessor;
use JMS\Serializer\Annotation\Exclude;
use Sulu\Bundle\CategoryBundle\Entity\CategoryInterface;
use Sulu\Bundle\MediaBundle\Entity\MediaInterface;
use Sulu\Bundle\TagBundle\Entity\Tag;
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
     * @var Collection
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
     * @var Collection
     */
    protected $urls;

    /**
     * @var Collection
     */
    protected $phones;

    /**
     * @var Collection
     */
    protected $emails;

    /**
     * @var Collection
     */
    protected $notes;

    /**
     * @var Collection
     */
    protected $faxes;

    /**
     * @var Collection
     */
    protected $bankAccounts;

    /**
     * @var Collection
     * @Accessor(getter="getTagNameArray")
     */
    protected $tags;

    /**
     * @var Collection
     */
    protected $accountContacts;

    /**
     * @var Collection
     * @Exclude
     */
    protected $accountAddresses;

    /**
     * @var Collection
     */
    protected $medias;

    /**
     * @var Collection
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
     * @param Url $urls
     *
     * @return Account
     */
    public function addUrl(Url $urls)
    {
        $this->urls[] = $urls;

        return $this;
    }

    /**
     * Remove url.
     *
     * @param Url $url
     */
    public function removeUrl(Url $url)
    {
        $this->urls->removeElement($url);
    }

    /**
     * Get urls.
     *
     * @return Collection
     */
    public function getUrls()
    {
        return $this->urls;
    }

    /**
     * Add phones.
     *
     * @param Phone $phone
     *
     * @return Account
     */
    public function addPhone(Phone $phone)
    {
        $this->phones[] = $phone;

        return $this;
    }

    /**
     * Remove phone.
     *
     * @param Phone $phone
     */
    public function removePhone(Phone $phone)
    {
        $this->phones->removeElement($phone);
    }

    /**
     * Get phones.
     *
     * @return Collection
     */
    public function getPhones()
    {
        return $this->phones;
    }

    /**
     * Add emails.
     *
     * @param Email $email
     *
     * @return Account
     */
    public function addEmail(Email $email)
    {
        $this->emails[] = $email;

        return $this;
    }

    /**
     * Remove emails.
     *
     * @param Email $email
     */
    public function removeEmail(Email $email)
    {
        $this->emails->removeElement($email);
    }

    /**
     * Get emails.
     *
     * @return Collection
     */
    public function getEmails()
    {
        return $this->emails;
    }

    /**
     * Add notes.
     *
     * @param Note $note
     *
     * @return Account
     */
    public function addNote(Note $note)
    {
        $this->notes[] = $note;

        return $this;
    }

    /**
     * Remove notes.
     *
     * @param Note $notes
     */
    public function removeNote(Note $note)
    {
        $this->notes->removeElement($note);
    }

    /**
     * Get notes.
     *
     * @return Collection
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
     * @return Collection
     */
    public function getChildren()
    {
        return $this->children;
    }

    /**
     * Add faxes.
     *
     * @param Fax $fax
     *
     * @return Account
     */
    public function addFax(Fax $fax)
    {
        $this->faxes[] = $fax;

        return $this;
    }

    /**
     * Remove fax.
     *
     * @param Fax $faxes
     */
    public function removeFax(Fax $fax)
    {
        $this->faxes->removeElement($fax);
    }

    /**
     * Get faxes.
     *
     * @return Collection
     */
    public function getFaxes()
    {
        return $this->faxes;
    }

    /**
     * Add bankAccounts.
     *
     * @param BankAccount $bankAccount
     *
     * @return Account
     */
    public function addBankAccount(BankAccount $bankAccount)
    {
        $this->bankAccounts[] = $bankAccount;

        return $this;
    }

    /**
     * Remove bankAccount.
     *
     * @param BankAccount $bankAccounts
     */
    public function removeBankAccount(BankAccount $bankAccount)
    {
        $this->bankAccounts->removeElement($bankAccount);
    }

    /**
     * Get bankAccounts.
     *
     * @return Collection
     */
    public function getBankAccounts()
    {
        return $this->bankAccounts;
    }

    /**
     * Add tags.
     *
     * @param Tag $tags
     *
     * @return Account
     */
    public function addTag(Tag $tag)
    {
        $this->tags[] = $tag;

        return $this;
    }

    /**
     * Remove tag.
     *
     * @param Tag $tag
     */
    public function removeTag(Tag $tag)
    {
        $this->tags->removeElement($tag);
    }

    /**
     * Get tags.
     *
     * @return Collection
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
     * Add accountContact.
     *
     * @param AccountContact $accountContact
     *
     * @return Account
     */
    public function addAccountContact(AccountContact $accountContacts)
    {
        $this->accountContacts[] = $accountContacts;

        return $this;
    }

    /**
     * Remove accountContacts.
     *
     * @param AccountContact $accountContacts
     */
    public function removeAccountContact(AccountContact $accountContacts)
    {
        $this->accountContacts->removeElement($accountContacts);
    }

    /**
     * Get accountContacts.
     *
     * @return Collection
     */
    public function getAccountContacts()
    {
        return $this->accountContacts;
    }

    /**
     * Add accountAddresses.
     *
     * @param AccountAddress $accountAddress
     *
     * @return Account
     */
    public function addAccountAddress(AccountAddress $accountAddress)
    {
        $this->accountAddresses[] = $accountAddress;

        return $this;
    }

    /**
     * Remove accountAddresses.
     *
     * @param AccountAddress $accountAddress
     */
    public function removeAccountAddress(AccountAddress $accountAddress)
    {
        $this->accountAddresses->removeElement($accountAddress);
    }

    /**
     * Get accountAddresses.
     *
     * @return Collection
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
     * @return Collection
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
     * @param MediaInterface $media
     *
     * @return Account
     */
    public function addMedia(MediaInterface $media)
    {
        $this->medias[] = $media;

        return $this;
    }

    /**
     * Remove medias.
     *
     * @param MediaInterface $media
     */
    public function removeMedia(MediaInterface $media)
    {
        $this->medias->removeElement($media);
    }

    /**
     * Get medias.
     *
     * @return Collection
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
     * @param CategoryInterface $categories
     *
     * @return Account
     */
    public function addCategory(CategoryInterface $category)
    {
        $this->categories[] = $category;

        return $this;
    }

    /**
     * Remove category.
     *
     * @param CategoryInterface $category
     */
    public function removeCategory(CategoryInterface $category)
    {
        $this->categories->removeElement($category);
    }

    /**
     * Get categories.
     *
     * @return Collection
     */
    public function getCategories()
    {
        return $this->categories;
    }
}
