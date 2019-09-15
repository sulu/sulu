<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContactBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use JMS\Serializer\Annotation\Exclude;
use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\Expose;
use JMS\Serializer\Annotation\Groups;
use JMS\Serializer\Annotation\SerializedName;
use JMS\Serializer\Annotation\VirtualProperty;
use Sulu\Bundle\CategoryBundle\Entity\CategoryInterface;
use Sulu\Bundle\CoreBundle\Entity\ApiEntity;
use Sulu\Bundle\MediaBundle\Entity\Media;
use Sulu\Bundle\MediaBundle\Entity\MediaInterface;
use Sulu\Bundle\TagBundle\Tag\TagInterface;
use Sulu\Component\Persistence\Model\AuditableInterface;
use Sulu\Component\Security\Authentication\UserInterface;

/**
 * @ExclusionPolicy("all")
 */
class Contact extends ApiEntity implements ContactInterface, AuditableInterface
{
    const TYPE = 'contact';

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
     * @var ContactTitle|null
     */
    protected $title;

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
     * @var Collection|ContactLocale[]
     */
    protected $locales;

    /**
     * @var UserInterface
     * @Expose
     * @Groups({"fullContact"})
     */
    protected $changer;

    /**
     * @var UserInterface
     * @Expose
     * @Groups({"fullContact"})
     */
    protected $creator;

    /**
     * @var string
     */
    protected $note;

    /**
     * @var Collection|Note[]
     *
     * @deprecated
     */
    protected $notes;

    /**
     * @var Collection|Email[]
     */
    protected $emails;

    /**
     * @var Collection|Phone[]
     */
    protected $phones;

    /**
     * @var Collection|Fax[]
     */
    protected $faxes;

    /**
     * @var Collection|SocialMediaProfile[]
     */
    protected $socialMediaProfiles;

    /**
     * @var int
     */
    protected $formOfAddress = 0;

    /**
     * @var string
     */
    protected $salutation;

    /**
     * @var Collection|TagInterface[]
     */
    protected $tags;

    /**
     * main account.
     *
     * @var string
     */
    protected $account;

    /**
     * main account.
     *
     * @var string
     */
    protected $addresses;

    /**
     * @var Collection|AccountContact[]
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
     * @var Collection|ContactAddress[]
     * @Exclude
     */
    protected $contactAddresses;

    /**
     * @var Collection|MediaInterface[]
     */
    protected $medias;

    /**
     * @var Collection|CategoryInterface[]
     */
    protected $categories;

    /**
     * @var Collection|Url[]
     */
    protected $urls;

    /**
     * @var Collection|BankAccount[]
     */
    protected $bankAccounts;

    /**
     * @var MediaInterface|null
     */
    protected $avatar;

    /**
     * @var string|null
     */
    protected $currentLocale;

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
        $this->socialMediaProfiles = new ArrayCollection();
        $this->tags = new ArrayCollection();
        $this->categories = new ArrayCollection();
        $this->accountContacts = new ArrayCollection();
        $this->contactAddresses = new ArrayCollection();
        $this->medias = new ArrayCollection();
    }

    public function getLocale(): ?string
    {
        return $this->currentLocale;
    }

    public function setLocale(?string $locale): self
    {
        $this->currentLocale = $locale;
        if ($this->avatar instanceof Media) {
            $this->avatar->setLocale($locale);
        }

        return $this;
    }

    /**
     * @VirtualProperty
     * @SerializedName("id")
     * @Groups({"fullContact","partialContact","select","frontend"})
     *
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
     * @VirtualProperty
     * @SerializedName("firstName")
     * @Groups({"fullContact","partialContact"})
     *
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
     * @VirtualProperty
     * @SerializedName("middleName")
     * @Groups({"fullContact","partialContact"})
     *
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
     * @VirtualProperty
     * @SerializedName("avatar")
     * @Groups({"fullContact","partialContact"})
     *
     * @return array|null
     */
    public function getAvatarData()
    {
        if (!$this->avatar) {
            return null;
        }

        return [
            'id' => $this->avatar->getId(),
            'url' => $this->avatar->getUrl(),
            'thumbnails' => $this->avatar->getFormats(),
        ];
    }

    /**
     * @VirtualProperty
     * @SerializedName("lastName")
     * @Groups({"fullContact","partialContact"})
     *
     * {@inheritdoc}
     */
    public function getLastName()
    {
        return $this->lastName;
    }

    /**
     * @VirtualProperty
     * @SerializedName("fullName")
     * @Groups({"fullContact","partialContact","select"})
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
     * @return int
     *
     * @VirtualProperty
     * @SerializedName("title")
     * @Groups({"fullContact", "partialContact"})
     */
    public function getTitleId(): ?int
    {
        if (!$this->title) {
            return null;
        }

        return $this->title->getId();
    }

    /**
     * {@inheritdoc}
     */
    public function setPosition($position)
    {
        $mainAccountContact = $this->getMainAccountContact();
        if ($mainAccountContact) {
            $mainAccountContact->setPosition($position);
        }

        return $this;
    }

    /**
     * Sets current position.
     *
     * @param $position
     */
    public function setCurrentPosition($position)
    {
        $this->setPosition($position);
    }

    /**
     * @VirtualProperty
     * @Groups({"fullContact"})
     *
     * {@inheritdoc}
     */
    public function getPosition()
    {
        $mainAccountContact = $this->getMainAccountContact();
        if ($mainAccountContact) {
            return $mainAccountContact->getPosition();
        }

        return null;
    }

    /**
     * Get position.
     *
     * @return string
     *
     * @VirtualProperty
     * @SerializedName("position")
     * @Groups({"fullContact"})
     */
    public function getPositionId()
    {
        $position = $this->getPosition();

        if (!$position) {
            return null;
        }

        return $position->getId();
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
     * @VirtualProperty
     * @SerializedName("birthday")
     * @Groups({"fullContact"})
     *
     * {@inheritdoc}
     */
    public function getBirthday()
    {
        return $this->birthday;
    }

    /**
     * @VirtualProperty
     * @SerializedName("created")
     * @Groups({"fullContact"})
     *
     * {@inheritdoc}
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * @VirtualProperty
     * @SerializedName("changed")
     * @Groups({"fullContact"})
     *
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
     * @VirtualProperty
     * @SerializedName("locales")
     * @Groups({"fullContact"})
     *
     * {@inheritdoc}
     */
    public function getLocales()
    {
        return $this->locales;
    }

    /**
     * Get locales.
     *
     * @return array
     */
    public function getContactLocales()
    {
        return $this->getLocales();
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

    public function setNote(?string $note): ContactInterface
    {
        $this->note = $note;

        return $this;
    }

    /**
     * @VirtualProperty
     * @SerializedName("note")
     * @Groups({"fullContact"})
     */
    public function getNote(): ?string
    {
        return $this->note;
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
     * @VirtualProperty
     * @SerializedName("notes")
     * @Groups({"fullContact"})
     *
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
     * @VirtualProperty
     * @SerializedName("emails")
     * @Groups({"fullContact", "partialContact"})
     *
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
     * @VirtualProperty
     * @SerializedName("phones")
     * @Groups({"fullContact"})
     *
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
     * @VirtualProperty
     * @SerializedName("faxes")
     * @Groups({"fullContact"})
     *
     * {@inheritdoc}
     */
    public function getFaxes()
    {
        return $this->faxes;
    }

    /**
     * {@inheritdoc}
     */
    public function addSocialMediaProfile(SocialMediaProfile $socialMediaProfile)
    {
        $this->socialMediaProfiles[] = $socialMediaProfile;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function removeSocialMediaProfile(SocialMediaProfile $socialMediaProfile)
    {
        $this->socialMediaProfiles->removeElement($socialMediaProfile);
    }

    /**
     * @VirtualProperty
     * @SerializedName("socialMediaProfiles")
     * @Groups({"fullContact"})
     *
     * {@inheritdoc}
     */
    public function getSocialMediaProfiles()
    {
        return $this->socialMediaProfiles;
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
     * @VirtualProperty
     * @SerializedName("urls")
     * @Groups({"fullContact"})
     *
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
     * @VirtualProperty
     * @SerializedName("formOfAddress")
     * @Groups({"fullContact"})
     *
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
     * @VirtualProperty
     * @SerializedName("salutation")
     * @Groups({"fullContact"})
     *
     * {@inheritdoc}
     */
    public function getSalutation()
    {
        return $this->salutation;
    }

    /**
     * {@inheritdoc}
     */
    public function addTag(TagInterface $tag)
    {
        $this->tags[] = $tag;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function removeTag(TagInterface $tag)
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
     * @VirtualProperty
     * @SerializedName("tags")
     * @Groups({"fullContact"})
     *
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
     * @VirtualProperty
     * @SerializedName("newsletter")
     * @Groups({"fullContact"})
     *
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
     * @VirtualProperty
     * @SerializedName("gender")
     * @Groups({"fullContact"})
     *
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

        return null;
    }

    /**
     * Returns main account.
     *
     * @VirtualProperty
     * @SerializedName("account")
     * @Groups({"fullContact"})
     */
    public function getAccount()
    {
        return $this->getMainAccount();
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

        return null;
    }

    /**
     * @VirtualProperty
     * @SerializedName("addresses")
     * @Groups({"fullContact"})
     *
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
     * @VirtualProperty
     * @SerializedName("mainEmail")
     * @Groups({"fullContact","partialContact"})
     *
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
     * @VirtualProperty
     * @SerializedName("mainPhone")
     * @Groups({"fullContact","partialContact"})
     *
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
     * @VirtualProperty
     * @SerializedName("mainFax")
     * @Groups({"fullContact","partialContact"})
     *
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
     * @VirtualProperty
     * @SerializedName("mainUrl")
     * @Groups({"fullContact","partialContact"})
     *
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
     * @VirtualProperty
     * @SerializedName("mainAddress")
     * @Groups({"fullContact","partialContact"})
     *
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

        return null;
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
     * @VirtualProperty
     * @SerializedName("medias")
     * @Groups({"fullContact"})
     *
     * @return int[]
     */
    public function getMediaIds(): array
    {
        $entities = [];
        if ($this->medias) {
            foreach ($this->medias as $media) {
                $entities[] = $media->getId();
            }
        }

        return $entities;
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
     * Get categories.
     *
     * @return int[]
     *
     * @VirtualProperty
     * @SerializedName("categories")
     * @Groups({"fullContact"})
     */
    public function getCategoryIds(): array
    {
        if (!$this->categories) {
            return [];
        }

        return array_map(function($category) {
            return $category->getId();
        }, $this->categories->toArray());
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
     * @VirtualProperty
     * @SerializedName("bankAccounts")
     * @Groups({"fullContact"})
     *
     * {@inheritdoc}
     */
    public function getBankAccounts()
    {
        return $this->bankAccounts;
    }

    /**
     * @VirtualProperty
     * @SerializedName("contactDetails")
     * @Groups({"fullContact"})
     */
    public function getContactDetails()
    {
        return [
            'emails' => $this->getEmails(),
            'faxes' => $this->getFaxes(),
            'phones' => $this->getPhones(),
            'socialMedia' => $this->getSocialMediaProfiles(),
            'websites' => $this->getUrls(),
        ];
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

    public function getType()
    {
        return self::TYPE;
    }
}
