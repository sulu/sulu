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

use Sulu\Bundle\CategoryBundle\Entity\Category;
use Sulu\Bundle\MediaBundle\Entity\Media;
use Sulu\Bundle\TagBundle\Entity\Tag;

/**
 * interface for accounts.
 */
interface AccountInterface
{
    /**
     * Set name.
     *
     * @param string $name
     *
     * @return AccountInterface
     */
    public function setName($name);

    /**
     * Get name.
     *
     * @return string
     */
    public function getName();

    /**
     * Set externalId.
     *
     * @param string $externalId
     *
     * @return AccountInterface
     */
    public function setExternalId($externalId);

    /**
     * Get externalId.
     *
     * @return string
     */
    public function getExternalId();

    /**
     * Set number.
     *
     * @param string $number
     *
     * @return AccountInterface
     */
    public function setNumber($number);

    /**
     * Get number.
     *
     * @return string
     */
    public function getNumber();

    /**
     * Set corporation.
     *
     * @param string $corporation
     *
     * @return AccountInterface
     */
    public function setCorporation($corporation);

    /**
     * Get corporation.
     *
     * @return string
     */
    public function getCorporation();

    /**
     * Set uid.
     *
     * @param string $uid
     *
     * @return AccountInterface
     */
    public function setUid($uid);

    /**
     * Get uid.
     *
     * @return string
     */
    public function getUid();

    /**
     * Set registerNumber.
     *
     * @param string $registerNumber
     *
     * @return AccountInterface
     */
    public function setRegisterNumber($registerNumber);

    /**
     * Get registerNumber.
     *
     * @return string
     */
    public function getRegisterNumber();

    /**
     * Set placeOfJurisdiction.
     *
     * @param string $placeOfJurisdiction
     *
     * @return AccountInterface
     */
    public function setPlaceOfJurisdiction($placeOfJurisdiction);

    /**
     * Get placeOfJurisdiction.
     *
     * @return string
     */
    public function getPlaceOfJurisdiction();

    /**
     * Set mainEmail.
     *
     * @param string $mainEmail
     *
     * @return AccountInterface
     */
    public function setMainEmail($mainEmail);

    /**
     * Get mainEmail.
     *
     * @return string
     */
    public function getMainEmail();

    /**
     * Set mainPhone.
     *
     * @param string $mainPhone
     *
     * @return AccountInterface
     */
    public function setMainPhone($mainPhone);

    /**
     * Get mainPhone.
     *
     * @return string
     */
    public function getMainPhone();

    /**
     * Set mainFax.
     *
     * @param string $mainFax
     *
     * @return AccountInterface
     */
    public function setMainFax($mainFax);

    /**
     * Set logo.
     *
     * @param Media $logo
     *
     * @return AccountInterface
     */
    public function setLogo($logo);

    /**
     * Get logo.
     *
     * @return Media
     */
    public function getLogo();

    /**
     * Get mainFax.
     *
     * @return string
     */
    public function getMainFax();

    /**
     * Set mainUrl.
     *
     * @param string $mainUrl
     *
     * @return AccountInterface
     */
    public function setMainUrl($mainUrl);

    /**
     * Get mainUrl.
     *
     * @return string
     */
    public function getMainUrl();

    /**
     * Get id.
     *
     * @return int
     */
    public function getId();

    /**
     * @return \DateTime
     */
    public function getCreated();

    /**
     * @param \DateTime $created
     */
    public function setCreated($created);

    /**
     * @return \DateTime
     */
    public function getChanged();

    /**
     * @param \DateTime $changed
     */
    public function setChanged($changed);

    /**
     * @return mixed
     */
    public function getChanger();

    /**
     * @param mixed $changer
     */
    public function setChanger($changer);

    /**
     * @return mixed
     */
    public function getCreator();

    /**
     * @param mixed $creator
     */
    public function setCreator($creator);

    /**
     * @return Contact
     */
    public function getMainContact();

    /**
     * @param Contact $mainContact
     */
    public function setMainContact($mainContact);

    /**
     * Set lft.
     *
     * @param int $lft
     *
     * @return Account
     */
    public function setLft($lft);

    /**
     * Get lft.
     *
     * @return int
     */
    public function getLft();

    /**
     * Set rgt.
     *
     * @param int $rgt
     *
     * @return Account
     */
    public function setRgt($rgt);

    /**
     * Get rgt.
     *
     * @return int
     */
    public function getRgt();

    /**
     * Set depth.
     *
     * @param int $depth
     *
     * @return Account
     */
    public function setDepth($depth);

    /**
     * Get depth.
     *
     * @return int
     */
    public function getDepth();

    /**
     * Set parent.
     *
     * @param AccountInterface $parent
     *
     * @return Account
     */
    public function setParent(AccountInterface $parent = null);

    /**
     * Get parent.
     *
     * @return AccountInterface
     */
    public function getParent();

    /**
     * Add urls.
     *
     * @param Url $urls
     *
     * @return Account
     */
    public function addUrl(Url $urls);

    /**
     * Remove urls.
     *
     * @param Url $urls
     */
    public function removeUrl(Url $urls);

    /**
     * Get urls.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getUrls();

    /**
     * Add phones.
     *
     * @param Phone $phones
     *
     * @return Account
     */
    public function addPhone(Phone $phones);

    /**
     * Remove phones.
     *
     * @param Phone $phones
     */
    public function removePhone(Phone $phones);

    /**
     * Get phones.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getPhones();

    /**
     * Add emails.
     *
     * @param Email $emails
     *
     * @return Account
     */
    public function addEmail(Email $emails);

    /**
     * Remove emails.
     *
     * @param Email $emails
     */
    public function removeEmail(Email $emails);

    /**
     * Get emails.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getEmails();

    /**
     * Add notes.
     *
     * @param Note $notes
     *
     * @return Account
     */
    public function addNote(Note $notes);

    /**
     * Remove notes.
     *
     * @param Note $notes
     */
    public function removeNote(Note $notes);

    /**
     * Get notes.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getNotes();

    /**
     * Get children.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getChildren();

    /**
     * Add faxes.
     *
     * @param Fax $faxes
     *
     * @return Account
     */
    public function addFax(Fax $faxes);

    /**
     * Remove faxes.
     *
     * @param Fax $faxes
     */
    public function removeFax(Fax $faxes);

    /**
     * Get faxes.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getFaxes();

    /**
     * Add bankAccounts.
     *
     * @param BankAccount $bankAccounts
     *
     * @return Account
     */
    public function addBankAccount(BankAccount $bankAccounts);

    /**
     * Remove bankAccounts.
     *
     * @param BankAccount $bankAccounts
     */
    public function removeBankAccount(BankAccount $bankAccounts);

    /**
     * Get bankAccounts.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getBankAccounts();

    /**
     * Add tags.
     *
     * @param Tag $tags
     *
     * @return Account
     */
    public function addTag(Tag $tags);

    /**
     * Remove tags.
     *
     * @param Tag $tags
     */
    public function removeTag(Tag $tags);

    /**
     * Get tags.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getTags();

    /**
     * Add accountContacts.
     *
     * @param AccountContact $accountContacts
     *
     * @return Account
     */
    public function addAccountContact(AccountContact $accountContacts);

    /**
     * Remove accountContacts.
     *
     * @param AccountContact $accountContacts
     */
    public function removeAccountContact(AccountContact $accountContacts);

    /**
     * Get accountContacts.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getAccountContacts();

    /**
     * Get accountAddresses.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getAccountAddresses();

    /**
     * Returns the main address.
     *
     * @return mixed
     */
    public function getMainAddress();

    /**
     * Get contacts.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getContacts();

    /**
     * Add medias.
     *
     * @param Media $medias
     *
     * @return Account
     */
    public function addMedia(Media $medias);

    /**
     * Remove medias.
     *
     * @param Media $medias
     */
    public function removeMedia(Media $medias);

    /**
     * Get medias.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getMedias();

    /**
     * Add accountAddresses.
     *
     * @param AccountAddress $accountAddresses
     *
     * @return Account
     */
    public function addAccountAddress(AccountAddress $accountAddresses);

    /**
     * Remove accountAddresses.
     *
     * @param AccountAddress $accountAddresses
     */
    public function removeAccountAddress(AccountAddress $accountAddresses);

    /**
     * Add children.
     *
     * @param AccountInterface $child
     *
     * @return Account
     */
    public function addChild(AccountInterface $child);

    /**
     * Remove children.
     *
     * @param AccountInterface $child
     */
    public function removeChild(AccountInterface $child);

    /**
     * Add categories.
     *
     * @param Category $category
     *
     * @return Account
     */
    public function addCategory(Category $category);

    /**
     * Remove categories.
     *
     * @param Category $category
     */
    public function removeCategory(Category $category);

    /**
     * Get categories.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getCategories();
}
