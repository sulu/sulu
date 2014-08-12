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

use Sulu\Bundle\ContactBundle\Entity\Account as AccountEntity;
use Doctrine\Entity;
use Sulu\Bundle\ContactBundle\Entity\AccountAddress;
use Sulu\Bundle\ContactBundle\Entity\AccountCategory;
use Sulu\Bundle\ContactBundle\Entity\AccountContact;
use Sulu\Bundle\ContactBundle\Entity\Activity;
use Sulu\Bundle\ContactBundle\Entity\BankAccount;
use Sulu\Bundle\ContactBundle\Entity\Contact;
use Sulu\Bundle\ContactBundle\Entity\ContactAddress;
use Sulu\Bundle\ContactBundle\Entity\Email;
use Sulu\Bundle\ContactBundle\Entity\Fax;
use Sulu\Bundle\ContactBundle\Entity\Note;
use Sulu\Bundle\ContactBundle\Entity\Phone;
use Sulu\Bundle\ContactBundle\Entity\TermsOfDelivery;
use Sulu\Bundle\ContactBundle\Entity\TermsOfPayment;
use Sulu\Bundle\ContactBundle\Entity\Url;
use Sulu\Bundle\MediaBundle\Entity\Media;
use Sulu\Bundle\TagBundle\Entity\Tag;
use Sulu\Component\Rest\ApiWrapper;
use Sulu\Component\Security\UserInterface;

/**
 * The UrlType class which will be exported to the API
 *
 * @package Sulu\Bundle\ContactBundle\Api
 * @Relation("self", href="expr('/api/admin/contacts/' ~ object.getId())")
 */
class UrlType extends ApiWrapper
{
    /**
     * @param AccountEntity $account
     * @param string $locale The locale of this product
     */
    public function __construct(AccountEntity $account, $locale)
    {
        $this->entity = $account;
        $this->locale = $locale;
    }

    /**
     * Returns the id of the product
     *
     * @return int
     * @VirtualProperty
     * @SerializedName("id")
     */
    public function getId()
    {
        return $this->entity->getId();
    }

    /**
     * Get lft
     *
     * @return integer
     */
    public function getLft()
    {
        return $this->entity->getLft();
    }

    /**
     * Set lft
     *
     * @param integer $lft
     */
    public function setLft($lft)
    {
        $this->entity->setLft($lft);
    }

    /**
     * Set rgt
     *
     * @param integer $rgt
     */
    public function setRgt($rgt)
    {
        $this->entity->setRgt($rgt);
    }

    /**
     * Get rgt
     *
     * @return integer
     */
    public function getRgt()
    {
        return $this->entity->getRgt();
    }

    /**
     * Set depth
     *
     * @param integer $depth
     */
    public function setDepth($depth)
    {
        $this->entity->setDepth($depth);
    }

    /**
     * Get depth
     *
     * @return integer
     */
    public function getDepth()
    {
        return $this->entity->getDepth();
    }

    /**
     * Set name
     *
     * @param string $name
     */
    public function setName($name)
    {
        $this->entity->setName($name);
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->entity->getName();
    }

    /**
     * Set created
     *
     * @param \DateTime $created
     */
    public function setCreated($created)
    {
        $this->entity->setCreated($created);
    }

    /**
     * Get created
     *
     * @return \DateTime
     */
    public function getCreated()
    {
        return $this->entity->getCreated();
    }

    /**
     * Set changed
     *
     * @param \DateTime $changed
     */
    public function setChanged($changed)
    {
        $this->entity->setChanged($changed);
    }

    /**
     * Get changed
     *
     * @return \DateTime
     */
    public function getChanged()
    {
        return $this->entity->getChanged();
    }

    /**
     * Set changer
     *
     * @param UserInterface $changer
     */
    public function setChanger(UserInterface $changer = null)
    {
        $this->entity->setChanger($changer);
    }

    /**
     * Get changer
     *
     * @return UserInterface
     */
    public function getChanger()
    {
        return $this->entity->getChanger();
    }

    /**
     * Set creator
     *
     * @param UserInterface $creator
     */
    public function setCreator(UserInterface $creator = null)
    {
        $this->entity->setCreator($creator);
    }

    /**
     * Get creator
     *
     * @return UserInterface
     */
    public function getCreator()
    {
        return $this->entity->getCreator();
    }

    /**
     * Set parent
     *
     * @param AccountEntity $parent
     */
    public function setParent(AccountEntity $parent = null)
    {
        //  TODO replace by API entity
        $this->entity->setParent($parent);
    }

    /**
     * Get parent
     *
     * @return AccountEntity
     */
    public function getParent()
    {
        return $this->entity->getParent();
    }

    /**
     * Add urls
     *
     * @param Url $url
     */
    public function addUrl(Url $url)
    {
        $this->entity->addUrl($url);
    }

    /**
     * Remove urls
     *
     * @param Url $url
     */
    public function removeUrl(Url $url)
    {
        $this->entity->removeUrl($url);
    }

    /**
     * Get urls
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getUrls()
    {
        //  TODO replace by API entity
        return $this->entity->getUrls();
    }

    /**
     * Add phones
     *
     * @param Phone $phones
     */
    public function addPhone(Phone $phones)
    {
        $this->entity->addPhone($phones);
    }

    /**
     * Remove phones
     *
     * @param Phone $phone
     */
    public function removePhone(Phone $phone)
    {
        $this->entity->removePhone($phone);
    }

    /**
     * Get phones
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getPhones()
    {
        //  TODO replace by API entity
        return $this->entity->getPhones();
    }

    /**
     * Add emails
     *
     * @param Email $email
     */
    public function addEmail(Email $email)
    {
        $this->entity->addEmail($email);
    }

    /**
     * Remove emails
     *
     * @param Email $email
     */
    public function removeEmail(Email $email)
    {
        $this->entity->removeEmail($email);
    }

    /**
     * Get emails
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getEmails()
    {
        //  TODO replace by API entity
        return $this->entity->getEmails();
    }

    /**
     * Add notes
     *
     * @param Note $notes
     */
    public function addNote(Note $notes)
    {
        $this->entity->addNote($notes);
    }

    /**
     * Remove notes
     *
     * @param Note $note
     */
    public function removeNote(Note $note)
    {
        $this->entity->removeNote($note);
    }

    /**
     * Get notes
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getNotes()
    {
        //  TODO replace by API entity
        return $this->entity->getNotes();
    }

    /**
     * Add children
     *
     * @param AccountEntity $children
     */
    public function addChildren(AccountEntity $children)
    {
        $this->entity->addChildren($children);
    }

    /**
     * Remove children
     *
     * @param AccountEntity $children
     */
    public function removeChildren(AccountEntity $children)
    {
        $this->entity->removeChildren($children);
    }

    /**
     * Get children
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getChildren()
    {
        //  TODO replace by API entity
        return $this->entity->getChildren();
    }

    /**
     * Set type
     *
     * @param integer $type
     */
    public function setType($type)
    {
        $this->entity->setType($type);
    }

    /**
     * Get type
     *
     * @return integer
     */
    public function getType()
    {
        return $this->entity->getType();
    }

    /**
     * Add faxes
     *
     * @param Fax $fax
     */
    public function addFax(Fax $fax)
    {
        $this->entity->addFax($fax);
    }

    /**
     * Remove faxes
     *
     * @param Fax $fax
     */
    public function removeFax(Fax $fax)
    {
        $this->entity->removeFax($fax);
    }

    /**
     * Get faxes
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getFaxes()
    {
        //  TODO replace by API entity
        return $this->entity->getFaxes();
    }

    /**
     * Set corporation
     *
     * @param string $corporation
     */
    public function setCorporation($corporation)
    {
        $this->entity->setCorporation($corporation);
    }

    /**
     * Get corporation
     *
     * @return string
     */
    public function getCorporation()
    {
        return $this->entity->getCorporation();
    }

    /**
     * Set disabled
     *
     * @param integer $disabled
     */
    public function setDisabled($disabled)
    {
        $this->entity->setDisabled($disabled);
    }

    /**
     * Get disabled
     *
     * @return integer
     */
    public function getDisabled()
    {
        return $this->entity->getDisabled();
    }

    /**
     * Set uid
     *
     * @param string $uid
     */
    public function setUid($uid)
    {
        $this->entity->setUid($uid);
    }

    /**
     * Get uid
     *
     * @return string
     */
    public function getUid()
    {
        return $this->entity->getUid();
    }

    /**
     * Add faxes
     *
     * @param Fax $fax
     */
    public function addFaxe(Fax $fax)
    {
        $this->entity->addFaxe($fax);
    }

    /**
     * Remove faxes
     *
     * @param Fax $fax
     */
    public function removeFaxe(Fax $fax)
    {
        $this->entity->removeFaxe($fax);
    }

    /**
     * Set accountCategory
     *
     * @param AccountCategory $accountCategory
     */
    public function setAccountCategory(AccountCategory $accountCategory = null)
    {
        $this->entity->setAccountCategory($accountCategory);
    }

    /**
     * Set registerNumber
     *
     * @param string $registerNumber
     */
    public function setRegisterNumber($registerNumber)
    {
        $this->entity->setRegisterNumber($registerNumber);
    }

    /**
     * Get registerNumber
     *
     * @return string
     */
    public function getRegisterNumber()
    {
        return $this->entity->getRegisterNumber();
    }

    /**
     * Add bankAccounts
     *
     * @param BankAccount $bankAccount
     */
    public function addBankAccount(BankAccount $bankAccount)
    {
        $this->entity->addBankAccount($bankAccount);
    }

    /**
     * Get accountCategory
     *
     * @return AccountCategory
     */
    public function getAccountCategory()
    {
        //  TODO replace by API entity
        return $this->entity->getAccountCategory();
    }

    /**
     * Remove bankAccounts
     *
     * @param BankAccount $bankAccount
     */
    public function removeBankAccount(BankAccount $bankAccount)
    {
        $this->entity->removeBankAccount($bankAccount);
    }

    /**
     * Get bankAccounts
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getBankAccounts()
    {
        //  TODO replace by API entity
        return $this->entity->getBankAccounts();
    }

    /**
     * Add tags
     *
     * @param Tag $tag
     */
    public function addTag(Tag $tag)
    {
        $this->entity->addTag($tag);
    }

    /**
     * Remove tags
     *
     * @param Tag $tag
     */
    public function removeTag(Tag $tag)
    {
        $this->entity->removeTag($tag);
    }

    /**
     * Get tags
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getTags()
    {
        //  TODO replace by API entity
        return $this->entity->getTags();
    }

    /**
     * parses tags to array containing tag names
     *
     * @return array
     */
    public function getTagNameArray()
    {
        $tags = array();
        if (!is_null($this->entity->getTags())) {
            /** @var Tag $tag */
            foreach ($this->entity->getTags() as $tag) {
                $tags[] = $tag->getName();
            }
        }
        return $tags;
    }

    /**
     * Add accountContacts
     *
     * @param AccountContact $accountContact
     */
    public function addAccountContact(AccountContact $accountContact)
    {
        $this->entity->addAccountContact($accountContact);
    }

    /**
     * Remove accountContacts
     *
     * @param AccountContact $accountContact
     */
    public function removeAccountContact(AccountContact $accountContact)
    {
        $this->entity->removeAccountContact($accountContact);
    }

    /**
     * Get accountContacts
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getAccountContacts()
    {
        //  TODO replace by API entity
        return $this->entity->getAccountContacts();
    }

    /**
     * Set placeOfJurisdiction
     *
     * @param string $placeOfJurisdiction
     */
    public function setPlaceOfJurisdiction($placeOfJurisdiction)
    {
        $this->entity->setPlaceOfJurisdiction($placeOfJurisdiction);
    }

    /**
     * Get placeOfJurisdiction
     *
     * @return string
     */
    public function getPlaceOfJurisdiction()
    {
        return $this->entity->getPlaceOfJurisdiction();
    }

    /**
     * Set termsOfPayment
     *
     * @param TermsOfPayment $termsOfPayment
     */
    public function setTermsOfPayment(TermsOfPayment $termsOfPayment = null)
    {
        $this->entity->setTermsOfPayment($termsOfPayment);
    }

    /**
     * Get termsOfPayment
     *
     * @return TermsOfPayment
     */
    public function getTermsOfPayment()
    {
        //  TODO replace by API entity
        return $this->entity->getTermsOfPayment();
    }

    /**
     * Set termsOfDelivery
     *
     * @param TermsOfDelivery $termsOfDelivery
     */
    public function setTermsOfDelivery(TermsOfDelivery $termsOfDelivery = null)
    {
        $this->entity->setTermsOfDelivery($termsOfDelivery);
    }

    /**
     * Get termsOfDelivery
     *
     * @return TermsOfDelivery
     */
    public function getTermsOfDelivery()
    {
        //  TODO replace by API entity
        return $this->entity->getTermsOfDelivery();
    }

    /**
     * Set number
     *
     * @param string $number
     */
    public function setNumber($number)
    {
        $this->entity->setNumber($number);
    }

    /**
     * Get number
     *
     * @return string
     */
    public function getNumber()
    {
        return $this->entity->getNumber();
    }

    /**
     * Set externalId
     *
     * @param string $externalId
     */
    public function setExternalId($externalId)
    {
        $this->entity->setExternalId($externalId);
    }

    /**
     * Get externalId
     *
     * @return string
     */
    public function getExternalId()
    {
        return $this->entity->GetExternalId();
    }

    /**
     * Set responsiblePerson
     *
     * @param Contact $responsiblePerson
     */
    public function setResponsiblePerson(Contact $responsiblePerson = null)
    {
        $this->entity->setResponsiblePerson($responsiblePerson);
    }

    /**
     * Set mainContact
     *
     * @param Contact $mainContact
     */
    public function setMainContact(Contact $mainContact = null)
    {
        $this->entity->setMainContact($mainContact);
    }

    /**
     * Get responsiblePerson
     *
     * @return Contact
     */
    public function getResponsiblePerson()
    {
        //  TODO replace by API entity
        return $this->entity->getResponsiblePerson();
    }

    /**
     * Add activities
     *
     * @param Activity $activities
     */
    public function addActivitie(Activity $activities)
    {
        $this->entity->addActivitie($activities);
    }

    /**
     * Get mainContact
     *
     * @return Contact
     */
    public function getMainContact()
    {
        //  TODO replace by API entity
        return $this->entity->getMainContact();
    }

    /**
     * Set mainEmail
     *
     * @param string $mainEmail
     */
    public function setMainEmail($mainEmail)
    {
        $this->entity->setMainEmail($mainEmail);
    }

    /**
     * Get mainEmail
     *
     * @return string
     */
    public function getMainEmail()
    {
        //  TODO replace by API entity
        return $this->entity->getMainEmail();
    }

    /**
     * Set mainPhone
     *
     * @param string $mainPhone
     */
    public function setMainPhone($mainPhone)
    {
        $this->entity->setMainPhone($mainPhone);
    }

    /**
     * Get mainPhone
     *
     * @return string
     */
    public function getMainPhone()
    {
        return $this->entity->getMainPhone();
    }

    /**
     * Set mainFax
     *
     * @param string $mainFax
     */
    public function setMainFax($mainFax)
    {
        $this->entity->setMainFax($mainFax);
    }

    /**
     * Remove activities
     *
     * @param Activity $activitie
     */
    public function removeActivitie(Activity $activitie)
    {
        $this->entity->removeActivitie($activitie);
    }

    /**
     * Get activities
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getActivities()
    {
        //  TODO replace by API entity
        return $this->entity->getActivities();
    }

    /**
     * Get mainFax
     *
     * @return string
     */
    public function getMainFax()
    {
        return $this->entity->getMainFax();
    }

    /**
     * Set mainUrl
     *
     * @param string $mainUrl
     */
    public function setMainUrl($mainUrl)
    {
        $this->entity->setMainUrl($mainUrl);
    }

    /**
     * Get mainUrl
     *
     * @return string
     */
    public function getMainUrl()
    {
        return $this->entity->getMainUrl();
    }

    /**
     * Add accountAddresses
     *
     * @param AccountAddress $accountAddress
     */
    public function addAccountAddresse(AccountAddress $accountAddress)
    {
        $this->entity->addAccountAddresse($accountAddress);
    }

    /**
     * Remove accountAddresses
     *
     * @param AccountAddress $accountAddresses
     */
    public function removeAccountAddresse(AccountAddress $accountAddresses)
    {
        $this->entity->removeAccountAddresse($accountAddresses);
    }

    /**
     * Get accountAddresses
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getAccountAddresses()
    {
        //  TODO replace by API entity
        return $this->entity->getAccountAddresses();
    }

    /**
     * returns addresses
     */
    public function getAddresses()
    {
        //  TODO replace by API entity

        $accountAddresses = $this->entity->getAccountAddresses();
        $addresses = array();

        if (!is_null($accountAddresses)) {
            /** @var ContactAddress $accountAddress */
            foreach ($accountAddresses as $accountAddress) {
                $address = $accountAddress->getAddress();
                $address->setPrimaryAddress($accountAddress->getMain());
                $addresses[] = $address;
            }
        }

        return $addresses;
    }

    /**
     * Returns the main address
     *
     * @return mixed
     */
    public function getMainAddress()
    {
        //  TODO replace by API entity

        $accountAddresses = $this->entity->getAccountAddresses();

        if (!is_null($accountAddresses)) {
            /** @var AccountAddress $accountAddress */
            foreach ($accountAddresses as $accountAddress) {
                if ($accountAddress->getMain()) {
                    return $accountAddress->getAddress();
                }
            }
        }

        return null;
    }

    /**
     * Get contacts
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getContacts()
    {
        //  TODO replace by API entity
        $accountContacts = $this->entity->getAccountContacts();
        $contacts = [];

        if (!is_null($accountContacts)) {
            /** @var AccountContact $accountContact */
            foreach ($accountContacts as $accountContact) {
                $contacts[] = $accountContact->getContact();
            }
        }

        return $contacts;
    }

    /**
     * Add medias
     *
     * @param Media $medias
     */
    public function addMedia(Media $medias)
    {
        $this->entity->addMedia($medias);
    }

    /**
     * Remove medias
     *
     * @param Media $medias
     */
    public function removeMedia(Media $medias)
    {
        $this->entity->removeMedia($medias);
    }

    /**
     * Get medias
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getMedias()
    {
        //  TODO replace by API entity
        return $this->entity->getMedias();
    }
}
