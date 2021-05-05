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

use Doctrine\Common\Collections\Collection;
use Sulu\Bundle\CategoryBundle\Entity\CategoryInterface;
use Sulu\Bundle\MediaBundle\Entity\MediaInterface;
use Sulu\Bundle\TagBundle\Tag\TagInterface;

/**
 * Contact interface.
 */
interface ContactInterface
{
    const RESOURCE_KEY = 'contacts';

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
     * @param Position|null $position
     *
     * @return ContactInterface
     */
    public function setPosition($position);

    /**
     * Get position.
     *
     * @return string|null
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
     * @return \DateTime|null
     */
    public function getBirthday();

    /**
     * Add locale.
     *
     * @return ContactInterface
     */
    public function addLocale(ContactLocale $locale);

    /**
     * Remove locale.
     *
     * @return void
     */
    public function removeLocale(ContactLocale $locale);

    /**
     * Get locales.
     *
     * @return Collection<int, ContactLocale>
     */
    public function getLocales();

    /**
     * Add note.
     *
     * @return ContactInterface
     */
    public function addNote(Note $note);

    /**
     * Remove note.
     *
     * @return void
     */
    public function removeNote(Note $note);

    /**
     * Get notes.
     *
     * @return Collection<int, Note>
     */
    public function getNotes();

    /**
     * Add email.
     *
     * @return ContactInterface
     */
    public function addEmail(Email $email);

    /**
     * Remove email.
     *
     * @return void
     */
    public function removeEmail(Email $email);

    /**
     * Get emails.
     *
     * @return Collection<int, Email>
     */
    public function getEmails();

    /**
     * Add phone.
     *
     * @return ContactInterface
     */
    public function addPhone(Phone $phone);

    /**
     * Remove phone.
     *
     * @return void
     */
    public function removePhone(Phone $phone);

    /**
     * Get phones.
     *
     * @return Collection<int, Phone>
     */
    public function getPhones();

    /**
     * Add fax.
     *
     * @return ContactInterface
     */
    public function addFax(Fax $fax);

    /**
     * Remove fax.
     *
     * @return void
     */
    public function removeFax(Fax $fax);

    /**
     * Get faxes.
     *
     * @return Collection<int, Fax>
     */
    public function getFaxes();

    /**
     * Add social media profile.
     *
     * @return ContactInterface
     */
    public function addSocialMediaProfile(SocialMediaProfile $socialMediaProfile);

    /**
     * Remove social media profile.
     *
     * @return void
     */
    public function removeSocialMediaProfile(SocialMediaProfile $socialMediaProfile);

    /**
     * Get social media profiles.
     *
     * @return Collection<int, SocialMediaProfile>
     */
    public function getSocialMediaProfiles();

    /**
     * Add url.
     *
     * @return ContactInterface
     */
    public function addUrl(Url $url);

    /**
     * Remove url.
     *
     * @return void
     */
    public function removeUrl(Url $url);

    /**
     * Get urls.
     *
     * @return Collection<int, Url>
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
     * @return ContactInterface
     */
    public function addTag(TagInterface $tag);

    /**
     * Remove tag.
     *
     * @return void
     */
    public function removeTag(TagInterface $tag);

    /**
     * Get tags.
     *
     * @return Collection<int, TagInterface>
     */
    public function getTags();

    /**
     * Parse tags to array containing tag names.
     *
     * @return string[]
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
     * @return string|null
     */
    public function getSalutation();

    /**
     * Add account contact.
     *
     * @return ContactInterface
     */
    public function addAccountContact(AccountContact $accountContact);

    /**
     * Remove account contact.
     *
     * @return void
     */
    public function removeAccountContact(AccountContact $accountContact);

    /**
     * Get account contacts.
     *
     * @return Collection<int, AccountContact>
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
     * @return string|null
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
     * @param string|null $mainEmail
     *
     * @return ContactInterface
     */
    public function setMainEmail($mainEmail);

    /**
     * Get main email.
     *
     * @return string|null
     */
    public function getMainEmail();

    /**
     * Set main phone.
     *
     * @param string|null $mainPhone
     *
     * @return ContactInterface
     */
    public function setMainPhone($mainPhone);

    /**
     * Get main phone.
     *
     * @return string|null
     */
    public function getMainPhone();

    /**
     * Set main fax.
     *
     * @param string|null $mainFax
     *
     * @return ContactInterface
     */
    public function setMainFax($mainFax);

    /**
     * Get main fax.
     *
     * @return string|null
     */
    public function getMainFax();

    /**
     * Set main url.
     *
     * @param string|null $mainUrl
     *
     * @return ContactInterface
     */
    public function setMainUrl($mainUrl);

    /**
     * Get main url.
     *
     * @return string|null
     */
    public function getMainUrl();

    /**
     * Add contact address.
     *
     * @return ContactInterface
     */
    public function addContactAddress(ContactAddress $contactAddress);

    /**
     * Remove contact address.
     *
     * @return void
     */
    public function removeContactAddress(ContactAddress $contactAddress);

    /**
     * Get contact addresses.
     *
     * @return Collection<int, ContactAddress>
     */
    public function getContactAddresses();

    /**
     * Returns addresses.
     *
     * @return Address[]
     */
    public function getAddresses();

    /**
     * Returns the main address.
     *
     * @return Address|null
     */
    public function getMainAddress();

    /**
     * Add medias.
     *
     * @return ContactInterface
     */
    public function addMedia(MediaInterface $media);

    /**
     * Remove media.
     *
     * @return void
     */
    public function removeMedia(MediaInterface $media);

    /**
     * Get medias.
     *
     * @return Collection<int, MediaInterface>
     */
    public function getMedias();

    /**
     * Get the contacts avatar.
     *
     * @return MediaInterface|null
     */
    public function getAvatar();

    /**
     * Sets the avatar for the contact.
     *
     * @param MediaInterface|null $avatar
     *
     * @return void
     */
    public function setAvatar($avatar);

    /**
     * Add category.
     *
     * @return ContactInterface
     */
    public function addCategory(CategoryInterface $category);

    /**
     * Remove category.
     *
     * @return void
     */
    public function removeCategory(CategoryInterface $category);

    /**
     * Get categories.
     *
     * @return Collection<int, CategoryInterface>
     */
    public function getCategories();

    /**
     * Add bank account.
     *
     * @return ContactInterface
     */
    public function addBankAccount(BankAccount $bankAccount);

    /**
     * Remove bank account.
     *
     * @return void
     */
    public function removeBankAccount(BankAccount $bankAccount);

    /**
     * Get bankAccounts.
     *
     * @return Collection<int, BankAccount>
     */
    public function getBankAccounts();

    public function setNote(?string $note): self;

    public function getNote(): ?string;
}
