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
     * array containing all the translations for CRM types
     * @Exclude
     * @var array
     */
    public static $TYPE_TRANSLATIONS = array(
        self::TYPE_BASIC => 'contact.account.type.basic',
        self::TYPE_LEAD => 'contact.account.type.lead',
        self::TYPE_CUSTOMER => 'contact.account.type.customer',
        self::TYPE_SUPPLIER => 'contact.account.type.supplier',
    );

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
     * @var \Doctrine\Common\Collections\Collection
     */
    private $contacts;

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
     * @var \Doctrine\Common\Collections\Collection
     */
    private $urls;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $addresses;

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
    private $division;

    /**
     * @var integer
     */
    private $disabled = self::ENABLED;

    /**
     * @var string
     */
    private $uid;


    /**
     * Constructor
     */
    public function __construct()
    {
        $this->contacts = new \Doctrine\Common\Collections\ArrayCollection();
        $this->urls = new \Doctrine\Common\Collections\ArrayCollection();
        $this->addresses = new \Doctrine\Common\Collections\ArrayCollection();
        $this->phones = new \Doctrine\Common\Collections\ArrayCollection();
        $this->emails = new \Doctrine\Common\Collections\ArrayCollection();
        $this->notes = new \Doctrine\Common\Collections\ArrayCollection();
        $this->faxes = new \Doctrine\Common\Collections\ArrayCollection();
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
     * Add contacts
     *
     * @param \Sulu\Bundle\ContactBundle\Entity\Contact $contacts
     * @return Account
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
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getContacts()
    {
        return $this->contacts;
    }

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
     * Add addresses
     *
     * @param \Sulu\Bundle\ContactBundle\Entity\Address $addresses
     * @return Account
     */
    public function addAddresse(\Sulu\Bundle\ContactBundle\Entity\Address $addresses)
    {
        $this->addresses[] = $addresses;

        return $this;
    }

    /**
     * Remove addresses
     *
     * @param \Sulu\Bundle\ContactBundle\Entity\Address $addresses
     */
    public function removeAddresse(\Sulu\Bundle\ContactBundle\Entity\Address $addresses)
    {
        $this->addresses->removeElement($addresses);
    }

    /**
     * Get addresses
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getAddresses()
    {
        return $this->addresses;
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
     * Set division
     *
     * @param string $division
     * @return Account
     */
    public function setDivision($division)
    {
        $this->division = $division;
    
        return $this;
    }

    /**
     * Get division
     *
     * @return string 
     */
    public function getDivision()
    {
        return $this->division;
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
}
