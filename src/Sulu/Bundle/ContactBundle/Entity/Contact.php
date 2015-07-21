<?php

/*
 * This file is part of the Sulu.
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
use JMS\Serializer\Annotation\Groups;
use JMS\Serializer\Annotation\SerializedName;
use JMS\Serializer\Annotation\VirtualProperty;
use Sulu\Bundle\CategoryBundle\Entity\Category;
use Sulu\Bundle\CoreBundle\Entity\ApiEntity;
use Sulu\Bundle\MediaBundle\Entity\Media;
use Sulu\Bundle\TagBundle\Entity\Tag;
use Sulu\Component\Contact\Model\ContactInterface;
use Sulu\Component\Persistence\Model\AuditableInterface;
use Sulu\Component\Security\Authentication\UserInterface;

/**
 * Contact.
 */
class Contact extends ApiEntity implements ContactInterface, AuditableInterface
{
    /**
     * @var int
     */
    protected $id;

    /**
     * @var string
     */
    protected $firstName;

    /**
     * @var string
     */
    protected $middleName;

    /**
     * @var string
     */
    protected $lastName;

    /**
     * @var string
     */
    protected $title;

    /**
     * @Accessor(getter="getPosition")
     *
     * @var string
     */
    protected $position;

    /**
     * @var \DateTime
     */
    protected $birthday;

    /**
     * @var \DateTime
     */
    protected $created;

    /**
     * @var \DateTime
     */
    protected $changed;

    /**
     * @var Collection
     */
    protected $locales;

    /**
     * @var UserInterface
     * @Groups({"fullContact"})
     */
    protected $changer;

    /**
     * @var UserInterface
     * @Groups({"fullContact"})
     */
    protected $creator;

    /**
     * @var Collection
     */
    protected $notes;

    /**
     * @var Collection
     */
    protected $emails;

    /**
     * @var Collection
     */
    protected $phones;

    /**
     * @var Collection
     */
    protected $faxes;

    /**
     * @var int
     */
    protected $formOfAddress = 0;

    /**
     * @var string
     */
    protected $salutation;

    /**
     * @var int
     */
    protected $disabled = 0;

    /**
     * @var Collection
     * @Accessor(getter="getTagNameArray")
     */
    protected $tags;

    /**
     * main account.
     *
     * @Accessor(getter="getMainAccount")
     *
     * @var string
     */
    protected $account;

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
     * @Exclude
     */
    protected $accountContacts;

    /**
     * @var bool
     */
    protected $newsletter;

    /**
     * @var string
     */
    protected $gender;

    /**
     * @var string
     */
    protected $mainEmail;

    /**
     * @var string
     */
    protected $mainPhone;

    /**
     * @var string
     */
    protected $mainFax;

    /**
     * @var string
     */
    protected $mainUrl;

    /**
     * @var Collection
     * @Exclude
     */
    protected $contactAddresses;

    /**
     * @var Collection
     */
    protected $medias;

    /**
     * @var Collection
     */
    protected $categories;

    /**
     * @var Collection
     */
    protected $urls;

    /**
     * @var Collection
     */
    protected $bankAccounts;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->locales = new ArrayCollection();
        $this->notes = new ArrayCollection();
        $this->emails = new ArrayCollection();
        $this->urls = new ArrayCollection();
        $this->addresses = new ArrayCollection();
        $this->phones = new ArrayCollection();
        $this->faxes = new ArrayCollection();
        $this->tags = new ArrayCollection();
        $this->accountContacts = new ArrayCollection();
        $this->contactAddresses = new ArrayCollection();
    }

    /**
     * {@inheritDoc}
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * {@inheritDoc}
     */
    public function setFirstName($firstName)
    {
        $this->firstName = $firstName;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getFirstName()
    {
        return $this->firstName;
    }

    /**
     * {@inheritDoc}
     */
    public function setMiddleName($middleName)
    {
        $this->middleName = $middleName;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getMiddleName()
    {
        return $this->middleName;
    }

    /**
     * {@inheritDoc}
     */
    public function setLastName($lastName)
    {
        $this->lastName = $lastName;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getLastName()
    {
        return $this->lastName;
    }

    /**
     * @VirtualProperty
     * @SerializedName("fullName")
     *
     * @return string
     */
    public function getFullName()
    {
        return $this->firstName . ' ' . $this->lastName;
    }

    /**
     * {@inheritDoc}
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * {@inheritDoc}
     */
    public function setPosition($position)
    {
        $mainAccountContact = $this->getMainAccountContact();
        if ($mainAccountContact) {
            $mainAccountContact->setPosition($position);
            $this->position = $position;
        }

        return $this;
    }

    /**
     * Sets position variable.
     *
     * @param $position
     */
    public function setCurrentPosition($position)
    {
        $this->position = $position;
    }

    /**
     * {@inheritDoc}
     */
    public function getPosition()
    {
        $mainAccountContact = $this->getMainAccountContact();
        if ($mainAccountContact) {
            return $mainAccountContact->getPosition();
        }

        return;
    }

    /**
     * {@inheritDoc}
     */
    public function setBirthday($birthday)
    {
        $this->birthday = $birthday;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getBirthday()
    {
        return $this->birthday;
    }

    /**
     * {@inheritDoc}
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * {@inheritDoc}
     */
    public function getChanged()
    {
        return $this->changed;
    }

    /**
     * {@inheritDoc}
     */
    public function addLocale(ContactLocale $locale)
    {
        $this->locales[] = $locale;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function removeLocale(ContactLocale $locale)
    {
        $this->locales->removeElement($locale);
    }

    /**
     * {@inheritDoc}
     */
    public function getLocales()
    {
        return $this->locales;
    }

    /**
     * Set changer.
     *
     * @param UserInterface $changer
     *
     * @return Contact
     */
    public function setChanger(UserInterface $changer = null)
    {
        $this->changer = $changer;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getChanger()
    {
        return $this->changer;
    }

    /**
     * Set creator.
     *
     * @param UserInterface $creator
     *
     * @return Contact
     */
    public function setCreator(UserInterface $creator = null)
    {
        $this->creator = $creator;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getCreator()
    {
        return $this->creator;
    }

    /**
     * {@inheritDoc}
     */
    public function addNote(Note $note)
    {
        $this->notes[] = $note;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function removeNote(Note $note)
    {
        $this->notes->removeElement($note);
    }

    /**
     * {@inheritDoc}
     */
    public function getNotes()
    {
        return $this->notes;
    }

    /**
     * {@inheritDoc}
     */
    public function addEmail(Email $email)
    {
        $this->emails[] = $email;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function removeEmail(Email $email)
    {
        $this->emails->removeElement($email);
    }

    /**
     * {@inheritDoc}
     */
    public function getEmails()
    {
        return $this->emails;
    }

    /**
     * {@inheritDoc}
     */
    public function addPhone(Phone $phone)
    {
        $this->phones[] = $phone;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function removePhone(Phone $phone)
    {
        $this->phones->removeElement($phone);
    }

    /**
     * {@inheritDoc}
     */
    public function getPhones()
    {
        return $this->phones;
    }

    /**
     * {@inheritDoc}
     */
    public function addFax(Fax $fax)
    {
        $this->faxes[] = $fax;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function removeFax(Fax $fax)
    {
        $this->faxes->removeElement($fax);
    }

    /**
     * {@inheritDoc}
     */
    public function getFaxes()
    {
        return $this->faxes;
    }

    /**
     * {@inheritDoc}
     */
    public function addUrl(Url $url)
    {
        $this->urls[] = $url;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function removeUrl(Url $url)
    {
        $this->urls->removeElement($url);
    }

    /**
     * {@inheritDoc}
     */
    public function getUrls()
    {
        return $this->urls;
    }

    /**
     * {@inheritDoc}
     */
    public function setFormOfAddress($formOfAddress)
    {
        $this->formOfAddress = $formOfAddress;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getFormOfAddress()
    {
        return $this->formOfAddress;
    }

    /**
     * {@inheritDoc}
     */
    public function setSalutation($salutation)
    {
        $this->salutation = $salutation;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getSalutation()
    {
        return $this->salutation;
    }

    /**
     * {@inheritDoc}
     */
    public function setDisabled($disabled)
    {
        $this->disabled = $disabled;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getDisabled()
    {
        return $this->disabled;
    }

    /**
     * {@inheritDoc}
     */
    public function addTag(Tag $tag)
    {
        $this->tags[] = $tag;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function removeTag(Tag $tag)
    {
        $this->tags->removeElement($tag);
    }

    /**
     * {@inheritDoc}
     */
    public function getTags()
    {
        return $this->tags;
    }

    /**
     * {@inheritDoc}
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
     * {@inheritDoc}
     */
    public function addAccountContact(AccountContact $accountContact)
    {
        $this->accountContacts[] = $accountContact;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function removeAccountContact(AccountContact $accountContact)
    {
        $this->accountContacts->removeElement($accountContact);
    }

    /**
     * {@inheritDoc}
     */
    public function getAccountContacts()
    {
        return $this->accountContacts;
    }

    /**
     * {@inheritDoc}
     */
    public function setNewsletter($newsletter)
    {
        $this->newsletter = $newsletter;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getNewsletter()
    {
        return $this->newsletter;
    }

    /**
     * {@inheritDoc}
     */
    public function setGender($gender)
    {
        $this->gender = $gender;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getGender()
    {
        return $this->gender;
    }

    /**
     * {@inheritDoc}
     */
    public function getMainAccount()
    {
        $mainAccountContact = $this->getMainAccountContact();
        if (!is_null($mainAccountContact)) {
            return $mainAccountContact->getAccount();
        }

        return;
    }

    /**
     * Returns main account contact.
     */
    protected function getMainAccountContact()
    {
        $accountContacts = $this->getAccountContacts();

        if (!is_null($accountContacts)) {
            /** @var AccountContact $accountContact */
            foreach ($accountContacts as $accountContact) {
                if ($accountContact->getMain()) {
                    return $accountContact;
                }
            }
        }

        return;
    }

    /**
     * {@inheritDoc}
     */
    public function getAddresses()
    {
        $contactAddresses = $this->getContactAddresses();
        $addresses = [];

        if (!is_null($contactAddresses)) {
            /** @var ContactAddress $contactAddress */
            foreach ($contactAddresses as $contactAddress) {
                $address = $contactAddress->getAddress();
                $address->setPrimaryAddress($contactAddress->getMain());
                $addresses[] = $address;
            }
        }

        return $addresses;
    }

    /**
     * {@inheritDoc}
     */
    public function setMainEmail($mainEmail)
    {
        $this->mainEmail = $mainEmail;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getMainEmail()
    {
        return $this->mainEmail;
    }

    /**
     * {@inheritDoc}
     */
    public function setMainPhone($mainPhone)
    {
        $this->mainPhone = $mainPhone;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getMainPhone()
    {
        return $this->mainPhone;
    }

    /**
     * {@inheritDoc}
     */
    public function setMainFax($mainFax)
    {
        $this->mainFax = $mainFax;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getMainFax()
    {
        return $this->mainFax;
    }

    /**
     * {@inheritDoc}
     */
    public function setMainUrl($mainUrl)
    {
        $this->mainUrl = $mainUrl;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getMainUrl()
    {
        return $this->mainUrl;
    }

    /**
     * {@inheritDoc}
     */
    public function addContactAddress(ContactAddress $contactAddress)
    {
        $this->contactAddresses[] = $contactAddress;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function removeContactAddress(ContactAddress $contactAddress)
    {
        $this->contactAddresses->removeElement($contactAddress);
    }

    /**
     * {@inheritDoc}
     */
    public function getContactAddresses()
    {
        return $this->contactAddresses;
    }

    /**
     * {@inheritDoc}
     */
    public function getMainAddress()
    {
        $contactAddresses = $this->getContactAddresses();

        if (!is_null($contactAddresses)) {
            /** @var ContactAddress $contactAddress */
            foreach ($contactAddresses as $contactAddress) {
                if (!!$contactAddress->getMain()) {
                    return $contactAddress->getAddress();
                }
            }
        }

        return;
    }

    /**
     * {@inheritDoc}
     */
    public function addMedia(Media $media)
    {
        $this->medias[] = $media;
    }

    /**
     * {@inheritDoc}
     */
    public function removeMedia(Media $media)
    {
        $this->medias->removeElement($media);
    }

    /**
     * {@inheritDoc}
     */
    public function getMedias()
    {
        return $this->medias;
    }

    /**
     * {@inheritDoc}
     */
    public function addCategory(Category $category)
    {
        $this->categories[] = $category;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function removeCategory(Category $category)
    {
        $this->categories->removeElement($category);
    }

    /**
     * {@inheritDoc}
     */
    public function getCategories()
    {
        return $this->categories;
    }

    /**
     * {@inheritDoc}
     */
    public function addBankAccount(BankAccount $bankAccount)
    {
        $this->bankAccounts[] = $bankAccount;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function removeBankAccount(BankAccount $bankAccounts)
    {
        $this->bankAccounts->removeElement($bankAccounts);
    }

    /**
     * {@inheritDoc}
     */
    public function getBankAccounts()
    {
        return $this->bankAccounts;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return [
            'id' => $this->getLastName(),
            'firstName' => $this->getFirstName(),
            'middleName' => $this->getMiddleName(),
            'lastName' => $this->getLastName(),
            'title' => $this->getTitle(),
            'position' => $this->getPosition(),
            'birthday' => $this->getBirthday(),
            'created' => $this->getCreated(),
            'changed' => $this->getChanged(),
        ];
    }
}
