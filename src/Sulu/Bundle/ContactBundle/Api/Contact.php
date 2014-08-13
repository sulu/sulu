<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContactBundle\Api;

use Sulu\Bundle\CategoryBundle\Api\Category;
use Sulu\Bundle\CategoryBundle\Entity\Category as CategoryEntity;
use Sulu\Bundle\ContactBundle\Entity\AccountContact as AccountContactEntity;
use Sulu\Bundle\ContactBundle\Entity\Activity as ActivityEntity;
use Sulu\Bundle\ContactBundle\Entity\ContactAddress as ContactAddressEntity;
use Sulu\Bundle\ContactBundle\Entity\ContactLocale as ContactLocaleEntity;
use Sulu\Bundle\ContactBundle\Entity\Email as EmailEntity;
use Sulu\Bundle\ContactBundle\Entity\Fax as FaxEntity;
use Sulu\Bundle\ContactBundle\Entity\Note as NoteEntity;
use Sulu\Bundle\ContactBundle\Entity\Phone as PhoneEntity;
use Sulu\Bundle\ContactBundle\Entity\Url as UrlEntity;
use Sulu\Bundle\MediaBundle\Api\Media;
use Sulu\Bundle\MediaBundle\Entity\Media as MediaEntity;
use Sulu\Bundle\TagBundle\Entity\Tag as TagEntity;
use Sulu\Bundle\TagBundle\Tag\TagManagerInterface;
use Sulu\Component\Rest\ApiWrapper;
use Sulu\Bundle\ContactBundle\Entity\Contact as ContactEntity;
use Sulu\Component\Security\UserInterface;

/**
 * The UrlType class which will be exported to the API
 *
 * @package Sulu\Bundle\ContactBundle\Api
 * @Relation("self", href="expr('/api/admin/contacts/' ~ object.getId())")
 */
class Contact extends ApiWrapper
{

    /**
     * @var TagManagerInterface
     */
    protected $tagManager;

    /**
     * @param ContactEntity $account
     * @param string $locale The locale of this product
     * @param $tagManager
     */
    public function __construct(ContactEntity $account, $locale, $tagManager)
    {
        $this->entity = $account;
        $this->locale = $locale;
        $this->tagManager = $tagManager;
    }

    /**
     * Set firstName
     *
     * @param string $firstName
     * @return Contact
     */
    public function setFirstName($firstName)
    {
        $this->entity->setFirstName($firstName);

        return $this;
    }

    /**
     * Get firstName
     *
     * @return string
     * @VirtualProperty
     * @SerializedName("firstName")
     */
    public function getFirstName()
    {
        return $this->entity->getFirstName();
    }

    /**
     * Set middleName
     *
     * @param string $middleName
     * @return Contact
     */
    public function setMiddleName($middleName)
    {
        $this->entity->setMiddleName($middleName);

        return $this;
    }

    /**
     * Get middleName
     *
     * @return string
     * @VirtualProperty
     * @SerializedName("middleName")
     */
    public function getMiddleName()
    {
        return $this->entity->getMiddleName();
    }

    /**
     * Set lastName
     *
     * @param string $lastName
     * @return Contact
     */
    public function setLastName($lastName)
    {
        $this->entity->setLastName($lastName);

        return $this;
    }

    /**
     * Get lastName
     *
     * @return string
     * @VirtualProperty
     * @SerializedName("lastName")
     */
    public function getLastName()
    {
        return $this->entity->getLastName();
    }

    /**
     * @VirtualProperty
     * @SerializedName("fullName")
     * @return string
     */
    public function getFullName()
    {
        return $this->entity->getFullName();
    }

    /**
     * Set title
     *
     * @param object $title
     * @return Contact
     */
    public function setTitle($title)
    {
        $this->entity->setTitle($title);

        return $this;
    }

    /**
     * Get title
     *
     * @return string
     * @VirtualProperty
     * @SerializedName("title")
     */
    public function getTitle()
    {
        return $this->entity->getTitle();
    }

    /**
     * Set position
     *
     * @param string $position
     * @return Contact
     */
    public function setPosition($position)
    {
        $mainAccountContact = $this->entity->getMainAccountContact();
        if ($mainAccountContact) {
            $mainAccountContact->setPosition($position);
            $this->entity->setPosition($position);
        }

        return $this;
    }

    /**
     * sets position variable
     *
     * @param $position
     */
    public function setCurrentPosition($position)
    {
        $this->entity->setPosition($position);
    }

    /**
     * Get position
     *
     * @return string
     * @VirtualProperty
     * @SerializedName("position")
     */
    public function getPosition()
    {
        $mainAccountContact = $this->entity->getMainAccountContact();
        if ($mainAccountContact) {
            return $mainAccountContact->getPosition();
        }

        return null;
    }

    /**
     * Set birthday
     *
     * @param \DateTime $birthday
     * @return Contact
     */
    public function setBirthday($birthday)
    {
        $this->entity->setBirthday($birthday);

        return $this;
    }

    /**
     * Get birthday
     *
     * @return \DateTime
     * @VirtualProperty
     * @SerializedName("birthday")
     */
    public function getBirthday()
    {
        return $this->entity->getBirthday();
    }

    /**
     * Set created
     *
     * @param \DateTime $created
     * @return Contact
     */
    public function setCreated($created)
    {
        $this->entity->setCreated($created);

        return $this;
    }

    /**
     * Get created
     *
     * @return \DateTime
     * @VirtualProperty
     * @SerializedName("created")
     */
    public function getCreated()
    {
        return $this->getCreated();
    }

    /**
     * Set changed
     *
     * @param \DateTime $changed
     * @return Contact
     */
    public function setChanged($changed)
    {
        $this->entity->setChanged($changed);

        return $this;
    }

    /**
     * Get changed
     *
     * @return \DateTime
     * @VirtualProperty
     * @SerializedName("")
     */
    public function getChanged()
    {
        return $this->getChanged();
    }

    /**
     * Get id
     *
     * @return integer
     * @VirtualProperty
     * @SerializedName("id")
     */
    public function getId()
    {
        return $this->getId();
    }

    /**
     * Add locales
     *
     * @param ContactLocaleEntity $locales
     * @return Contact
     */
    public function addLocale(ContactLocaleEntity $locales)
    {
        $this->entity->addLocale($locales);

        return $this;
    }

    /**
     * Remove locales
     *
     * @param ContactLocaleEntity $locales
     * @return Contact
     */
    public function removeLocale(ContactLocaleEntity $locales)
    {
        $this->entity->removeLocale($locales);

        return $this;
    }

    /**
     * Get locales
     *
     * @return array
     * @VirtualProperty
     * @SerializedName("locales")
     */
    public function getLocales()
    {
        $entities = [];
        foreach ($this->entity->getLocales() as $locale) {
            $entities[] = $locale;
        }

        return $entities;
    }

    /**
     * Add activities
     *
     * @param ActivityEntity $activities
     * @return Contact
     */
    public function addActivitie(ActivityEntity $activities)
    {
        $this->entity->addActivitie($activities);

        return $this;
    }

    /**
     * Remove activities
     *
     * @param ActivityEntity $activities
     */
    public function removeActivitie(ActivityEntity $activities)
    {
        $this->entity->removeActivitie($activities);
    }

    /**
     * Get activities
     *
     * @return array
     * @VirtualProperty
     * @SerializedName("activities")
     */
    public function getActivities()
    {
        $entities = [];
        foreach ($this->entity->getActivities() as $activity) {
            $entities[] = $activity;
        }

        return $entities;
    }

    /**
     * Set changer
     *
     * @param UserInterface $changer
     * @return Contact
     */
    public function setChanger(UserInterface $changer = null)
    {
        $this->entity->setChanger($changer);

        return $this;
    }

    /**
     * Get changer
     *
     * @return UserInterface
     * @VirtualProperty
     * @SerializedName("changer")
     */
    public function getChanger()
    {
        // TODO use api entity
        return $this->entity->getChanger()->getId();
    }

    /**
     * Set creator
     *
     * @param UserInterface $creator
     * @return Contact
     */
    public function setCreator(UserInterface $creator = null)
    {
        $this->entity->setCreator($creator);

        return $this;
    }

    /**
     * Get creator
     *
     * @return UserInterface
     * @VirtualProperty
     * @SerializedName("ceator")
     */
    public function getCreator()
    {
        // TODO use api entity
        return $this->entity->getCreator()->getId();
    }

    /**
     * Add notes
     *
     * @param NoteEntity $notes
     * @return Contact
     */
    public function addNote(NoteEntity $notes)
    {
        $this->entity->addNote($notes);

        return $this;
    }

    /**
     * Remove notes
     *
     * @param NoteEntity $notes
     * @return $this
     */
    public function removeNote(NoteEntity $notes)
    {
        $this->entity->removeNote($notes);

        return $this;
    }

    /**
     * Get notes
     *
     * @return array
     * @VirtualProperty
     * @SerializedName("notes")
     */
    public function getNotes()
    {
        $entities = [];
        foreach ($this->entity->getNotes() as $note) {
            $entities[] = $note;
        }

        return $entities;
    }

    /**
     * Add emails
     *
     * @param EmailEntity $emails
     * @return Contact
     */
    public function addEmail(EmailEntity $emails)
    {
        $this->entity->addEmail($emails);

        return $this;
    }

    /**
     * Remove emails
     *
     * @param EmailEntity $emails
     */
    public function removeEmail(EmailEntity $emails)
    {
        $this->entity->removeEmail($emails);
    }

    /**
     * Get emails
     *
     * @return array
     * @VirtualProperty
     * @SerializedName("emails")
     */
    public function getEmails()
    {
        $entities = [];
        foreach ($this->entity->getEmails() as $email) {
            $entities[] = $email;
        }

        return $entities;
    }

    /**
     * Add phones
     *
     * @param PhoneEntity $phones
     * @return Contact
     */
    public function addPhone(PhoneEntity $phones)
    {
        $this->entity->addPhone($phones);

        return $this;
    }

    /**
     * Remove phones
     *
     * @param PhoneEntity $phones
     */
    public function removePhone(PhoneEntity $phones)
    {
        $this->entity->removePhone($phones);
    }

    /**
     * Get phones
     *
     * @return array
     * @VirtualProperty
     * @SerializedName("phones")
     */
    public function getPhones()
    {
        $entities = [];
        foreach ($this->entity->getPhones() as $phone) {
            $entities[] = $phone;
        }

        return $entities;
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
     * @param FaxEntity $faxes
     * @return Contact
     */
    public function addFax(FaxEntity $faxes)
    {
        $this->entity->addFax($faxes);

        return $this;
    }

    /**
     * Remove faxes
     *
     * @param FaxEntity $faxes
     */
    public function removeFax(FaxEntity $faxes)
    {
        $this->entity->removeFax($faxes);
    }

    /**
     * Get faxes
     *
     * @return array
     * @VirtualProperty
     * @SerializedName("faxes")
     */
    public function getFaxes()
    {
        $entities = [];
        foreach ($this->entity->getFaxes() as $fax) {
            $entities[] = $fax;
        }

        return $entities;
    }

    /**
     * Add urls
     *
     * @param UrlEntity $urls
     * @return Contact
     */
    public function addUrl(UrlEntity $urls)
    {
        $this->entity->addUrl($urls);

        return $this;
    }

    /**
     * Remove urls
     *
     * @param UrlEntity $urls
     */
    public function removeUrl(UrlEntity $urls)
    {
        $this->entity->removeUrl($urls);
    }

    /**
     * Get urls
     *
     * @return array
     * @VirtualProperty
     * @SerializedName("urls")
     */
    public function getUrls()
    {
        $entities = [];
        foreach ($this->entity->getUrls() as $entity) {
            $entities[] = $entity;
        }

        return $entities;
    }

    /**
     * Add faxes
     *
     * @param FaxEntity $faxes
     * @return Contact
     */
    public function addFaxe(FaxEntity $faxes)
    {
        $this->entity->addFax($faxes);

        return $this;
    }

    /**
     * Remove faxes
     *
     * @param FaxEntity $faxes
     */
    public function removeFaxe(FaxEntity $faxes)
    {
        $this->entity->removeFaxe($faxes);
    }

    /**
     * Set formOfAddress
     *
     * @param integer $formOfAddress
     * @return Contact
     */
    public function setFormOfAddress($formOfAddress)
    {
        $this->entity->setFormOfAddress($formOfAddress);

        return $this;
    }

    /**
     * Add tags
     *
     * @param TagEntity $tags
     * @return Contact
     */
    public function addTag(TagEntity $tags)
    {
        $this->entity->addTag($tags);

        return $this;
    }

    /**
     * Get formOfAddress
     *
     * @return integer
     * @VirtualProperty
     * @SerializedName("formOfAddress")
     */
    public function getFormOfAddress()
    {
        return $this->entity->getFormOfAddress();
    }

    /**
     * Set salutation
     *
     * @param string $salutation
     * @return Contact
     */
    public function setSalutation($salutation)
    {
        $this->entity->setSalutation($salutation);

        return $this;
    }

    /**
     * Get salutation
     *
     * @return string
     * @VirtualProperty
     * @SerializedName("salutation")
     */
    public function getSalutation()
    {
        return $this->entity->getSalutation();
    }

    /**
     * Set disabled
     *
     * @param integer $disabled
     * @return Contact
     */
    public function setDisabled($disabled)
    {
        $this->entity->setDisabled($disabled);

        return $this;
    }

    /**
     * Get disabled
     *
     * @return integer
     * @VirtualProperty
     * @SerializedName("disabled")
     */
    public function getDisabled()
    {
        return $this->entity->getDisabled();
    }

    /**
     * Remove tags
     *
     * @param TagEntity $tags
     */
    public function removeTag(TagEntity $tags)
    {
        $this->entity->removeTag($tags);
    }

    /**
     * Get tags
     *
     * @return array
     * @VirtualProperty
     * @SerializedName("tags")
     */
    public function getTags()
    {
        $entities = [];
        foreach ($this->entity->getTags() as $entity) {
            $entities[] = $entity;
        }

        return $entities;
    }

    /**
     * parses tags to array containing tag names
     *
     * @return array
     * @VirtualProperty
     * @SerializedName("tagNameArray")
     */
    public function getTagNameArray()
    {
        $tags = array();
        if (!is_null($this->entity->getTags())) {
            /** @var TagEntity $tag */
            foreach ($this->entity->getTags() as $tag) {
                $tags[] = $tag->getName();
            }
        }

        return $tags;
    }

    /**
     * Add accountContacts
     *
     * @param AccountContactEntity $accountContacts
     * @return Contact
     */
    public function addAccountContact(AccountContactEntity $accountContacts)
    {
        $this->entity->addAccountContact($accountContacts);

        return $this;
    }

    /**
     * Remove accountContacts
     *
     * @param AccountContactEntity $accountContacts
     */
    public function removeAccountContact(AccountContactEntity $accountContacts)
    {
        $this->entity->removeAccountContact($accountContacts);
    }

    /**
     * Get accountContacts
     *
     * @return array
     * @VirtualProperty
     * @SerializedName("accountContacts")
     */
    public function getAccountContacts()
    {
        $entities = [];
        foreach ($this->entity->getAccountContacts() as $entity) {
            $entities[] = $entity;
        }

        return $entities;
    }

    /**
     * Set newsletter
     *
     * @param boolean $newsletter
     * @return Contact
     */
    public function setNewsletter($newsletter)
    {
        $this->entity->setNewsletter($newsletter);

        return $this;
    }

    /**
     * Get newsletter
     *
     * @return boolean
     * @VirtualProperty
     * @SerializedName("newsletter")
     */
    public function getNewsletter()
    {
        return $this->entity->getNewsletter();
    }

    /**
     * Set gender
     *
     * @param string $gender
     * @return Contact
     */
    public function setGender($gender)
    {
        $this->entity->setGender($gender);

        return $this;
    }

    /**
     * Get gender
     *
     * @return string
     * @VirtualProperty
     * @SerializedName("gender")
     */
    public function getGender()
    {
        return $this->entity->getGender();
    }

    /**
     * returns main account
     *
     * @VirtualProperty
     * @SerializedName("mainAccount")
     */
    public function getMainAccount()
    {
        $mainAccountContact = $this->entity->getMainAccountContact();
        if (!is_null($mainAccountContact)) {
            return new Account($mainAccountContact->getAccount(), $this->locale, $this->tagManager);
        }

        return null;
    }

    /**
     * returns main account
     *
     * @VirtualProperty
     * @SerializedName("addresses")
     */
    public function getAddresses()
    {
        $contactAddresses = $this->entity->getContactAddresses();
        $addresses = array();

        if (!is_null($contactAddresses)) {
            /** @var ContactAddressEntity $contactAddress */
            foreach ($contactAddresses as $contactAddress) {
                $address = $contactAddress->getAddress();
                $address->setPrimaryAddress($contactAddress->getMain());
                $addresses[] = $address;
            }
        }

        return $addresses;
    }

    /**
     * Set mainEmail
     *
     * @param string $mainEmail
     * @return Contact
     */
    public function setMainEmail($mainEmail)
    {
        $this->entity->setMainEmail($mainEmail);

        return $this;
    }

    /**
     * Get mainEmail
     *
     * @return string
     * @VirtualProperty
     * @SerializedName("mainEmail")
     */
    public function getMainEmail()
    {
        return $this->entity->getMainEmail();
    }

    /**
     * Set mainPhone
     *
     * @param string $mainPhone
     * @return Contact
     */
    public function setMainPhone($mainPhone)
    {
        $this->entity->setMainPhone($mainPhone);

        return $this;
    }

    /**
     * Get mainPhone
     *
     * @return string
     * @VirtualProperty
     * @SerializedName("mainPhone")
     */
    public function getMainPhone()
    {
        return $this->entity->mainPhone;
    }

    /**
     * Set mainFax
     *
     * @param string $mainFax
     * @return Contact
     */
    public function setMainFax($mainFax)
    {
        $this->entity->setMainFax($mainFax);

        return $this;
    }

    /**
     * Get mainFax
     *
     * @return string
     * @VirtualProperty
     * @SerializedName("mainFax")
     */
    public function getMainFax()
    {
        return $this->entity->getMainFax();
    }

    /**
     * Set mainUrl
     *
     * @param string $mainUrl
     * @return Contact
     */
    public function setMainUrl($mainUrl)
    {
        $this->entity->setMainUrl($mainUrl);

        return $this;
    }

    /**
     * Get mainUrl
     *
     * @return string
     * @VirtualProperty
     * @SerializedName("mainUrl")
     */
    public function getMainUrl()
    {
        return $this->entity->getMainUrl();
    }

    /**
     * Add contactAddresses
     *
     * @param ContactAddressEntity $contactAddresses
     * @return Contact
     */
    public function addContactAddresse(ContactAddressEntity $contactAddresses)
    {
        $this->entity->addContactAddresse($contactAddresses);

        return $this;
    }

    /**
     * Remove contactAddresses
     *
     * @param ContactAddressEntity $contactAddresses
     */
    public function removeContactAddresse(ContactAddressEntity $contactAddresses)
    {
        $this->entity->removeContactAddresse($contactAddresses);
    }

    /**
     * Get contactAddresses
     *
     * @return array
     * @VirtualProperty
     * @SerializedName("contactAddresses")
     */
    public function getContactAddresses()
    {
        $entities = [];
        foreach ($this->entity->getContactAddresses() as $entity) {
            $entities[] = $entity;
        }

        return $entities;
    }

    /**
     * Add assignedActivities
     *
     * @param ActivityEntity $assignedActivities
     * @return Contact
     */
    public function addAssignedActivitie(ActivityEntity $assignedActivities)
    {
        $this->entity->addAssignedActivitie($assignedActivities);

        return $this;
    }

    /**
     * Remove assignedActivities
     *
     * @param ActivityEntity $assignedActivities
     */
    public function removeAssignedActivitie(ActivityEntity $assignedActivities)
    {
        $this->entity->removeAssignedActivitie($assignedActivities);
    }

    /**
     * Get assignedActivities
     *
     * @return array
     * @VirtualProperty
     * @SerializedName("assignedActivities")
     */
    public function getAssignedActivities()
    {
        $entities = [];
        foreach ($this->entity->getAssignedActivities() as $entity) {
            $entities[] = $entity;
        }

        return $entities;
    }

    /**
     * Returns the main address
     *
     * @return mixed
     * @VirtualProperty
     * @SerializedName("mainAddress")
     */
    public function getMainAddress()
    {
        $contactAddresses = $this->entity->getContactAddresses();

        if (!is_null($contactAddresses)) {
            /** @var ContactAddressEntity $contactAddress */
            foreach ($contactAddresses as $contactAddress) {
                if (!!$contactAddress->getMain()) {
                    return $contactAddress->getAddress();
                }
            }
        }

        return null;
    }

    /**
     * Add medias
     *
     * @param MediaEntity $medias
     * @return Contact
     */
    public function addMedia(MediaEntity $medias)
    {
        $this->entity->addMedia($medias);
    }

    /** Add categories
     *
     * @param CategoryEntity $categories
     * @return Contact
     */
    public function addCategorie(CategoryEntity $categories)
    {
        $this->entity->addCategorie($categories);

        return $this;
    }

    /**
     * Remove medias
     *
     * @param MediaEntity $medias
     */
    public function removeMedia(MediaEntity $medias)
    {
        $this->entity->removeMedia($medias);
    }

    /**
     * Get medias
     *
     * @return Media[]
     * @VirtualProperty
     * @SerializedName("medias")
     */
    public function getMedias()
    {
        $entities = [];
        foreach ($this->entity->getMedias() as $media) {
            $entities[] = new Media($media, $this->locale, $this->tagManager);
        }

        return $entities;
    }

    /**
     * Remove categories
     *
     * @param CategoryEntity $categories
     */
    public function removeCategorie(CategoryEntity $categories)
    {
        $this->entity->removeCategorie($categories);
    }

    /**
     * Get categories
     *
     * @return Category[]
     * @VirtualProperty
     * @SerializedName("categories")
     */
    public function getCategories()
    {
        $entities = [];
        foreach ($this->entity->getCategories() as $category) {
            $entities[] = new Category($category, $this->locale);
        }

        return $entities;
    }
}
