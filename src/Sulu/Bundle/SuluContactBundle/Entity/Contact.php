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
use Sulu\Bundle\CoreBundle\Entity\ApiEntity;
use JMS\Serializer\Annotation\Exclude;
use JMS\Serializer\Annotation\VirtualProperty;
use JMS\Serializer\Annotation\SerializedName;
use JMS\Serializer\Annotation\PostDeserialize;
use JMS\Serializer\Annotation\PostSerialize;
use JMS\Serializer\Annotation\Accessor;

/**
 * Contact
 */
class Contact extends ApiEntity
{
    /**
     * @var string
     */
    private $firstName;

    /**
     * @var string
     */
    private $middleName;

    /**
     * @var string
     */
    private $lastName;

    /**
     * @var string
     */
    private $title;

    /**
     * @var string
     */
    private $position;

    /**
     * @var \DateTime
     */
    private $birthday;

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
    private $locales;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $activities;

    /**
     * @var \Sulu\Component\Security\UserInterface
     */
    private $changer;

    /**
     * @var \Sulu\Component\Security\UserInterface
     */
    private $creator;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $notes;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $emails;

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
    private $faxes;

    /**
     * @var integer
     */
    private $formOfAddress = 0;

    /**
     * @var string
     */
    private $salutation;

    /**
     * @var integer
     */
    private $disabled = 0;

    /**
     * @var \Doctrine\Common\Collections\Collection
     * @Accessor(getter="getTagNameArray")
     */
    private $tags;

    /**
     * main account
     * @Accessor(getter="getMainAccount")
     * @var string
     */
    private $account;

    /**
     * @var \Doctrine\Common\Collections\Collection
     * @Exclude
     */
    private $accountContacts;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->locales = new \Doctrine\Common\Collections\ArrayCollection();
        $this->activities = new \Doctrine\Common\Collections\ArrayCollection();
        $this->notes = new \Doctrine\Common\Collections\ArrayCollection();
        $this->emails = new \Doctrine\Common\Collections\ArrayCollection();
        $this->addresses = new \Doctrine\Common\Collections\ArrayCollection();
        $this->phones = new \Doctrine\Common\Collections\ArrayCollection();
        $this->faxes = new \Doctrine\Common\Collections\ArrayCollection();
        $this->tags = new \Doctrine\Common\Collections\ArrayCollection();
        $this->accountContacts = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Set firstName
     *
     * @param string $firstName
     * @return Contact
     */
    public function setFirstName($firstName)
    {
        $this->firstName = $firstName;

        return $this;
    }

    /**
     * Get firstName
     *
     * @return string
     */
    public function getFirstName()
    {
        return $this->firstName;
    }

    /**
     * Set middleName
     *
     * @param string $middleName
     * @return Contact
     */
    public function setMiddleName($middleName)
    {
        $this->middleName = $middleName;

        return $this;
    }

    /**
     * Get middleName
     *
     * @return string
     */
    public function getMiddleName()
    {
        return $this->middleName;
    }

    /**
     * Set lastName
     *
     * @param string $lastName
     * @return Contact
     */
    public function setLastName($lastName)
    {
        $this->lastName = $lastName;

        return $this;
    }

    /**
     * Get lastName
     *
     * @return string
     */
    public function getLastName()
    {
        return $this->lastName;
    }

    /**
     * @VirtualProperty
     * @SerializedName("fullName")
     * @return string
     */
    public function getFullName()
    {
        return $this->firstName . ' ' . $this->lastName;
    }

    /**
     * Set title
     *
     * @param string $title
     * @return Contact
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get title
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set position
     *
     * @param string $position
     * @return Contact
     */
    public function setPosition($position)
    {
        $this->position = $position;

        return $this;
    }

    /**
     * Get position
     *
     * @return string
     */
    public function getPosition()
    {
        return $this->position;
    }

    /**
     * Set birthday
     *
     * @param \DateTime $birthday
     * @return Contact
     */
    public function setBirthday($birthday)
    {
        $this->birthday = $birthday;

        return $this;
    }

    /**
     * Get birthday
     *
     * @return \DateTime
     */
    public function getBirthday()
    {
        return $this->birthday;
    }

    /**
     * Set created
     *
     * @param \DateTime $created
     * @return Contact
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
     * @return Contact
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
     * Add locales
     *
     * @param \Sulu\Bundle\ContactBundle\Entity\ContactLocale $locales
     * @return Contact
     */
    public function addLocale(\Sulu\Bundle\ContactBundle\Entity\ContactLocale $locales)
    {
        $this->locales[] = $locales;

        return $this;
    }

    /**
     * Remove locales
     *
     * @param \Sulu\Bundle\ContactBundle\Entity\ContactLocale $locales
     */
    public function removeLocale(\Sulu\Bundle\ContactBundle\Entity\ContactLocale $locales)
    {
        $this->locales->removeElement($locales);
    }

    /**
     * Get locales
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getLocales()
    {
        return $this->locales;
    }

    /**
     * Add activities
     *
     * @param \Sulu\Bundle\ContactBundle\Entity\Activity $activities
     * @return Contact
     */
    public function addActivitie(\Sulu\Bundle\ContactBundle\Entity\Activity $activities)
    {
        $this->activities[] = $activities;

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
     * Set changer
     *
     * @param \Sulu\Component\Security\UserInterface $changer
     * @return Contact
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
     * @return Contact
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
     * Add notes
     *
     * @param \Sulu\Bundle\ContactBundle\Entity\Note $notes
     * @return Contact
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
     * Add emails
     *
     * @param \Sulu\Bundle\ContactBundle\Entity\Email $emails
     * @return Contact
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
     * Add addresses
     *
     * @param \Sulu\Bundle\ContactBundle\Entity\Address $addresses
     * @return Contact
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
     * @return Contact
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

    public function toArray()
    {
        return array(
            'id' => $this->getLastName(),
            'firstName' => $this->getFirstName(),
            'middleName' => $this->getMiddleName(),
            'lastName' => $this->getLastName(),
            'title' => $this->getTitle(),
            'position' => $this->getPosition(),
            'birthday' => $this->getBirthday(),
            'created' => $this->getCreated(),
            'changed' => $this->getChanged()
        );
    }

    /**
     * Add faxes
     *
     * @param \Sulu\Bundle\ContactBundle\Entity\Fax $faxes
     * @return Contact
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
     * @var \Doctrine\Common\Collections\Collection
     */
    private $urls;

    /**
     * Add urls
     *
     * @param \Sulu\Bundle\ContactBundle\Entity\Url $urls
     * @return Contact
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
     * Add faxes
     *
     * @param \Sulu\Bundle\ContactBundle\Entity\Fax $faxes
     * @return Contact
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
     * Set formOfAddress
     *
     * @param integer $formOfAddress
     * @return Contact
     */
    public function setFormOfAddress($formOfAddress)
    {
        $this->formOfAddress = $formOfAddress;

        return $this;
    }

    /**
     * Add tags
     *
     * @param \Sulu\Bundle\TagBundle\Entity\Tag $tags
     * @return Contact
     */
    public function addTag(\Sulu\Bundle\TagBundle\Entity\Tag $tags)
    {
        $this->tags[] = $tags;

        return $this;
    }

    /**
     * Get formOfAddress
     *
     * @return integer
     */
    public function getFormOfAddress()
    {
        return $this->formOfAddress;
    }

    /**
     * Set salutation
     *
     * @param string $salutation
     * @return Contact
     */
    public function setSalutation($salutation)
    {
        $this->salutation = $salutation;

        return $this;
    }

    /**
     * Get salutation
     *
     * @return string
     */
    public function getSalutation()
    {
        return $this->salutation;
    }

    /**
     * Set disabled
     *
     * @param integer $disabled
     * @return Contact
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
     * @return Contact
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
     * returns main account
     */
    public function getMainAccount()
    {
        $accountContacts = $this->getAccountContacts();

        /** @var AccountContact $accountContact */
        foreach ($accountContacts as $accountContact) {
            if ($accountContact->getMain()) {
                return $accountContact->getAccount();
            }
        }
        return null;
    }
}