<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Contact\Model;

use Doctrine\Common\Collections\Collection;
use Sulu\Bundle\CategoryBundle\Entity\CategoryInterface;
use Sulu\Bundle\ContactBundle\Entity\AccountContact;
use Sulu\Bundle\ContactBundle\Entity\AccountInterface;
use Sulu\Bundle\ContactBundle\Entity\BankAccount;
use Sulu\Bundle\ContactBundle\Entity\ContactAddress;
use Sulu\Bundle\ContactBundle\Entity\ContactLocale;
use Sulu\Bundle\ContactBundle\Entity\Email;
use Sulu\Bundle\ContactBundle\Entity\Fax;
use Sulu\Bundle\ContactBundle\Entity\Note;
use Sulu\Bundle\ContactBundle\Entity\Phone;
use Sulu\Bundle\ContactBundle\Entity\Url;
use Sulu\Bundle\MediaBundle\Entity\Media;
use Sulu\Bundle\MediaBundle\Entity\MediaInterface;
use Sulu\Bundle\TagBundle\Entity\Tag;

/**
 * Contact interface.
 */
interface ContactInterface
{
    /**
     * Get id.
     *
     * @return int
     */
    public function getId();

    /**
     * Set first name.
     *
     * @param string $firstName
     *
     * @return ContactInterface
     */
    public function setFirstName($firstName);

    /**
     * Get first name.
     *
     * @return string
     */
    public function getFirstName();

    /**
     * Set middle name.
     *
     * @param string $middleName
     *
     * @return ContactInterface
     */
    public function setMiddleName($middleName);

    /**
     * Get middle name.
     *
     * @return string
     */
    public function getMiddleName();

    /**
     * Set last name.
     *
     * @param string $lastName
     *
     * @return ContactInterface
     */
    public function setLastName($lastName);

    /**
     * Get last name.
     *
     * @return string
     */
    public function getLastName();

    /**
     * Set title.
     *
     * @param object $title
     *
     * @return ContactInterface
     */
    public function setTitle($title);

    /**
     * Get title.
     *
     * @return string
     */
    public function getTitle();

    /**
     * Set position.
     *
     * @param string $position
     *
     * @return ContactInterface
     */
    public function setPosition($position);

    /**
     * Get position.
     *
     * @return string
     */
    public function getPosition();

    /**
     * Set birthday.
     *
     * @param \DateTime $birthday
     *
     * @return ContactInterface
     */
    public function setBirthday($birthday);

    /**
     * Get birthday.
     *
     * @return \DateTime
     */
    public function getBirthday();

    /**
     * Add locale.
     *
     * @param ContactLocale $locale
     *
     * @return ContactInterface
     */
    public function addLocale(ContactLocale $locale);

    /**
     * Remove locale.
     *
     * @param ContactLocale $locale
     */
    public function removeLocale(ContactLocale $locale);

    /**
     * Get locales.
     *
     * @return Collection
     */
    public function getLocales();

    /**
     * Add note.
     *
     * @param Note $note
     *
     * @return ContactInterface
     */
    public function addNote(Note $note);

    /**
     * Remove note.
     *
     * @param Note $note
     */
    public function removeNote(Note $note);

    /**
     * Get notes.
     *
     * @return Collection
     */
    public function getNotes();

    /**
     * Add email.
     *
     * @param Email $email
     *
     * @return ContactInterface
     */
    public function addEmail(Email $email);

    /**
     * Remove email.
     *
     * @param Email $email
     */
    public function removeEmail(Email $email);

    /**
     * Get emails.
     *
     * @return Collection
     */
    public function getEmails();

    /**
     * Add phone.
     *
     * @param Phone $phone
     *
     * @return ContactInterface
     */
    public function addPhone(Phone $phone);

    /**
     * Remove phone.
     *
     * @param Phone $phone
     */
    public function removePhone(Phone $phone);

    /**
     * Get phones.
     *
     * @return Collection
     */
    public function getPhones();

    /**
     * Add fax.
     *
     * @param Fax $fax
     *
     * @return ContactInterface
     */
    public function addFax(Fax $fax);

    /**
     * Remove fax.
     *
     * @param Fax $fax
     */
    public function removeFax(Fax $fax);

    /**
     * Get faxes.
     *
     * @return Collection
     */
    public function getFaxes();

    /**
     * Add url.
     *
     * @param Url $url
     *
     * @return ContactInterface
     */
    public function addUrl(Url $url);

    /**
     * Remove url.
     *
     * @param Url $url
     */
    public function removeUrl(Url $url);

    /**
     * Get urls.
     *
     * @return Collection
     */
    public function getUrls();

    /**
     * Set form of address.
     *
     * @param int $formOfAddress
     *
     * @return ContactInterface
     */
    public function setFormOfAddress($formOfAddress);

    /**
     * Get form of address.
     *
     * @return int
     */
    public function getFormOfAddress();

    /**
     * Add tag.
     *
     * @param Tag $tag
     *
     * @return ContactInterface
     */
    public function addTag(Tag $tag);

    /**
     * Remove tag.
     *
     * @param Tag $tag
     */
    public function removeTag(Tag $tag);

    /**
     * Get tags.
     *
     * @return Collection
     */
    public function getTags();

    /**
     * Parse tags to array containing tag names.
     *
     * @return array
     */
    public function getTagNameArray();

    /**
     * Set salutation.
     *
     * @param string $salutation
     *
     * @return ContactInterface
     */
    public function setSalutation($salutation);

    /**
     * Get salutation.
     *
     * @return string
     */
    public function getSalutation();

    /**
     * Add account contact.
     *
     * @param AccountContact $accountContact
     *
     * @return ContactInterface
     */
    public function addAccountContact(AccountContact $accountContact);

    /**
     * Remove account contact.
     *
     * @param AccountContact $accountContact
     */
    public function removeAccountContact(AccountContact $accountContact);

    /**
     * Get account contacts.
     *
     * @return Collection
     */
    public function getAccountContacts();

    /**
     * Set newsletter.
     *
     * @param bool $newsletter
     *
     * @return ContactInterface
     */
    public function setNewsletter($newsletter);

    /**
     * Get newsletter.
     *
     * @return bool
     */
    public function getNewsletter();

    /**
     * Set gender.
     *
     * @param string $gender
     *
     * @return ContactInterface
     */
    public function setGender($gender);

    /**
     * Get gender.
     *
     * @return string
     */
    public function getGender();

    /**
     * Returns main account.
     *
     * @return AccountInterface|null
     */
    public function getMainAccount();

    /**
     * Set main email.
     *
     * @param string $mainEmail
     *
     * @return ContactInterface
     */
    public function setMainEmail($mainEmail);

    /**
     * Get main email.
     *
     * @return string
     */
    public function getMainEmail();

    /**
     * Set main phone.
     *
     * @param string $mainPhone
     *
     * @return ContactInterface
     */
    public function setMainPhone($mainPhone);

    /**
     * Get main phone.
     *
     * @return string
     */
    public function getMainPhone();

    /**
     * Set main fax.
     *
     * @param string $mainFax
     *
     * @return ContactInterface
     */
    public function setMainFax($mainFax);

    /**
     * Get main fax.
     *
     * @return string
     */
    public function getMainFax();

    /**
     * Set main url.
     *
     * @param string $mainUrl
     *
     * @return ContactInterface
     */
    public function setMainUrl($mainUrl);

    /**
     * Get main url.
     *
     * @return string
     */
    public function getMainUrl();

    /**
     * Add contact address.
     *
     * @param ContactAddress $contactAddress
     *
     * @return ContactInterface
     */
    public function addContactAddress(ContactAddress $contactAddress);

    /**
     * Remove contact address.
     *
     * @param ContactAddress $contactAddress
     */
    public function removeContactAddress(ContactAddress $contactAddress);

    /**
     * Get contact addresses.
     *
     * @return Collection
     */
    public function getContactAddresses();

    /**
     * Returns addresses.
     */
    public function getAddresses();

    /**
     * Returns the main address.
     *
     * @return mixed
     */
    public function getMainAddress();

    /**
     * Add medias.
     *
     * @param MediaInterface $media
     *
     * @return ContactInterface
     */
    public function addMedia(MediaInterface $media);

    /**
     * Remove media.
     *
     * @param MediaInterface $media
     */
    public function removeMedia(MediaInterface $media);

    /**
     * Get medias.
     *
     * @return Collection
     */
    public function getMedias();

    /**
     * Get the contacts avatar.
     *
     * @return Media
     */
    public function getAvatar();

    /**
     * Sets the avatar for the contact.
     *
     * @param Media $avatar
     */
    public function setAvatar($avatar);

    /**
     * Add category.
     *
     * @param CategoryInterface $category
     *
     * @return ContactInterface
     */
    public function addCategory(CategoryInterface $category);

    /**
     * Remove category.
     *
     * @param CategoryInterface $category
     */
    public function removeCategory(CategoryInterface $category);

    /**
     * Get categories.
     *
     * @return Collection
     */
    public function getCategories();

    /**
     * Add bank account.
     *
     * @param BankAccount $bankAccount
     *
     * @return ContactInterface
     */
    public function addBankAccount(BankAccount $bankAccount);

    /**
     * Remove bank account.
     *
     * @param BankAccount $bankAccount
     */
    public function removeBankAccount(BankAccount $bankAccount);

    /**
     * Get bankAccounts.
     *
     * @return Collection
     */
    public function getBankAccounts();
}
