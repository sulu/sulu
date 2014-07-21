<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContactBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\Exclude;
use JMS\Serializer\Annotation\Accessor;
use Sulu\Bundle\CoreBundle\Entity\ApiEntity;


/**
 * Account
 */
class Account extends ApiEntity
{

    const TYPE_BASIC = 0;
    const TYPE_LEAD = 1;
    const TYPE_CUSTOMER = 2;
    const TYPE_SUPPLIER = 3;

    const ENABLED = 0;
    const DISABLED = 1;

    /**
     * @var integer
     */
    private $lft;

    /**
     * @var integer
     */
    private $rgt;

    /**
     * @var integer
     */
    private $depth;

    /**
     * @var string
     */
    private $name;

    /**
     * @var \DateTime
     */
    private $created;

    /**
     * @var \DateTime
     */
    private $changed;

    /**
     * @var integer
     */
    private $id;

    /**
     * @var \Sulu\Component\Security\UserInterface
     * @Exclude
     */
    private $changer;

    /**
     * @var \Sulu\Component\Security\UserInterface
     * @Exclude
     */
    private $creator;

    /**
     * @var \Sulu\Component\Security\UserInterface
     * @Exclude
     */
    private $children;

    /**
     * @var \Sulu\Bundle\ContactBundle\Entity\Account
     */
    private $parent;

    /**
     * main account
     * @Accessor(getter="getAddresses")
     * @var string
     */
    private $addresses;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $urls;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $phones;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $emails;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $notes;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $faxes;

    /**
     * @var integer
     */
    private $type = self::TYPE_BASIC;

    /**
     * @var string
     */
    private $corporation;

    /**
     * @var integer
     */
    private $disabled = self::ENABLED;

    /**
     * @var string
     */
    private $uid;

    /**
     * @var string
     */
    private $registerNumber;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $bankAccounts;

    /**
     * @var \Doctrine\Common\Collections\Collection
     * @Accessor(getter="getTagNameArray")
     */
    private $tags;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $accountContacts;

    /**
     * @var string
     */
    private $placeOfJurisdiction;

    /**
     * @var \Sulu\Bundle\ContactBundle\Entity\TermsOfPayment
     */
    private $termsOfPayment;

    /**
     * @var \Sulu\Bundle\ContactBundle\Entity\TermsOfDelivery
     */
    private $termsOfDelivery;

    /**
     * @var string
     */
    private $number;

    /**
     * @var \Sulu\Bundle\ContactBundle\Entity\Contact
     */
    private $responsiblePerson;

    /**
     * @var \Doctrine\Common\Collections\Collection
     * @Exclude
     */
    private $activities;

    /**
     * @var string
     */
    private $externalId;

    /**
     * @var string
     */
    private $mainEmail;

    /**
     * @var string
     */
    private $mainPhone;

    /**
     * @var string
     */
    private $mainFax;

    /**
     * @var string
     */
    private $mainUrl;

    /**
     * @var \Doctrine\Common\Collections\Collection
     * @Exclude
     */
    private $accountAddresses;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->urls = new \Doctrine\Common\Collections\ArrayCollection();
        $this->addresses = new \Doctrine\Common\Collections\ArrayCollection();
        $this->phones = new \Doctrine\Common\Collections\ArrayCollection();
        $this->emails = new \Doctrine\Common\Collections\ArrayCollection();
        $this->notes = new \Doctrine\Common\Collections\ArrayCollection();
        $this->faxes = new \Doctrine\Common\Collections\ArrayCollection();
        $this->tags = new \Doctrine\Common\Collections\ArrayCollection();
        $this->accountContacts = new \Doctrine\Common\Collections\ArrayCollection();
        $this->accountAddresses = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Set lft
     *
     * @param integer $lft
     * @return Account
     */
    public function setLft($lft)
    {
        $this->lft = $lft;

        return $this;
    }

    /**
     * Get lft
     *
     * @return integer
     */
    public function getLft()
    {
        return $this->lft;
    }

    /**
     * Set rgt
     *
     * @param integer $rgt
     * @return Account
     */
    public function setRgt($rgt)
    {
        $this->rgt = $rgt;

        return $this;
    }

    /**
     * Get rgt
     *
     * @return integer
     */
    public function getRgt()
    {
        return $this->rgt;
    }

    /**
     * Set depth
     *
     * @param integer $depth
     * @return Account
     */
    public function setDepth($depth)
    {
        $this->depth = $depth;

        return $this;
    }

    /**
     * Get depth
     *
     * @return integer
     */
    public function getDepth()
    {
        return $this->depth;
    }

    /**
     * Set name
     *
     * @param string $name
     * @return Account
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set created
     *
     * @param \DateTime $created
     * @return Account
     */
    public function setCreated($created)
    {
        $this->created = $created;

        return $this;
    }

    /**
     * Get created
     *
     * @return \DateTime
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * Set changed
     *
     * @param \DateTime $changed
     * @return Account
     */
    public function setChanged($changed)
    {
        $this->changed = $changed;

        return $this;
    }

    /**
     * Get changed
     *
     * @return \DateTime
     */
    public function getChanged()
    {
        return $this->changed;
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
     * @var \Sulu\Bundle\ContactBundle\Entity\Contact
     */
    private $mainContact;

    /**
     * Set changer
     *
     * @param \Sulu\Component\Security\UserInterface $changer
     * @return Account
     */
    public function setChanger(\Sulu\Component\Security\UserInterface $changer = null)
    {
        $this->changer = $changer;

        return $this;
    }

    /**
     * Get changer
     *
     * @return \Sulu\Component\Security\UserInterface
     */
    public function getChanger()
    {
        return $this->changer;
    }

    /**
     * Set creator
     *
     * @param \Sulu\Component\Security\UserInterface $creator
     * @return Account
     */
    public function setCreator(\Sulu\Component\Security\UserInterface $creator = null)
    {
        $this->creator = $creator;

        return $this;
    }

    /**
     * Get creator
     *
     * @return \Sulu\Component\Security\UserInterface
     */
    public function getCreator()
    {
        return $this->creator;
    }

    /**
     * Set parent
     *
     * @param \Sulu\Bundle\ContactBundle\Entity\Account $parent
     * @return Account
     */
    public function setParent(\Sulu\Bundle\ContactBundle\Entity\Account $parent = null)
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * Get parent
     *
     * @return \Sulu\Bundle\ContactBundle\Entity\Account
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * Add urls
     *
     * @param \Sulu\Bundle\ContactBundle\Entity\Url $urls
     * @return Account
     */
    public function addUrl(\Sulu\Bundle\ContactBundle\Entity\Url $urls)
    {
        $this->urls[] = $urls;

        return $this;
    }

    /**
     * Remove urls
     *
     * @param \Sulu\Bundle\ContactBundle\Entity\Url $urls
     */
    public function removeUrl(\Sulu\Bundle\ContactBundle\Entity\Url $urls)
    {
        $this->urls->removeElement($urls);
    }

    /**
     * Get urls
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getUrls()
    {
        return $this->urls;
    }

    /**
     * Add phones
     *
     * @param \Sulu\Bundle\ContactBundle\Entity\Phone $phones
     * @return Account
     */
    public function addPhone(\Sulu\Bundle\ContactBundle\Entity\Phone $phones)
    {
        $this->phones[] = $phones;

        return $this;
    }

    /**
     * Remove phones
     *
     * @param \Sulu\Bundle\ContactBundle\Entity\Phone $phones
     */
    public function removePhone(\Sulu\Bundle\ContactBundle\Entity\Phone $phones)
    {
        $this->phones->removeElement($phones);
    }

    /**
     * Get phones
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getPhones()
    {
        return $this->phones;
    }

    /**
     * Add emails
     *
     * @param \Sulu\Bundle\ContactBundle\Entity\Email $emails
     * @return Account
     */
    public function addEmail(\Sulu\Bundle\ContactBundle\Entity\Email $emails)
    {
        $this->emails[] = $emails;

        return $this;
    }

    /**
     * Remove emails
     *
     * @param \Sulu\Bundle\ContactBundle\Entity\Email $emails
     */
    public function removeEmail(\Sulu\Bundle\ContactBundle\Entity\Email $emails)
    {
        $this->emails->removeElement($emails);
    }

    /**
     * Get emails
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getEmails()
    {
        return $this->emails;
    }

    /**
     * Add notes
     *
     * @param \Sulu\Bundle\ContactBundle\Entity\Note $notes
     * @return Account
     */
    public function addNote(\Sulu\Bundle\ContactBundle\Entity\Note $notes)
    {
        $this->notes[] = $notes;

        return $this;
    }

    /**
     * Remove notes
     *
     * @param \Sulu\Bundle\ContactBundle\Entity\Note $notes
     */
    public function removeNote(\Sulu\Bundle\ContactBundle\Entity\Note $notes)
    {
        $this->notes->removeElement($notes);
    }

    /**
     * Get notes
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getNotes()
    {
        return $this->notes;
    }

    /**
     * Add children
     *
     * @param \Sulu\Bundle\ContactBundle\Entity\Account $children
     * @return Account
     */
    public function addChildren(\Sulu\Bundle\ContactBundle\Entity\Account $children)
    {
        $this->children[] = $children;

        return $this;
    }

    /**
     * Remove children
     *
     * @param \Sulu\Bundle\ContactBundle\Entity\Account $children
     */
    public function removeChildren(\Sulu\Bundle\ContactBundle\Entity\Account $children)
    {
        $this->children->removeElement($children);
    }

    /**
     * Get children
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getChildren()
    {
        return $this->children;
    }


    /**
     * Set type
     *
     * @param integer $type
     * @return Account
     */
    public function setType($type)
    {
        $this->type = $type;
    
        return $this;
    }

    /**
     * Get type
     *
     * @return integer 
     */
    public function getType()
    {
        return $this->type;
    }



    /**
     * Add faxes
     *
     * @param \Sulu\Bundle\ContactBundle\Entity\Fax $faxes
     * @return Account
     */
    public function addFax(\Sulu\Bundle\ContactBundle\Entity\Fax $faxes)
    {
        $this->faxes[] = $faxes;
    
        return $this;
    }

    /**
     * Remove faxes
     *
     * @param \Sulu\Bundle\ContactBundle\Entity\Fax $faxes
     */
    public function removeFax(\Sulu\Bundle\ContactBundle\Entity\Fax $faxes)
    {
        $this->faxes->removeElement($faxes);
    }

    /**
     * Get faxes
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getFaxes()
    {
        return $this->faxes;
    }

    /**
     * Set corporation
     *
     * @param string $corporation
     * @return Account
     */
    public function setCorporation($corporation)
    {
        $this->corporation = $corporation;
    
        return $this;
    }

    /**
     * Get corporation
     *
     * @return string 
     */
    public function getCorporation()
    {
        return $this->corporation;
    }

    /**
     * Set disabled
     *
     * @param integer $disabled
     * @return Account
     */
    public function setDisabled($disabled)
    {
        $this->disabled = $disabled;
    
        return $this;
    }

    /**
     * Get disabled
     *
     * @return integer 
     */
    public function getDisabled()
    {
        return $this->disabled;
    }

    /**
     * Set uid
     *
     * @param string $uid
     * @return Account
     */
    public function setUid($uid)
    {
        $this->uid = $uid;
    
        return $this;
    }

    /**
     * Get uid
     *
     * @return string 
     */
    public function getUid()
    {
        return $this->uid;
    }

    /**
     * Add faxes
     *
     * @param \Sulu\Bundle\ContactBundle\Entity\Fax $faxes
     * @return Account
     */
    public function addFaxe(\Sulu\Bundle\ContactBundle\Entity\Fax $faxes)
    {
        $this->faxes[] = $faxes;
    
        return $this;
    }

    /**
     * Remove faxes
     *
     * @param \Sulu\Bundle\ContactBundle\Entity\Fax $faxes
     */
    public function removeFaxe(\Sulu\Bundle\ContactBundle\Entity\Fax $faxes)
    {
        $this->faxes->removeElement($faxes);
    }

    /**
     * @var \Sulu\Bundle\ContactBundle\Entity\AccountCategory
     */
    private $accountCategory;


    /**
     * Set accountCategory
     *
     * @param \Sulu\Bundle\ContactBundle\Entity\AccountCategory $accountCategory
     * @return Account
     */
    public function setAccountCategory(\Sulu\Bundle\ContactBundle\Entity\AccountCategory $accountCategory = null)
    {
        $this->accountCategory = $accountCategory;
    }

    /**
     * Set registerNumber
     *
     * @param string $registerNumber
     * @return Account
     */
    public function setRegisterNumber($registerNumber)
    {
        $this->registerNumber = $registerNumber;
    
        return $this;
    }

    /**
     * Get registerNumber
     *
     * @return string 
     */
    public function getRegisterNumber()
    {
        return $this->registerNumber;
    }

    /**
     * Add bankAccounts
     *
     * @param \Sulu\Bundle\ContactBundle\Entity\BankAccount $bankAccounts
     * @return Account
     */
    public function addBankAccount(\Sulu\Bundle\ContactBundle\Entity\BankAccount $bankAccounts)
    {
        $this->bankAccounts[] = $bankAccounts;
    
        return $this;
    }

    /**
     * Get accountCategory
     *
     * @return \Sulu\Bundle\ContactBundle\Entity\AccountCategory 
     */
    public function getAccountCategory()
    {
        return $this->accountCategory;
    }

    /**
     * Remove bankAccounts
     *
     * @param \Sulu\Bundle\ContactBundle\Entity\BankAccount $bankAccounts
     */
    public function removeBankAccount(\Sulu\Bundle\ContactBundle\Entity\BankAccount $bankAccounts)
    {
        $this->bankAccounts->removeElement($bankAccounts);
    }

    /**
     * Get bankAccounts
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getBankAccounts()
    {
        return $this->bankAccounts;
    }

    /**
     * Add tags
     *
     * @param \Sulu\Bundle\TagBundle\Entity\Tag $tags
     * @return Account
     */
    public function addTag(\Sulu\Bundle\TagBundle\Entity\Tag $tags)
    {
        $this->tags[] = $tags;
    
        return $this;
    }

    /**
     * Remove tags
     *
     * @param \Sulu\Bundle\TagBundle\Entity\Tag $tags
     */
    public function removeTag(\Sulu\Bundle\TagBundle\Entity\Tag $tags)
    {
        $this->tags->removeElement($tags);
    }

    /**
     * Get tags
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getTags()
    {
        return $this->tags;
    }

    /**
     * parses tags to array containing tag names
     * @return array
     */
    public function getTagNameArray()
    {
        $tags = array();
        if (!is_null($this->getTags())) {
            foreach ($this->getTags() as $tag) {
                $tags[] = $tag->getName();
            }
        }
        return $tags;
    }

    /**
     * Add accountContacts
     *
     * @param \Sulu\Bundle\ContactBundle\Entity\AccountContact $accountContacts
     * @return Account
     */
    public function addAccountContact(\Sulu\Bundle\ContactBundle\Entity\AccountContact $accountContacts)
    {
        $this->accountContacts[] = $accountContacts;
    
        return $this;
    }

    /**
     * Remove accountContacts
     *
     * @param \Sulu\Bundle\ContactBundle\Entity\AccountContact $accountContacts
     */
    public function removeAccountContact(\Sulu\Bundle\ContactBundle\Entity\AccountContact $accountContacts)
    {
        $this->accountContacts->removeElement($accountContacts);
    }

    /**
     * Get accountContacts
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getAccountContacts()
    {
        return $this->accountContacts;
    }

    /**
     * Set placeOfJurisdiction
     *
     * @param string $placeOfJurisdiction
     * @return Account
     */
    public function setPlaceOfJurisdiction($placeOfJurisdiction)
    {
        $this->placeOfJurisdiction = $placeOfJurisdiction;
    
        return $this;
    }

    /**
     * Get placeOfJurisdiction
     *
     * @return string 
     */
    public function getPlaceOfJurisdiction()
    {
        return $this->placeOfJurisdiction;
    }

    /**
     * Set termsOfPayment
     *
     * @param \Sulu\Bundle\ContactBundle\Entity\TermsOfPayment $termsOfPayment
     * @return Account
     */
    public function setTermsOfPayment(\Sulu\Bundle\ContactBundle\Entity\TermsOfPayment $termsOfPayment = null)
    {
        $this->termsOfPayment = $termsOfPayment;
    
        return $this;
    }

    /**
     * Get termsOfPayment
     *
     * @return \Sulu\Bundle\ContactBundle\Entity\TermsOfPayment 
     */
    public function getTermsOfPayment()
    {
        return $this->termsOfPayment;
    }

    /**
     * Set termsOfDelivery
     *
     * @param \Sulu\Bundle\ContactBundle\Entity\TermsOfDelivery $termsOfDelivery
     * @return Account
     */
    public function setTermsOfDelivery(\Sulu\Bundle\ContactBundle\Entity\TermsOfDelivery $termsOfDelivery = null)
    {
        $this->termsOfDelivery = $termsOfDelivery;
    
        return $this;
    }

    /**
     * Get termsOfDelivery
     *
     * @return \Sulu\Bundle\ContactBundle\Entity\TermsOfDelivery 
     */
    public function getTermsOfDelivery()
    {
        return $this->termsOfDelivery;
    }

    /**
     * Set number
     *
     * @param string $number
     * @return Account
     */
    public function setNumber($number)
    {
        $this->number = $number;
    
        return $this;
    }

    /**
     * Get number
     *
     * @return string 
     */
    public function getNumber()
    {
        return $this->number;
    }

    /**
     * Set externalId
     *
     * @param string $externalId
     * @return Account
     */
    public function setExternalId($externalId)
    {
        $this->externalId = $externalId;
    
        return $this;
    }

    /**
     * Get externalId
     *
     * @return string 
     */
    public function getExternalId()
    {
        return $this->externalId;
    }

    /**
     * Set responsiblePerson
     *
     * @param \Sulu\Bundle\ContactBundle\Entity\Contact $responsiblePerson
     * @return Account
     */
    public function setResponsiblePerson(\Sulu\Bundle\ContactBundle\Entity\Contact $responsiblePerson = null)
    {
        $this->responsiblePerson = $responsiblePerson;
    }

    /**
     * Set mainContact
     *
     * @param \Sulu\Bundle\ContactBundle\Entity\Contact $mainContact
     * @return Account
     */
    public function setMainContact(\Sulu\Bundle\ContactBundle\Entity\Contact $mainContact = null)
    {
        $this->mainContact = $mainContact;
    
        return $this;
    }

    /**
     * Get responsiblePerson
     *
     * @return \Sulu\Bundle\ContactBundle\Entity\Contact 
     */
    public function getResponsiblePerson()
    {
        return $this->responsiblePerson;
    }

    /**
     * Add activities
     *
     * @param \Sulu\Bundle\ContactBundle\Entity\Activity $activities
     * @return Account
     */
    public function addActivitie(\Sulu\Bundle\ContactBundle\Entity\Activity $activities)
    {
        $this->activities[] = $activities;
    }

    /**
     * Get mainContact
     *
     * @return \Sulu\Bundle\ContactBundle\Entity\Contact 
     */
    public function getMainContact()
    {
        return $this->mainContact;
    }

    /**
     * Set mainEmail
     *
     * @param string $mainEmail
     * @return Account
     */
    public function setMainEmail($mainEmail)
    {
        $this->mainEmail = $mainEmail;
    
        return $this;
    }

    /**
     * Get mainEmail
     *
     * @return string 
     */
    public function getMainEmail()
    {
        return $this->mainEmail;
    }

    /**
     * Set mainPhone
     *
     * @param string $mainPhone
     * @return Account
     */
    public function setMainPhone($mainPhone)
    {
        $this->mainPhone = $mainPhone;
    
        return $this;
    }

    /**
     * Get mainPhone
     *
     * @return string 
     */
    public function getMainPhone()
    {
        return $this->mainPhone;
    }

    /**
     * Set mainFax
     *
     * @param string $mainFax
     * @return Account
     */
    public function setMainFax($mainFax)
    {
        $this->mainFax = $mainFax;
        return $this;
    }

    /**
     * Remove activities
     *
     * @param \Sulu\Bundle\ContactBundle\Entity\Activity $activities
     */
    public function removeActivitie(\Sulu\Bundle\ContactBundle\Entity\Activity $activities)
    {
        $this->activities->removeElement($activities);
    }

    /**
     * Get activities
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getActivities()
    {
        return $this->activities;
    }

    /**
     * Get mainFax
     *
     * @return string 
     */
    public function getMainFax()
    {
        return $this->mainFax;
    }

    /**
     * Set mainUrl
     *
     * @param string $mainUrl
     * @return Account
     */
    public function setMainUrl($mainUrl)
    {
        $this->mainUrl = $mainUrl;
    
        return $this;
    }

    /**
     * Get mainUrl
     *
     * @return string 
     */
    public function getMainUrl()
    {
        return $this->mainUrl;
    }

    /**
     * Add accountAddresses
     *
     * @param \Sulu\Bundle\ContactBundle\Entity\AccountAddress $accountAddresses
     * @return Account
     */
    public function addAccountAddresse(\Sulu\Bundle\ContactBundle\Entity\AccountAddress $accountAddresses)
    {
        $this->accountAddresses[] = $accountAddresses;
    
        return $this;
    }

    /**
     * Remove accountAddresses
     *
     * @param \Sulu\Bundle\ContactBundle\Entity\AccountAddress $accountAddresses
     */
    public function removeAccountAddresse(\Sulu\Bundle\ContactBundle\Entity\AccountAddress $accountAddresses)
    {
        $this->accountAddresses->removeElement($accountAddresses);
    }

    /**
     * Get accountAddresses
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getAccountAddresses()
    {
        return $this->accountAddresses;
    }

    /**
     * returns main account
     */
    public function getAddresses()
    {
        $accountAddresses = $this->getAccountAddresses();
        $addresses = array();

        if (!is_null($accountAddresses)) {
            /** @var ContactAddress $contactAddress */
            foreach ($accountAddresses as $accountAddress) {
                $address = $accountAddress->getAddress();
                $address->setPrimaryAddress($accountAddress->getMain());
                $addresses[] = $address;
            }
        }
        return $addresses;
    }
}