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

use Hateoas\Configuration\Annotation\Relation;
use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\Groups;
use JMS\Serializer\Annotation\SerializedName;
use JMS\Serializer\Annotation\VirtualProperty;
use Sulu\Bundle\CategoryBundle\Api\Category;
use Sulu\Bundle\CategoryBundle\Entity\Category as CategoryEntity;
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
use Sulu\Bundle\MediaBundle\Entity\Media as MediaEntity;
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
     * @VirtualProperty
     * @SerializedName("id")
     * @Groups({"fullContact","partialContact","select"})
     */
    public function getId()
    {
        return $this->entity->getId();
    }

    /**
     * Set firstName.
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
     * Get firstName.
     *
     * @return string
     * @VirtualProperty
     * @SerializedName("firstName")
     * @Groups({"fullContact","partialContact"})
     */
    public function getFirstName()
    {
        return $this->entity->getFirstName();
    }

    /**
     * Set middleName.
     *
     * @param string $middleName
     *
     * @return Contact
     * @Groups({"fullContact","partialContact"})
     */
    public function setMiddleName($middleName)
    {
        $this->entity->setMiddleName($middleName);

        return $this;
    }

    /**
     * Get middleName.
     *
     * @return string
     * @VirtualProperty
     * @SerializedName("middleName")
     * @Groups({"fullContact","partialContact"})
     */
    public function getMiddleName()
    {
        return $this->entity->getMiddleName();
    }

    /**
     * Set lastName.
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
     * Get lastName.
     *
     * @return string
     * @VirtualProperty
     * @SerializedName("lastName")
     * @Groups({"fullContact","partialContact"})
     */
    public function getLastName()
    {
        return $this->entity->getLastName();
    }

    /**
     * @VirtualProperty
     * @SerializedName("fullName")
     *
     * @return string
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
     * sets position variable.
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
     * @VirtualProperty
     * @SerializedName("changed")
     * @Groups({"fullContact"})
     */
    public function getChanged()
    {
        return $this->entity->getChanged();
    }

    /**
     * Add locales.
     *
     * @param ContactLocaleEntity $locales
     *
     * @return Contact
     */
    public function addLocale(ContactLocaleEntity $locales)
    {
        $this->entity->addLocale($locales);

        return $this;
    }

    /**
     * Remove locales.
     *
     * @param ContactLocaleEntity $locales
     *
     * @return Contact
     */
    public function removeLocale(ContactLocaleEntity $locales)
    {
        $this->entity->removeLocale($locales);

        return $this;
    }

    /**
     * Get locales.
     *
     * @return array
     * @VirtualProperty
     * @SerializedName("locales")
     * @Groups({"fullContact"})
     */
    public function getLocales()
    {
        $entities = array();
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
     * Add notes.
     *
     * @param NoteEntity $notes
     *
     * @return Contact
     */
    public function addNote(NoteEntity $notes)
    {
        $this->entity->addNote($notes);

        return $this;
    }

    /**
     * Remove notes.
     *
     * @param NoteEntity $notes
     *
     * @return $this
     */
    public function removeNote(NoteEntity $notes)
    {
        $this->entity->removeNote($notes);

        return $this;
    }

    /**
     * Get notes.
     *
     * @return array
     * @VirtualProperty
     * @SerializedName("notes")
     * @Groups({"fullContact"})
     */
    public function getNotes()
    {
        $entities = array();
        if ($this->entity->getNotes()) {
            foreach ($this->entity->getNotes() as $note) {
                $entities[] = $note;
            }
        }

        return $entities;
    }

    /**
     * Add emails.
     *
     * @param EmailEntity $emails
     *
     * @return Contact
     */
    public function addEmail(EmailEntity $emails)
    {
        $this->entity->addEmail($emails);

        return $this;
    }

    /**
     * Remove emails.
     *
     * @param EmailEntity $emails
     */
    public function removeEmail(EmailEntity $emails)
    {
        $this->entity->removeEmail($emails);
    }

    /**
     * Get emails.
     *
     * @return array
     * @VirtualProperty
     * @SerializedName("emails")
     * @Groups({"fullContact"})
     */
    public function getEmails()
    {
        $entities = array();
        if ($this->entity->getEmails()) {
            foreach ($this->entity->getEmails() as $email) {
                $entities[] = $email;
            }
        }

        return $entities;
    }

    /**
     * Add phones.
     *
     * @param PhoneEntity $phones
     *
     * @return Contact
     */
    public function addPhone(PhoneEntity $phones)
    {
        $this->entity->addPhone($phones);

        return $this;
    }

    /**
     * Remove phones.
     *
     * @param PhoneEntity $phones
     */
    public function removePhone(PhoneEntity $phones)
    {
        $this->entity->removePhone($phones);
    }

    /**
     * Get phones.
     *
     * @return array
     * @VirtualProperty
     * @SerializedName("phones")
     * @Groups({"fullContact"})
     */
    public function getPhones()
    {
        $entities = array();
        if ($this->entity->getPhones()) {
            foreach ($this->entity->getPhones() as $phone) {
                $entities[] = $phone;
            }
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
            'changed' => $this->getChanged(),
        );
    }

    /**
     * Add faxes.
     *
     * @param FaxEntity $faxes
     *
     * @return Contact
     */
    public function addFax(FaxEntity $faxes)
    {
        $this->entity->addFax($faxes);

        return $this;
    }

    /**
     * Remove faxes.
     *
     * @param FaxEntity $faxes
     */
    public function removeFax(FaxEntity $faxes)
    {
        $this->entity->removeFax($faxes);
    }

    /**
     * Get faxes.
     *
     * @return array
     * @VirtualProperty
     * @SerializedName("faxes")
     * @Groups({"fullContact"})
     */
    public function getFaxes()
    {
        $entities = array();
        if ($this->entity->getFaxes()) {
            foreach ($this->entity->getFaxes() as $fax) {
                $entities[] = $fax;
            }
        }

        return $entities;
    }

    /**
     * Add urls.
     *
     * @param UrlEntity $urls
     *
     * @return Contact
     */
    public function addUrl(UrlEntity $urls)
    {
        $this->entity->addUrl($urls);

        return $this;
    }

    /**
     * Remove urls.
     *
     * @param UrlEntity $urls
     */
    public function removeUrl(UrlEntity $urls)
    {
        $this->entity->removeUrl($urls);
    }

    /**
     * Get urls.
     *
     * @return array
     * @VirtualProperty
     * @SerializedName("urls")
     * @Groups({"fullContact"})
     */
    public function getUrls()
    {
        $entities = array();
        if ($this->entity->getUrls()) {
            foreach ($this->entity->getUrls() as $entity) {
                $entities[] = $entity;
            }
        }

        return $entities;
    }

    /**
     * Add faxes.
     *
     * @param FaxEntity $faxes
     *
     * @return Contact
     */
    public function addFaxe(FaxEntity $faxes)
    {
        $this->entity->addFax($faxes);

        return $this;
    }

    /**
     * Remove faxes.
     *
     * @param FaxEntity $faxes
     */
    public function removeFaxe(FaxEntity $faxes)
    {
        $this->entity->removeFaxe($faxes);
    }

    /**
     * Set formOfAddress.
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
     * Add tags.
     *
     * @param TagEntity $tags
     *
     * @return Contact
     */
    public function addTag(TagEntity $tags)
    {
        $this->entity->addTag($tags);

        return $this;
    }

    /**
     * Get formOfAddress.
     *
     * @return int
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
     * @VirtualProperty
     * @SerializedName("salutation")
     * @Groups({"fullContact"})
     */
    public function getSalutation()
    {
        return $this->entity->getSalutation();
    }

    /**
     * Set disabled.
     *
     * @param int $disabled
     *
     * @return Contact
     */
    public function setDisabled($disabled)
    {
        $this->entity->setDisabled($disabled);

        return $this;
    }

    /**
     * Get disabled.
     *
     * @return int
     * @VirtualProperty
     * @SerializedName("disabled")
     * @Groups({"fullContact","partialContact"})
     */
    public function getDisabled()
    {
        return $this->entity->getDisabled();
    }

    /**
     * Remove tags.
     *
     * @param TagEntity $tags
     */
    public function removeTag(TagEntity $tags)
    {
        $this->entity->removeTag($tags);
    }

    /**
     * Get tags.
     *
     * @return array
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
     * @VirtualProperty
     * @SerializedName("bankAccounts")
     * @Groups({"fullContact"})
     */
    public function getBankAccounts()
    {
        $bankAccounts = array();
        if ($this->entity->getBankAccounts()) {
            foreach ($this->entity->getBankAccounts() as $bankAccount) {
                /** @var BankAccountEntity $bankAccount */
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
     * @VirtualProperty
     * @SerializedName("gender")
     * @Groups({"fullContact"})
     */
    public function getGender()
    {
        return $this->entity->getGender();
    }

    /**
     * returns main account.
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
     * returns main addresses.
     *
     * @VirtualProperty
     * @SerializedName("addresses")
     * @Groups({"fullContact"})
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
     * Set mainEmail.
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
     * Get mainEmail.
     *
     * @return string
     * @VirtualProperty
     * @SerializedName("mainEmail")
     * @Groups({"fullContact","partialContact"})
     */
    public function getMainEmail()
    {
        return $this->entity->getMainEmail();
    }

    /**
     * Set mainPhone.
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
     * Get mainPhone.
     *
     * @return string
     * @VirtualProperty
     * @SerializedName("mainPhone")
     * @Groups({"fullContact","partialContact"})
     */
    public function getMainPhone()
    {
        return $this->entity->getMainPhone();
    }

    /**
     * Set mainFax.
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
     * Get mainFax.
     *
     * @return string
     * @VirtualProperty
     * @SerializedName("mainFax")
     * @Groups({"fullContact","partialContact"})
     */
    public function getMainFax()
    {
        return $this->entity->getMainFax();
    }

    /**
     * Set mainUrl.
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
     * Get mainUrl.
     *
     * @return string
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
                if (!!$contactAddress->getMain()) {
                    return $contactAddress->getAddress();
                }
            }
        }

        return;
    }

    /**
     * Add medias.
     *
     * @param MediaEntity $medias
     *
     * @return Contact
     */
    public function addMedia(MediaEntity $medias)
    {
        $this->entity->addMedia($medias);
    }

    /** Add categories
     * @param CategoryEntity $categories
     *
     * @return Contact
     */
    public function addCategorie(CategoryEntity $categories)
    {
        $this->entity->addCategorie($categories);

        return $this;
    }

    /**
     * Remove medias.
     *
     * @param MediaEntity $medias
     */
    public function removeMedia(MediaEntity $medias)
    {
        $this->entity->removeMedia($medias);
    }

    /**
     * Get medias.
     *
     * @return Media[]
     * @VirtualProperty
     * @SerializedName("medias")
     * @Groups({"fullContact"})
     */
    public function getMedias()
    {
        $entities = array();
        if ($this->entity->getMedias()) {
            foreach ($this->entity->getMedias() as $media) {
                $entities[] = new Media($media, $this->locale, null);
            }
        }

        return $entities;
    }

    /**
     * Remove categories.
     *
     * @param CategoryEntity $categories
     */
    public function removeCategorie(CategoryEntity $categories)
    {
        $this->entity->removeCategorie($categories);
    }

    /**
     * Get categories.
     *
     * @return Category[]
     * @VirtualProperty
     * @SerializedName("categories")
     * @Groups({"fullContact"})
     */
    public function getCategories()
    {
        $entities = array();
        if ($this->entity->getCategories()) {
            foreach ($this->entity->getCategories() as $category) {
                $entities[] = new Category($category, $this->locale);
            }
        }

        return $entities;
    }
}
