<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContactBundle\Api;

use Hateoas\Configuration\Annotation\Relation;
use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\Groups;
use JMS\Serializer\Annotation\SerializedName;
use JMS\Serializer\Annotation\VirtualProperty;
use Sulu\Bundle\CategoryBundle\Api\Category;
use Sulu\Bundle\CategoryBundle\Entity\CategoryInterface as CategoryEntity;
use Sulu\Bundle\ContactBundle\Entity\BankAccount as BankAccountEntity;
use Sulu\Bundle\ContactBundle\Entity\Contact as ContactEntity;
use Sulu\Bundle\ContactBundle\Entity\ContactAddress as ContactAddressEntity;
use Sulu\Bundle\ContactBundle\Entity\ContactLocale as ContactLocaleEntity;
use Sulu\Bundle\ContactBundle\Entity\Email as EmailEntity;
use Sulu\Bundle\ContactBundle\Entity\Fax as FaxEntity;
use Sulu\Bundle\ContactBundle\Entity\Note as NoteEntity;
use Sulu\Bundle\ContactBundle\Entity\Phone as PhoneEntity;
use Sulu\Bundle\ContactBundle\Entity\Url as UrlEntity;
use Sulu\Bundle\MediaBundle\Api\Media;
use Sulu\Bundle\MediaBundle\Entity\MediaInterface;
use Sulu\Bundle\TagBundle\Entity\Tag as TagEntity;
use Sulu\Component\Rest\ApiWrapper;
use Sulu\Component\Security\Authentication\UserInterface;

/**
 * The Contact class which will be exported to the API.
 *
 * @Relation("self", href="expr('/api/admin/contacts/' ~ object.getId())")
 * @ExclusionPolicy("all")
 */
class Contact extends ApiWrapper
{
    const TYPE = 'contact';

    /**
     * @var Media
     */
    private $avatar = null;

    /**
     * @param ContactEntity $contact
     * @param string $locale The locale of this product
     */
    public function __construct(ContactEntity $contact, $locale)
    {
        $this->entity = $contact;
        $this->locale = $locale;
    }

    /**
     * Get id.
     *
     * @return int
     *
     * @VirtualProperty
     * @SerializedName("id")
     * @Groups({"fullContact","partialContact","select"})
     */
    public function getId()
    {
        return $this->entity->getId();
    }

    /**
     * Set first name.
     *
     * @param string $firstName
     *
     * @return Contact
     */
    public function setFirstName($firstName)
    {
        $this->entity->setFirstName($firstName);

        return $this;
    }

    /**
     * Get first name.
     *
     * @return string
     *
     * @VirtualProperty
     * @SerializedName("firstName")
     * @Groups({"fullContact","partialContact"})
     */
    public function getFirstName()
    {
        return $this->entity->getFirstName();
    }

    /**
     * Set middle name.
     *
     * @param string $middleName
     *
     * @return Contact
     */
    public function setMiddleName($middleName)
    {
        $this->entity->setMiddleName($middleName);

        return $this;
    }

    /**
     * Get middle name.
     *
     * @return string
     *
     * @VirtualProperty
     * @SerializedName("middleName")
     * @Groups({"fullContact","partialContact"})
     */
    public function getMiddleName()
    {
        return $this->entity->getMiddleName();
    }

    /**
     * Set last name.
     *
     * @param string $lastName
     *
     * @return Contact
     */
    public function setLastName($lastName)
    {
        $this->entity->setLastName($lastName);

        return $this;
    }

    /**
     * Get last name.
     *
     * @return string
     *
     * @VirtualProperty
     * @SerializedName("lastName")
     * @Groups({"fullContact","partialContact"})
     */
    public function getLastName()
    {
        return $this->entity->getLastName();
    }

    /**
     * @return string
     *
     * @VirtualProperty
     * @SerializedName("fullName")
     * @Groups({"fullContact","partialContact","select"})
     */
    public function getFullName()
    {
        return $this->entity->getFullName();
    }

    /**
     * Set title.
     *
     * @param object $title
     *
     * @return Contact
     */
    public function setTitle($title)
    {
        $this->entity->setTitle($title);

        return $this;
    }

    /**
     * Get title.
     *
     * @return string
     *
     * @VirtualProperty
     * @SerializedName("title")
     * @Groups({"fullContact", "partialContact"})
     */
    public function getTitle()
    {
        return $this->entity->getTitle();
    }

    /**
     * Set position.
     *
     * @param string $position
     *
     * @return Contact
     */
    public function setPosition($position)
    {
        $this->entity->setPosition($position);

        return $this;
    }

    /**
     * Sets current position.
     *
     * @param $position
     */
    public function setCurrentPosition($position)
    {
        $this->entity->setPosition($position);
    }

    /**
     * Get position.
     *
     * @return string
     *
     * @VirtualProperty
     * @SerializedName("position")
     * @Groups({"fullContact","partialContact"})
     */
    public function getPosition()
    {
        return $this->entity->getPosition();
    }

    /**
     * Set birthday.
     *
     * @param \DateTime $birthday
     *
     * @return Contact
     */
    public function setBirthday($birthday)
    {
        $this->entity->setBirthday($birthday);

        return $this;
    }

    /**
     * Get birthday.
     *
     * @return \DateTime
     *
     * @VirtualProperty
     * @SerializedName("birthday")
     * @Groups({"fullContact"})
     */
    public function getBirthday()
    {
        return $this->entity->getBirthday();
    }

    /**
     * Get created.
     *
     * @return \DateTime
     *
     * @VirtualProperty
     * @SerializedName("created")
     * @Groups({"fullContact"})
     */
    public function getCreated()
    {
        return $this->entity->getCreated();
    }

    /**
     * Get changed.
     *
     * @return \DateTime
     *
     * @VirtualProperty
     * @SerializedName("changed")
     * @Groups({"fullContact"})
     */
    public function getChanged()
    {
        return $this->entity->getChanged();
    }

    /**
     * Add locale.
     *
     * @param ContactLocaleEntity $locale
     *
     * @return Contact
     */
    public function addLocale(ContactLocaleEntity $locale)
    {
        $this->entity->addLocale($locale);

        return $this;
    }

    /**
     * Remove locale.
     *
     * @param ContactLocaleEntity $locale
     *
     * @return Contact
     */
    public function removeLocale(ContactLocaleEntity $locale)
    {
        $this->entity->removeLocale($locale);

        return $this;
    }

    /**
     * Get locales.
     *
     * @return array
     *
     * @VirtualProperty
     * @SerializedName("locales")
     * @Groups({"fullContact"})
     */
    public function getLocales()
    {
        $entities = [];
        if ($this->entity->getLocales()) {
            foreach ($this->entity->getLocales() as $locale) {
                $entities[] = new ContactLocale($locale);
            }
        }

        return $entities;
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
        $this->entity->setChanger($changer);

        return $this;
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
        $this->entity->setCreator($creator);

        return $this;
    }

    /**
     * Add note.
     *
     * @param NoteEntity $note
     *
     * @return Contact
     */
    public function addNote(NoteEntity $note)
    {
        $this->entity->addNote($note);

        return $this;
    }

    /**
     * Remove note.
     *
     * @param NoteEntity $note
     *
     * @return $this
     */
    public function removeNote(NoteEntity $note)
    {
        $this->entity->removeNote($note);

        return $this;
    }

    /**
     * Get notes.
     *
     * @return array
     *
     * @VirtualProperty
     * @SerializedName("notes")
     * @Groups({"fullContact"})
     */
    public function getNotes()
    {
        $entities = [];
        if ($this->entity->getNotes()) {
            foreach ($this->entity->getNotes() as $note) {
                $entities[] = $note;
            }
        }

        return $entities;
    }

    /**
     * Add email.
     *
     * @param EmailEntity $email
     *
     * @return Contact
     */
    public function addEmail(EmailEntity $email)
    {
        $this->entity->addEmail($email);

        return $this;
    }

    /**
     * Remove email.
     *
     * @param EmailEntity $email
     */
    public function removeEmail(EmailEntity $email)
    {
        $this->entity->removeEmail($email);
    }

    /**
     * Get emails.
     *
     * @return array
     *
     * @VirtualProperty
     * @SerializedName("emails")
     * @Groups({"fullContact"})
     */
    public function getEmails()
    {
        $entities = [];
        if ($this->entity->getEmails()) {
            foreach ($this->entity->getEmails() as $email) {
                $entities[] = $email;
            }
        }

        return $entities;
    }

    /**
     * Add phone.
     *
     * @param PhoneEntity $phone
     *
     * @return Contact
     */
    public function addPhone(PhoneEntity $phone)
    {
        $this->entity->addPhone($phone);

        return $this;
    }

    /**
     * Remove phone.
     *
     * @param PhoneEntity $phone
     */
    public function removePhone(PhoneEntity $phone)
    {
        $this->entity->removePhone($phone);
    }

    /**
     * Get phones.
     *
     * @return array
     *
     * @VirtualProperty
     * @SerializedName("phones")
     * @Groups({"fullContact"})
     */
    public function getPhones()
    {
        $entities = [];
        if ($this->entity->getPhones()) {
            foreach ($this->entity->getPhones() as $phone) {
                $entities[] = $phone;
            }
        }

        return $entities;
    }

    /**
     * Add fax.
     *
     * @param FaxEntity $fax
     *
     * @return Contact
     */
    public function addFax(FaxEntity $fax)
    {
        $this->entity->addFax($fax);

        return $this;
    }

    /**
     * Remove fax.
     *
     * @param FaxEntity $fax
     */
    public function removeFax(FaxEntity $fax)
    {
        $this->entity->removeFax($fax);
    }

    /**
     * Get faxes.
     *
     * @return array
     *
     * @VirtualProperty
     * @SerializedName("faxes")
     * @Groups({"fullContact"})
     */
    public function getFaxes()
    {
        $entities = [];
        if ($this->entity->getFaxes()) {
            foreach ($this->entity->getFaxes() as $fax) {
                $entities[] = $fax;
            }
        }

        return $entities;
    }

    /**
     * Add url.
     *
     * @param UrlEntity $url
     *
     * @return Contact
     */
    public function addUrl(UrlEntity $url)
    {
        $this->entity->addUrl($url);

        return $this;
    }

    /**
     * Remove url.
     *
     * @param UrlEntity $url
     */
    public function removeUrl(UrlEntity $url)
    {
        $this->entity->removeUrl($url);
    }

    /**
     * Get urls.
     *
     * @return array
     *
     * @VirtualProperty
     * @SerializedName("urls")
     * @Groups({"fullContact"})
     */
    public function getUrls()
    {
        $entities = [];
        if ($this->entity->getUrls()) {
            foreach ($this->entity->getUrls() as $entity) {
                $entities[] = $entity;
            }
        }

        return $entities;
    }

    /**
     * Set form of address.
     *
     * @param int $formOfAddress
     *
     * @return Contact
     */
    public function setFormOfAddress($formOfAddress)
    {
        $this->entity->setFormOfAddress($formOfAddress);

        return $this;
    }

    /**
     * Get form of address.
     *
     * @return int
     *
     * @VirtualProperty
     * @SerializedName("formOfAddress")
     * @Groups({"fullContact"})
     */
    public function getFormOfAddress()
    {
        return $this->entity->getFormOfAddress();
    }

    /**
     * Set salutation.
     *
     * @param string $salutation
     *
     * @return Contact
     */
    public function setSalutation($salutation)
    {
        $this->entity->setSalutation($salutation);

        return $this;
    }

    /**
     * Get salutation.
     *
     * @return string
     *
     * @VirtualProperty
     * @SerializedName("salutation")
     * @Groups({"fullContact"})
     */
    public function getSalutation()
    {
        return $this->entity->getSalutation();
    }

    /**
     * Sets the avatar (media-api object).
     *
     * @param Media $avatar
     */
    public function setAvatar(Media $avatar)
    {
        $this->avatar = $avatar;
    }

    /**
     * Get the contacts avatar and return the array of different formats.
     *
     * @return Media
     *
     * @VirtualProperty
     * @SerializedName("avatar")
     * @Groups({"fullContact","partialContact"})
     */
    public function getAvatar()
    {
        if ($this->avatar) {
            return [
                'id' => $this->avatar->getId(),
                'url' => $this->avatar->getUrl(),
                'thumbnails' => $this->avatar->getFormats(),
            ];
        }

        return;
    }

    /**
     * Add tag.
     *
     * @param TagEntity $tag
     *
     * @return Contact
     */
    public function addTag(TagEntity $tag)
    {
        $this->entity->addTag($tag);

        return $this;
    }

    /**
     * Remove tag.
     *
     * @param TagEntity $tag
     */
    public function removeTag(TagEntity $tag)
    {
        $this->entity->removeTag($tag);
    }

    /**
     * Get tags.
     *
     * @return array
     *
     * @VirtualProperty
     * @SerializedName("tags")
     * @Groups({"fullContact"})
     */
    public function getTags()
    {
        return $this->entity->getTagNameArray();
    }

    /**
     * Get bank accounts.
     *
     * @return array
     *
     * @VirtualProperty
     * @SerializedName("bankAccounts")
     * @Groups({"fullContact"})
     */
    public function getBankAccounts()
    {
        $bankAccounts = [];
        if ($this->entity->getBankAccounts()) {
            foreach ($this->entity->getBankAccounts() as $bankAccount) {
                /* @var BankAccountEntity $bankAccount */
                $bankAccounts[] = new BankAccount($bankAccount);
            }
        }

        return $bankAccounts;
    }

    /**
     * Set newsletter.
     *
     * @param bool $newsletter
     *
     * @return Contact
     */
    public function setNewsletter($newsletter)
    {
        $this->entity->setNewsletter($newsletter);

        return $this;
    }

    /**
     * Get newsletter.
     *
     * @return bool
     *
     * @VirtualProperty
     * @SerializedName("newsletter")
     * @Groups({"fullContact"})
     */
    public function getNewsletter()
    {
        return $this->entity->getNewsletter();
    }

    /**
     * Set gender.
     *
     * @param string $gender
     *
     * @return Contact
     */
    public function setGender($gender)
    {
        $this->entity->setGender($gender);

        return $this;
    }

    /**
     * Get gender.
     *
     * @return string
     *
     * @VirtualProperty
     * @SerializedName("gender")
     * @Groups({"fullContact"})
     */
    public function getGender()
    {
        return $this->entity->getGender();
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
        $mainAccount = $this->entity->getMainAccount();
        if (!is_null($mainAccount)) {
            return new Account($mainAccount, $this->locale);
        }

        return;
    }

    /**
     * Returns main addresses.
     *
     * @VirtualProperty
     * @SerializedName("addresses")
     * @Groups({"fullContact"})
     */
    public function getAddresses()
    {
        $contactAddresses = $this->entity->getContactAddresses();
        $addresses = [];

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
     * Set main email.
     *
     * @param string $mainEmail
     *
     * @return Contact
     */
    public function setMainEmail($mainEmail)
    {
        $this->entity->setMainEmail($mainEmail);

        return $this;
    }

    /**
     * Get main email.
     *
     * @return string
     *
     * @VirtualProperty
     * @SerializedName("mainEmail")
     * @Groups({"fullContact","partialContact"})
     */
    public function getMainEmail()
    {
        return $this->entity->getMainEmail();
    }

    /**
     * Set main phone.
     *
     * @param string $mainPhone
     *
     * @return Contact
     */
    public function setMainPhone($mainPhone)
    {
        $this->entity->setMainPhone($mainPhone);

        return $this;
    }

    /**
     * Get main phone.
     *
     * @return string
     *
     * @VirtualProperty
     * @SerializedName("mainPhone")
     * @Groups({"fullContact","partialContact"})
     */
    public function getMainPhone()
    {
        return $this->entity->getMainPhone();
    }

    /**
     * Set main fax.
     *
     * @param string $mainFax
     *
     * @return Contact
     */
    public function setMainFax($mainFax)
    {
        $this->entity->setMainFax($mainFax);

        return $this;
    }

    /**
     * Get main fax.
     *
     * @return string
     *
     * @VirtualProperty
     * @SerializedName("mainFax")
     * @Groups({"fullContact","partialContact"})
     */
    public function getMainFax()
    {
        return $this->entity->getMainFax();
    }

    /**
     * Set main url.
     *
     * @param string $mainUrl
     *
     * @return Contact
     */
    public function setMainUrl($mainUrl)
    {
        $this->entity->setMainUrl($mainUrl);

        return $this;
    }

    /**
     * Get main url.
     *
     * @return string
     *
     * @VirtualProperty
     * @SerializedName("mainUrl")
     * @Groups({"fullContact","partialContact"})
     */
    public function getMainUrl()
    {
        return $this->entity->getMainUrl();
    }

    /**
     * Returns the main address.
     *
     * @return mixed
     *
     * @VirtualProperty
     * @SerializedName("mainAddress")
     * @Groups({"fullContact","partialContact"})
     */
    public function getMainAddress()
    {
        $contactAddresses = $this->entity->getContactAddresses();

        if (!is_null($contactAddresses)) {
            /** @var ContactAddressEntity $contactAddress */
            foreach ($contactAddresses as $contactAddress) {
                if ((bool) $contactAddress->getMain()) {
                    return $contactAddress->getAddress();
                }
            }
        }

        return;
    }

    /**
     * Add media.
     *
     * @param MediaInterface $media
     *
     * @return Contact
     */
    public function addMedia(MediaInterface $media)
    {
        $this->entity->addMedia($media);
    }

    /**
     * Remove media.
     *
     * @param MediaInterface $media
     */
    public function removeMedia(MediaInterface $media)
    {
        $this->entity->removeMedia($media);
    }

    /**
     * Get medias.
     *
     * @return Media[]
     *
     * @VirtualProperty
     * @SerializedName("medias")
     * @Groups({"fullContact"})
     */
    public function getMedias()
    {
        $entities = [];
        if ($this->entity->getMedias()) {
            foreach ($this->entity->getMedias() as $media) {
                $entities[] = new Media($media, $this->locale, null);
            }
        }

        return $entities;
    }

    /**
     * Add category.
     *
     * @param CategoryEntity $category
     *
     * @return Contact
     */
    public function addCategory(CategoryEntity $category)
    {
        $this->entity->addCategory($category);

        return $this;
    }

    /**
     * Remove category.
     *
     * @param CategoryEntity $category
     */
    public function removeCategory(CategoryEntity $category)
    {
        $this->entity->removeCategory($category);
    }

    /**
     * Get categories.
     *
     * @return Category[]
     *
     * @VirtualProperty
     * @SerializedName("categories")
     * @Groups({"fullContact"})
     */
    public function getCategories()
    {
        $entities = [];
        if ($this->entity->getCategories()) {
            foreach ($this->entity->getCategories() as $category) {
                $entities[] = new Category($category, $this->locale);
            }
        }

        return $entities;
    }

    /**
     * Get type of api entity.
     *
     * @VirtualProperty
     *
     * @return string
     */
    public function getType()
    {
        return self::TYPE;
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
