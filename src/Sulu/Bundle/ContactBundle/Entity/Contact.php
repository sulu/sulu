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

use JMS\Serializer\Annotation\Accessor;
use JMS\Serializer\Annotation\Exclude;
use JMS\Serializer\Annotation\SerializedName;
use JMS\Serializer\Annotation\VirtualProperty;
use Sulu\Bundle\CoreBundle\Entity\ApiEntity;
use Sulu\Component\Persistence\Model\AuditableInterface;

/**
 * Contact.
 */
class Contact extends ApiEntity implements AuditableInterface
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
     * @Accessor(getter="getPosition")
     *
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
     * @var int
     */
    private $id;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $locales;

    /**
     * @var \Sulu\Component\Security\Authentication\UserInterface
     */
    private $changer;

    /**
     * @var \Sulu\Component\Security\Authentication\UserInterface
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
    private $phones;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $faxes;

    /**
     * @var int
     */
    private $formOfAddress = 0;

    /**
     * @var string
     */
    private $salutation;

    /**
     * @var int
     */
    private $disabled = 0;

    /**
     * @var \Doctrine\Common\Collections\Collection
     * @Accessor(getter="getTagNameArray")
     */
    private $tags;

    /**
     * main account.
     *
     * @Accessor(getter="getMainAccount")
     *
     * @var string
     */
    private $account;

    /**
     * main account.
     *
     * @Accessor(getter="getAddresses")
     *
     * @var string
     */
    private $addresses;

    /**
     * @var \Doctrine\Common\Collections\Collection
     * @Exclude
     */
    private $accountContacts;

    /**
     * @var bool
     */
    private $newsletter;

    /**
     * @var string
     */
    private $gender;

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
    private $contactAddresses;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $medias;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $categories;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $urls;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $bankAccounts;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->locales = new \Doctrine\Common\Collections\ArrayCollection();
        $this->notes = new \Doctrine\Common\Collections\ArrayCollection();
        $this->emails = new \Doctrine\Common\Collections\ArrayCollection();
        $this->urls = new \Doctrine\Common\Collections\ArrayCollection();
        $this->addresses = new \Doctrine\Common\Collections\ArrayCollection();
        $this->phones = new \Doctrine\Common\Collections\ArrayCollection();
        $this->faxes = new \Doctrine\Common\Collections\ArrayCollection();
        $this->tags = new \Doctrine\Common\Collections\ArrayCollection();
        $this->accountContacts = new \Doctrine\Common\Collections\ArrayCollection();
        $this->contactAddresses = new \Doctrine\Common\Collections\ArrayCollection();
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
        $this->firstName = $firstName;

        return $this;
    }

    /**
     * Get firstName.
     *
     * @return string
     */
    public function getFirstName()
    {
        return $this->firstName;
    }

    /**
     * Set middleName.
     *
     * @param string $middleName
     *
     * @return Contact
     */
    public function setMiddleName($middleName)
    {
        $this->middleName = $middleName;

        return $this;
    }

    /**
     * Get middleName.
     *
     * @return string
     */
    public function getMiddleName()
    {
        return $this->middleName;
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
        $this->lastName = $lastName;

        return $this;
    }

    /**
     * Get lastName.
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
     *
     * @return string
     */
    public function getFullName()
    {
        return $this->firstName . ' ' . $this->lastName;
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
        $this->title = $title;

        return $this;
    }

    /**
     * Get title.
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
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
        $mainAccountContact = $this->getMainAccountContact();
        if ($mainAccountContact) {
            $mainAccountContact->setPosition($position);
            $this->position = $position;
        }

        return $this;
    }

    /**
     * sets position variable.
     *
     * @param $position
     */
    public function setCurrentPosition($position)
    {
        $this->position = $position;
    }

    /**
     * Get position.
     *
     * @return string
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
     * Set birthday.
     *
     * @param \DateTime $birthday
     *
     * @return Contact
     */
    public function setBirthday($birthday)
    {
        $this->birthday = $birthday;

        return $this;
    }

    /**
     * Get birthday.
     *
     * @return \DateTime
     */
    public function getBirthday()
    {
        return $this->birthday;
    }

    /**
     * Get created.
     *
     * @return \DateTime
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * Get changed.
     *
     * @return \DateTime
     */
    public function getChanged()
    {
        return $this->changed;
    }

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Add locales.
     *
     * @param \Sulu\Bundle\ContactBundle\Entity\ContactLocale $locales
     *
     * @return Contact
     */
    public function addLocale(\Sulu\Bundle\ContactBundle\Entity\ContactLocale $locales)
    {
        $this->locales[] = $locales;

        return $this;
    }

    /**
     * Remove locales.
     *
     * @param \Sulu\Bundle\ContactBundle\Entity\ContactLocale $locales
     */
    public function removeLocale(\Sulu\Bundle\ContactBundle\Entity\ContactLocale $locales)
    {
        $this->locales->removeElement($locales);
    }

    /**
     * Get locales.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getLocales()
    {
        return $this->locales;
    }

    /**
     * Set changer.
     *
     * @param \Sulu\Component\Security\Authentication\UserInterface $changer
     *
     * @return Contact
     */
    public function setChanger(\Sulu\Component\Security\Authentication\UserInterface $changer = null)
    {
        $this->changer = $changer;

        return $this;
    }

    /**
     * Get changer.
     *
     * @return \Sulu\Component\Security\Authentication\UserInterface
     */
    public function getChanger()
    {
        return $this->changer;
    }

    /**
     * Set creator.
     *
     * @param \Sulu\Component\Security\Authentication\UserInterface $creator
     *
     * @return Contact
     */
    public function setCreator(\Sulu\Component\Security\Authentication\UserInterface $creator = null)
    {
        $this->creator = $creator;

        return $this;
    }

    /**
     * Get creator.
     *
     * @return \Sulu\Component\Security\Authentication\UserInterface
     */
    public function getCreator()
    {
        return $this->creator;
    }

    /**
     * Add notes.
     *
     * @param \Sulu\Bundle\ContactBundle\Entity\Note $notes
     *
     * @return Contact
     */
    public function addNote(\Sulu\Bundle\ContactBundle\Entity\Note $notes)
    {
        $this->notes[] = $notes;

        return $this;
    }

    /**
     * Remove notes.
     *
     * @param \Sulu\Bundle\ContactBundle\Entity\Note $notes
     */
    public function removeNote(\Sulu\Bundle\ContactBundle\Entity\Note $notes)
    {
        $this->notes->removeElement($notes);
    }

    /**
     * Get notes.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getNotes()
    {
        return $this->notes;
    }

    /**
     * Add emails.
     *
     * @param \Sulu\Bundle\ContactBundle\Entity\Email $emails
     *
     * @return Contact
     */
    public function addEmail(\Sulu\Bundle\ContactBundle\Entity\Email $emails)
    {
        $this->emails[] = $emails;

        return $this;
    }

    /**
     * Remove emails.
     *
     * @param \Sulu\Bundle\ContactBundle\Entity\Email $emails
     */
    public function removeEmail(\Sulu\Bundle\ContactBundle\Entity\Email $emails)
    {
        $this->emails->removeElement($emails);
    }

    /**
     * Get emails.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getEmails()
    {
        return $this->emails;
    }

    /**
     * Add phones.
     *
     * @param \Sulu\Bundle\ContactBundle\Entity\Phone $phones
     *
     * @return Contact
     */
    public function addPhone(\Sulu\Bundle\ContactBundle\Entity\Phone $phones)
    {
        $this->phones[] = $phones;

        return $this;
    }

    /**
     * Remove phones.
     *
     * @param \Sulu\Bundle\ContactBundle\Entity\Phone $phones
     */
    public function removePhone(\Sulu\Bundle\ContactBundle\Entity\Phone $phones)
    {
        $this->phones->removeElement($phones);
    }

    /**
     * Get phones.
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
            'changed' => $this->getChanged(),
        );
    }

    /**
     * Add faxes.
     *
     * @param \Sulu\Bundle\ContactBundle\Entity\Fax $faxes
     *
     * @return Contact
     */
    public function addFax(\Sulu\Bundle\ContactBundle\Entity\Fax $faxes)
    {
        $this->faxes[] = $faxes;

        return $this;
    }

    /**
     * Remove faxes.
     *
     * @param \Sulu\Bundle\ContactBundle\Entity\Fax $faxes
     */
    public function removeFax(\Sulu\Bundle\ContactBundle\Entity\Fax $faxes)
    {
        $this->faxes->removeElement($faxes);
    }

    /**
     * Get faxes.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getFaxes()
    {
        return $this->faxes;
    }

    /**
     * Add urls.
     *
     * @param \Sulu\Bundle\ContactBundle\Entity\Url $urls
     *
     * @return Contact
     */
    public function addUrl(\Sulu\Bundle\ContactBundle\Entity\Url $urls)
    {
        $this->urls[] = $urls;

        return $this;
    }

    /**
     * Remove urls.
     *
     * @param \Sulu\Bundle\ContactBundle\Entity\Url $urls
     */
    public function removeUrl(\Sulu\Bundle\ContactBundle\Entity\Url $urls)
    {
        $this->urls->removeElement($urls);
    }

    /**
     * Get urls.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getUrls()
    {
        return $this->urls;
    }

    /**
     * Add faxes.
     *
     * @param \Sulu\Bundle\ContactBundle\Entity\Fax $faxes
     *
     * @return Contact
     */
    public function addFaxe(\Sulu\Bundle\ContactBundle\Entity\Fax $faxes)
    {
        $this->faxes[] = $faxes;

        return $this;
    }

    /**
     * Remove faxes.
     *
     * @param \Sulu\Bundle\ContactBundle\Entity\Fax $faxes
     */
    public function removeFaxe(\Sulu\Bundle\ContactBundle\Entity\Fax $faxes)
    {
        $this->faxes->removeElement($faxes);
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
        $this->formOfAddress = $formOfAddress;

        return $this;
    }

    /**
     * Add tags.
     *
     * @param \Sulu\Bundle\TagBundle\Entity\Tag $tags
     *
     * @return Contact
     */
    public function addTag(\Sulu\Bundle\TagBundle\Entity\Tag $tags)
    {
        $this->tags[] = $tags;

        return $this;
    }

    /**
     * Get formOfAddress.
     *
     * @return int
     */
    public function getFormOfAddress()
    {
        return $this->formOfAddress;
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
        $this->salutation = $salutation;

        return $this;
    }

    /**
     * Get salutation.
     *
     * @return string
     */
    public function getSalutation()
    {
        return $this->salutation;
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
        $this->disabled = $disabled;

        return $this;
    }

    /**
     * Get disabled.
     *
     * @return int
     */
    public function getDisabled()
    {
        return $this->disabled;
    }

    /**
     * Remove tags.
     *
     * @param \Sulu\Bundle\TagBundle\Entity\Tag $tags
     */
    public function removeTag(\Sulu\Bundle\TagBundle\Entity\Tag $tags)
    {
        $this->tags->removeElement($tags);
    }

    /**
     * Get tags.
     *
     * @return \Doctrine\Common\Collections\Collection
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
        $tags = array();
        if (!is_null($this->getTags())) {
            foreach ($this->getTags() as $tag) {
                $tags[] = $tag->getName();
            }
        }

        return $tags;
    }

    /**
     * Add accountContacts.
     *
     * @param \Sulu\Bundle\ContactBundle\Entity\AccountContact $accountContacts
     *
     * @return Contact
     */
    public function addAccountContact(\Sulu\Bundle\ContactBundle\Entity\AccountContact $accountContacts)
    {
        $this->accountContacts[] = $accountContacts;

        return $this;
    }

    /**
     * Remove accountContacts.
     *
     * @param \Sulu\Bundle\ContactBundle\Entity\AccountContact $accountContacts
     */
    public function removeAccountContact(\Sulu\Bundle\ContactBundle\Entity\AccountContact $accountContacts)
    {
        $this->accountContacts->removeElement($accountContacts);
    }

    /**
     * Get accountContacts.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getAccountContacts()
    {
        return $this->accountContacts;
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
        $this->newsletter = $newsletter;

        return $this;
    }

    /**
     * Get newsletter.
     *
     * @return bool
     */
    public function getNewsletter()
    {
        return $this->newsletter;
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
        $this->gender = $gender;

        return $this;
    }

    /**
     * Get gender.
     *
     * @return string
     */
    public function getGender()
    {
        return $this->gender;
    }

    /**
     * returns main account.
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
     * returns main account contact.
     */
    private function getMainAccountContact()
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
     * returns main account.
     */
    public function getAddresses()
    {
        $contactAddresses = $this->getContactAddresses();
        $addresses = array();

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
     * Set mainEmail.
     *
     * @param string $mainEmail
     *
     * @return Contact
     */
    public function setMainEmail($mainEmail)
    {
        $this->mainEmail = $mainEmail;

        return $this;
    }

    /**
     * Get mainEmail.
     *
     * @return string
     */
    public function getMainEmail()
    {
        return $this->mainEmail;
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
        $this->mainPhone = $mainPhone;

        return $this;
    }

    /**
     * Get mainPhone.
     *
     * @return string
     */
    public function getMainPhone()
    {
        return $this->mainPhone;
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
        $this->mainFax = $mainFax;

        return $this;
    }

    /**
     * Get mainFax.
     *
     * @return string
     */
    public function getMainFax()
    {
        return $this->mainFax;
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
        $this->mainUrl = $mainUrl;

        return $this;
    }

    /**
     * Get mainUrl.
     *
     * @return string
     */
    public function getMainUrl()
    {
        return $this->mainUrl;
    }

    /**
     * Add contactAddresses.
     *
     * @param \Sulu\Bundle\ContactBundle\Entity\ContactAddress $contactAddresses
     *
     * @return Contact
     */
    public function addContactAddresse(\Sulu\Bundle\ContactBundle\Entity\ContactAddress $contactAddresses)
    {
        $this->contactAddresses[] = $contactAddresses;

        return $this;
    }

    /**
     * Remove contactAddresses.
     *
     * @param \Sulu\Bundle\ContactBundle\Entity\ContactAddress $contactAddresses
     */
    public function removeContactAddresse(\Sulu\Bundle\ContactBundle\Entity\ContactAddress $contactAddresses)
    {
        $this->contactAddresses->removeElement($contactAddresses);
    }

    /**
     * Get contactAddresses.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getContactAddresses()
    {
        return $this->contactAddresses;
    }

    /**
     * Returns the main address.
     *
     * @return mixed
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
     * Add medias.
     *
     * @param \Sulu\Bundle\MediaBundle\Entity\Media $medias
     *
     * @return Contact
     */
    public function addMedia(\Sulu\Bundle\MediaBundle\Entity\Media $medias)
    {
        $this->medias[] = $medias;
    }

    /** Add categories
     * @param \Sulu\Bundle\CategoryBundle\Entity\Category $categories
     *
     * @return Contact
     */
    public function addCategorie(\Sulu\Bundle\CategoryBundle\Entity\Category $categories)
    {
        $this->categories[] = $categories;

        return $this;
    }

    /**
     * Remove medias.
     *
     * @param \Sulu\Bundle\MediaBundle\Entity\Media $medias
     */
    public function removeMedia(\Sulu\Bundle\MediaBundle\Entity\Media $medias)
    {
        $this->medias->removeElement($medias);
    }

    /**
     * Get medias.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getMedias()
    {
        return $this->medias;
    }

    /**
     * Remove categories.
     *
     * @param \Sulu\Bundle\CategoryBundle\Entity\Category $categories
     */
    public function removeCategorie(\Sulu\Bundle\CategoryBundle\Entity\Category $categories)
    {
        $this->categories->removeElement($categories);
    }

    /**
     * Get categories.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getCategories()
    {
        return $this->categories;
    }

    /**
     * Add contactAddresses.
     *
     * @param \Sulu\Bundle\ContactBundle\Entity\ContactAddress $contactAddresses
     *
     * @return Contact
     */
    public function addContactAddress(\Sulu\Bundle\ContactBundle\Entity\ContactAddress $contactAddresses)
    {
        $this->contactAddresses[] = $contactAddresses;

        return $this;
    }

    /**
     * Remove contactAddresses.
     *
     * @param \Sulu\Bundle\ContactBundle\Entity\ContactAddress $contactAddresses
     */
    public function removeContactAddress(\Sulu\Bundle\ContactBundle\Entity\ContactAddress $contactAddresses)
    {
        $this->contactAddresses->removeElement($contactAddresses);
    }

    /**
     * Add categories.
     *
     * @param \Sulu\Bundle\CategoryBundle\Entity\Category $categories
     *
     * @return Contact
     */
    public function addCategory(\Sulu\Bundle\CategoryBundle\Entity\Category $categories)
    {
        $this->categories[] = $categories;

        return $this;
    }

    /**
     * Remove categories.
     *
     * @param \Sulu\Bundle\CategoryBundle\Entity\Category $categories
     */
    public function removeCategory(\Sulu\Bundle\CategoryBundle\Entity\Category $categories)
    {
        $this->categories->removeElement($categories);
    }

    /**
     * Add bankAccounts.
     *
     * @param \Sulu\Bundle\ContactBundle\Entity\BankAccount $bankAccounts
     *
     * @return Contact
     */
    public function addBankAccount(\Sulu\Bundle\ContactBundle\Entity\BankAccount $bankAccounts)
    {
        $this->bankAccounts[] = $bankAccounts;

        return $this;
    }

    /**
     * Remove bankAccounts.
     *
     * @param \Sulu\Bundle\ContactBundle\Entity\BankAccount $bankAccounts
     */
    public function removeBankAccount(\Sulu\Bundle\ContactBundle\Entity\BankAccount $bankAccounts)
    {
        $this->bankAccounts->removeElement($bankAccounts);
    }

    /**
     * Get bankAccounts.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getBankAccounts()
    {
        return $this->bankAccounts;
    }
}
