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
     * @return BaseAccount
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
     * @return BaseAccount
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
     * @return BaseAccount
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
     * @return BaseAccount
     */
    public function setCorporation($corporation);

    /**
     * Get corporation.
     *
     * @return string
     */
    public function getCorporation();

    /**
     * Set disabled.
     *
     * @param int $disabled
     *
     * @return BaseAccount
     */
    public function setDisabled($disabled);

    /**
     * Get disabled.
     *
     * @return int
     */
    public function getDisabled();

    /**
     * Set uid.
     *
     * @param string $uid
     *
     * @return BaseAccount
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
     * @return BaseAccount
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
     * @return BaseAccount
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
     * @return BaseAccount
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
     * @return BaseAccount
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
     * @return BaseAccount
     */
    public function setMainFax($mainFax);

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
     * @return BaseAccount
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
     * @param \Sulu\Bundle\ContactBundle\Entity\Url $urls
     *
     * @return Account
     */
    public function addUrl(\Sulu\Bundle\ContactBundle\Entity\Url $urls);

    /**
     * Remove urls.
     *
     * @param \Sulu\Bundle\ContactBundle\Entity\Url $urls
     */
    public function removeUrl(\Sulu\Bundle\ContactBundle\Entity\Url $urls);

    /**
     * Get urls.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getUrls();

    /**
     * Add phones.
     *
     * @param \Sulu\Bundle\ContactBundle\Entity\Phone $phones
     *
     * @return Account
     */
    public function addPhone(\Sulu\Bundle\ContactBundle\Entity\Phone $phones);

    /**
     * Remove phones.
     *
     * @param \Sulu\Bundle\ContactBundle\Entity\Phone $phones
     */
    public function removePhone(\Sulu\Bundle\ContactBundle\Entity\Phone $phones);

    /**
     * Get phones.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getPhones();

    /**
     * Add emails.
     *
     * @param \Sulu\Bundle\ContactBundle\Entity\Email $emails
     *
     * @return Account
     */
    public function addEmail(\Sulu\Bundle\ContactBundle\Entity\Email $emails);

    /**
     * Remove emails.
     *
     * @param \Sulu\Bundle\ContactBundle\Entity\Email $emails
     */
    public function removeEmail(\Sulu\Bundle\ContactBundle\Entity\Email $emails);

    /**
     * Get emails.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getEmails();

    /**
     * Add notes.
     *
     * @param \Sulu\Bundle\ContactBundle\Entity\Note $notes
     *
     * @return Account
     */
    public function addNote(\Sulu\Bundle\ContactBundle\Entity\Note $notes);

    /**
     * Remove notes.
     *
     * @param \Sulu\Bundle\ContactBundle\Entity\Note $notes
     */
    public function removeNote(\Sulu\Bundle\ContactBundle\Entity\Note $notes);

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
     * @param \Sulu\Bundle\ContactBundle\Entity\Fax $faxes
     *
     * @return Account
     */
    public function addFax(\Sulu\Bundle\ContactBundle\Entity\Fax $faxes);

    /**
     * Remove faxes.
     *
     * @param \Sulu\Bundle\ContactBundle\Entity\Fax $faxes
     */
    public function removeFax(\Sulu\Bundle\ContactBundle\Entity\Fax $faxes);

    /**
     * Get faxes.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getFaxes();

    /**
     * Add bankAccounts.
     *
     * @param \Sulu\Bundle\ContactBundle\Entity\BankAccount $bankAccounts
     *
     * @return Account
     */
    public function addBankAccount(\Sulu\Bundle\ContactBundle\Entity\BankAccount $bankAccounts);

    /**
     * Remove bankAccounts.
     *
     * @param \Sulu\Bundle\ContactBundle\Entity\BankAccount $bankAccounts
     */
    public function removeBankAccount(\Sulu\Bundle\ContactBundle\Entity\BankAccount $bankAccounts);

    /**
     * Get bankAccounts.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getBankAccounts();

    /**
     * Add tags.
     *
     * @param \Sulu\Bundle\TagBundle\Entity\Tag $tags
     *
     * @return Account
     */
    public function addTag(\Sulu\Bundle\TagBundle\Entity\Tag $tags);

    /**
     * Remove tags.
     *
     * @param \Sulu\Bundle\TagBundle\Entity\Tag $tags
     */
    public function removeTag(\Sulu\Bundle\TagBundle\Entity\Tag $tags);

    /**
     * Get tags.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getTags();

    /**
     * Add accountContacts.
     *
     * @param \Sulu\Bundle\ContactBundle\Entity\AccountContact $accountContacts
     *
     * @return Account
     */
    public function addAccountContact(\Sulu\Bundle\ContactBundle\Entity\AccountContact $accountContacts);

    /**
     * Remove accountContacts.
     *
     * @param \Sulu\Bundle\ContactBundle\Entity\AccountContact $accountContacts
     */
    public function removeAccountContact(\Sulu\Bundle\ContactBundle\Entity\AccountContact $accountContacts);

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
     * @param \Sulu\Bundle\MediaBundle\Entity\Media $medias
     *
     * @return Account
     */
    public function addMedia(\Sulu\Bundle\MediaBundle\Entity\Media $medias);

    /**
     * Remove medias.
     *
     * @param \Sulu\Bundle\MediaBundle\Entity\Media $medias
     */
    public function removeMedia(\Sulu\Bundle\MediaBundle\Entity\Media $medias);

    /**
     * Get medias.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getMedias();

    /**
     * Add accountAddresses.
     *
     * @param \Sulu\Bundle\ContactBundle\Entity\AccountAddress $accountAddresses
     *
     * @return Account
     */
    public function addAccountAddress(\Sulu\Bundle\ContactBundle\Entity\AccountAddress $accountAddresses);

    /**
     * Remove accountAddresses.
     *
     * @param \Sulu\Bundle\ContactBundle\Entity\AccountAddress $accountAddresses
     */
    public function removeAccountAddress(\Sulu\Bundle\ContactBundle\Entity\AccountAddress $accountAddresses);

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
    public function removeChild(AccountInterface$child);

    /**
     * Add categories.
     *
     * @param \Sulu\Bundle\CategoryBundle\Entity\Category $category
     *
     * @return Account
     */
    public function addCategory(\Sulu\Bundle\CategoryBundle\Entity\Category $category);

    /**
     * Remove categories.
     *
     * @param \Sulu\Bundle\CategoryBundle\Entity\Category $category
     */
    public function removeCategory(\Sulu\Bundle\CategoryBundle\Entity\Category $category);

    /**
     * Get categories.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getCategories();
}
