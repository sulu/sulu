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
use JMS\Serializer\Annotation\Expose;
use JMS\Serializer\Annotation\Groups;
use JMS\Serializer\Annotation\SerializedName;
use JMS\Serializer\Annotation\VirtualProperty;
use Sulu\Bundle\CategoryBundle\Entity\CategoryInterface;
use Sulu\Bundle\CoreBundle\Entity\ApiEntity;
use Sulu\Bundle\MediaBundle\Entity\MediaInterface;
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
     * @Expose
     * @Groups({"frontend", "partialContact", "fullContact"})
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
     * @Groups({"fullContact"})
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
     * @Groups({"fullContact"})
     */
    protected $notes;

    /**
     * @var Collection
     * @Groups({"fullContact", "partialContact"})
     */
    protected $emails;

    /**
     * @var Collection
     * @Groups({"fullContact"})
     */
    protected $phones;

    /**
     * @var Collection
     * @Groups({"fullContact"})
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
     * @var Collection
     * @Accessor(getter="getTagNameArray")
     * @Groups({"fullContact"})
     */
    protected $tags;

    /**
     * main account.
     *
     * @var string
     * @Accessor(getter="getMainAccount")
     * @Groups({"fullContact"})
     */
    protected $account;

    /**
     * main account.
     *
     * @var string
     * @Accessor(getter="getAddresses")
     * @Groups({"fullContact"})
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
     * @Groups({"fullContact"})
     */
    protected $medias;

    /**
     * @var Collection
     * @Groups({"fullContact"})
     */
    protected $categories;

    /**
     * @var Collection
     * @Groups({"fullContact"})
     */
    protected $urls;

    /**
     * @var Collection
     * @Groups({"fullContact"})
     */
    protected $bankAccounts;

    /**
     * @var MediaInterface
     */
    protected $avatar;

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
        $this->categories = new ArrayCollection();
        $this->accountContacts = new ArrayCollection();
        $this->contactAddresses = new ArrayCollection();
        $this->medias = new ArrayCollection();
    }

    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * {@inheritdoc}
     */
    public function setFirstName($firstName)
    {
        $this->firstName = $firstName;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getFirstName()
    {
        return $this->firstName;
    }

    /**
     * {@inheritdoc}
     */
    public function setMiddleName($middleName)
    {
        $this->middleName = $middleName;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getMiddleName()
    {
        return $this->middleName;
    }

    /**
     * {@inheritdoc}
     */
    public function setLastName($lastName)
    {
        $this->lastName = $lastName;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setAvatar($avatar)
    {
        $this->avatar = $avatar;
    }

    /**
     * {@inheritdoc}
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
     * {@inheritdoc}
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * {@inheritdoc}
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
     * {@inheritdoc}
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
     * {@inheritdoc}
     */
    public function setBirthday($birthday)
    {
        $this->birthday = $birthday;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getBirthday()
    {
        return $this->birthday;
    }

    /**
     * {@inheritdoc}
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * {@inheritdoc}
     */
    public function getChanged()
    {
        return $this->changed;
    }

    /**
     * {@inheritdoc}
     */
    public function addLocale(ContactLocale $locale)
    {
        $this->locales[] = $locale;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function removeLocale(ContactLocale $locale)
    {
        $this->locales->removeElement($locale);
    }

    /**
     * {@inheritdoc}
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
     * {@inheritdoc}
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
     * {@inheritdoc}
     */
    public function getCreator()
    {
        return $this->creator;
    }

    /**
     * {@inheritdoc}
     */
    public function addNote(Note $note)
    {
        $this->notes[] = $note;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function removeNote(Note $note)
    {
        $this->notes->removeElement($note);
    }

    /**
     * {@inheritdoc}
     */
    public function getNotes()
    {
        return $this->notes;
    }

    /**
     * {@inheritdoc}
     */
    public function addEmail(Email $email)
    {
        $this->emails[] = $email;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function removeEmail(Email $email)
    {
        $this->emails->removeElement($email);
    }

    /**
     * {@inheritdoc}
     */
    public function getEmails()
    {
        return $this->emails;
    }

    /**
     * {@inheritdoc}
     */
    public function addPhone(Phone $phone)
    {
        $this->phones[] = $phone;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function removePhone(Phone $phone)
    {
        $this->phones->removeElement($phone);
    }

    /**
     * {@inheritdoc}
     */
    public function getPhones()
    {
        return $this->phones;
    }

    /**
     * {@inheritdoc}
     */
    public function addFax(Fax $fax)
    {
        $this->faxes[] = $fax;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function removeFax(Fax $fax)
    {
        $this->faxes->removeElement($fax);
    }

    /**
     * {@inheritdoc}
     */
    public function getFaxes()
    {
        return $this->faxes;
    }

    /**
     * {@inheritdoc}
     */
    public function addUrl(Url $url)
    {
        $this->urls[] = $url;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function removeUrl(Url $url)
    {
        $this->urls->removeElement($url);
    }

    /**
     * {@inheritdoc}
     */
    public function getUrls()
    {
        return $this->urls;
    }

    /**
     * {@inheritdoc}
     */
    public function setFormOfAddress($formOfAddress)
    {
        $this->formOfAddress = $formOfAddress;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getFormOfAddress()
    {
        return $this->formOfAddress;
    }

    /**
     * {@inheritdoc}
     */
    public function setSalutation($salutation)
    {
        $this->salutation = $salutation;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getSalutation()
    {
        return $this->salutation;
    }

    /**
     * {@inheritdoc}
     */
    public function addTag(Tag $tag)
    {
        $this->tags[] = $tag;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function removeTag(Tag $tag)
    {
        $this->tags->removeElement($tag);
    }

    /**
     * {@inheritdoc}
     */
    public function getTags()
    {
        return $this->tags;
    }

    /**
     * {@inheritdoc}
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
     * {@inheritdoc}
     */
    public function addAccountContact(AccountContact $accountContact)
    {
        $this->accountContacts[] = $accountContact;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function removeAccountContact(AccountContact $accountContact)
    {
        $this->accountContacts->removeElement($accountContact);
    }

    /**
     * {@inheritdoc}
     */
    public function getAccountContacts()
    {
        return $this->accountContacts;
    }

    /**
     * {@inheritdoc}
     */
    public function setNewsletter($newsletter)
    {
        $this->newsletter = $newsletter;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getNewsletter()
    {
        return $this->newsletter;
    }

    /**
     * {@inheritdoc}
     */
    public function setGender($gender)
    {
        $this->gender = $gender;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getGender()
    {
        return $this->gender;
    }

    /**
     * {@inheritdoc}
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
     * {@inheritdoc}
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
     * {@inheritdoc}
     */
    public function setMainEmail($mainEmail)
    {
        $this->mainEmail = $mainEmail;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getMainEmail()
    {
        return $this->mainEmail;
    }

    /**
     * {@inheritdoc}
     */
    public function setMainPhone($mainPhone)
    {
        $this->mainPhone = $mainPhone;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getMainPhone()
    {
        return $this->mainPhone;
    }

    /**
     * {@inheritdoc}
     */
    public function setMainFax($mainFax)
    {
        $this->mainFax = $mainFax;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getMainFax()
    {
        return $this->mainFax;
    }

    /**
     * {@inheritdoc}
     */
    public function setMainUrl($mainUrl)
    {
        $this->mainUrl = $mainUrl;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getMainUrl()
    {
        return $this->mainUrl;
    }

    /**
     * {@inheritdoc}
     */
    public function addContactAddress(ContactAddress $contactAddress)
    {
        $this->contactAddresses[] = $contactAddress;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function removeContactAddress(ContactAddress $contactAddress)
    {
        $this->contactAddresses->removeElement($contactAddress);
    }

    /**
     * {@inheritdoc}
     */
    public function getContactAddresses()
    {
        return $this->contactAddresses;
    }

    /**
     * {@inheritdoc}
     */
    public function getMainAddress()
    {
        $contactAddresses = $this->getContactAddresses();

        if (!is_null($contactAddresses)) {
            /** @var ContactAddress $contactAddress */
            foreach ($contactAddresses as $contactAddress) {
                if ((bool) $contactAddress->getMain()) {
                    return $contactAddress->getAddress();
                }
            }
        }

        return;
    }

    /**
     * {@inheritdoc}
     */
    public function addMedia(MediaInterface $media)
    {
        $this->medias[] = $media;
    }

    /**
     * {@inheritdoc}
     */
    public function removeMedia(MediaInterface $media)
    {
        $this->medias->removeElement($media);
    }

    /**
     * {@inheritdoc}
     */
    public function getMedias()
    {
        return $this->medias;
    }

    /**
     * {@inheritdoc}
     */
    public function getAvatar()
    {
        return $this->avatar;
    }

    /**
     * {@inheritdoc}
     */
    public function addCategory(CategoryInterface $category)
    {
        $this->categories[] = $category;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function removeCategory(CategoryInterface $category)
    {
        $this->categories->removeElement($category);
    }

    /**
     * {@inheritdoc}
     */
    public function getCategories()
    {
        return $this->categories;
    }

    /**
     * {@inheritdoc}
     */
    public function addBankAccount(BankAccount $bankAccount)
    {
        $this->bankAccounts[] = $bankAccount;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function removeBankAccount(BankAccount $bankAccounts)
    {
        $this->bankAccounts->removeElement($bankAccounts);
    }

    /**
     * {@inheritdoc}
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
            'id' => $this->getId(),
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
