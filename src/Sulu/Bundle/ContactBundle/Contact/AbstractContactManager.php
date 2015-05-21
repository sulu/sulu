<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContactBundle\Contact;

use Doctrine\Common\Persistence\ObjectManager;
use Sulu\Bundle\ContactBundle\Entity\AccountAddress;
use Sulu\Bundle\ContactBundle\Entity\AccountContact;
use Sulu\Bundle\ContactBundle\Entity\AccountInterface;
use Sulu\Bundle\ContactBundle\Entity\Address;
use Sulu\Bundle\ContactBundle\Entity\Contact;

/**
 * This Manager handles general Account and Contact functionality.
 */
abstract class AbstractContactManager implements ContactManagerInterface
{
    protected static $contactEntityName = 'SuluContactBundle:Contact';
    protected static $accountContactEntityName = 'SuluContactBundle:AccountContact';
    protected static $positionEntityName = 'SuluContactBundle:Position';
    protected static $addressTypeEntityName = 'SuluContactBundle:AddressType';
    protected static $urlTypeEntityName = 'SuluContactBundle:UrlType';
    protected static $emailTypeEntityName = 'SuluContactBundle:EmailType';
    protected static $faxTypeEntityName = 'SuluContactBundle:FaxType';
    protected static $phoneTypeEntityName = 'SuluContactBundle:PhoneType';

    /**
     * @var ObjectManager
     */
    public $em;

    /**
     * @var string
     */
    protected $accountEntityName;

    /**
     * @param ObjectManager $em
     * @param $accountEntityName
     */
    public function __construct(ObjectManager $em, $accountEntityName)
    {
        $this->em = $em;
        $this->accountEntityName = $accountEntityName;
    }

    /**
     * unsets main of all elements of an ArrayCollection | PersistanceCollection.
     *
     * @param $arrayCollection
     *
     * @return bool returns true if a element was unset
     */
    public function unsetMain($arrayCollection)
    {
        if ($arrayCollection && !$arrayCollection->isEmpty()) {
            return $arrayCollection->forAll(
                function ($index, $entry) {
                    if ($entry->getMain() === true) {
                        $entry->setMain(false);

                        return false;
                    }

                    return true;
                }
            );
        }
    }

    /**
     * sets the first element to main, if none is set.
     *
     * @param $arrayCollection
     */
    public function setMainForCollection($arrayCollection)
    {
        if ($arrayCollection && !$arrayCollection->isEmpty() && !$this->hasMain($arrayCollection)) {
            $arrayCollection->first()->setMain(true);
        }
    }

    /**
     * checks if a collection for main attribute.
     *
     * @param $arrayCollection
     * @param $mainEntity will be set, if found
     *
     * @return mixed
     */
    private function hasMain($arrayCollection, &$mainEntity = null)
    {
        if ($arrayCollection && !$arrayCollection->isEmpty()) {
            return $arrayCollection->exists(
                function ($index, $entity) {
                    $mainEntity = $entity;

                    return $entity->getMain() === true;
                }
            );
        }

        return false;
    }

    /**
     * sets Entity's Main-Email.
     *
     * @param Contact|AccountInterface $entity
     */
    public function setMainEmail($entity)
    {
        // set main to first entry or to null
        if ($entity->getEmails()->isEmpty()) {
            $entity->setMainEmail(null);
        } else {
            $entity->setMainEmail($entity->getEmails()->first()->getEmail());
        }
    }

    /**
     * sets Entity's Main-Phone.
     *
     * @param Contact|AccountInterface $entity
     */
    public function setMainPhone($entity)
    {
        // set main to first entry or to null
        if ($entity->getPhones()->isEmpty()) {
            $entity->setMainPhone(null);
        } else {
            $entity->setMainPhone($entity->getPhones()->first()->getPhone());
        }
    }

    /**
     * sets Entity's Main-Fax.
     *
     * @param Contact|AccountInterface $entity
     */
    public function setMainFax($entity)
    {
        // set main to first entry or to null
        if ($entity->getFaxes()->isEmpty()) {
            $entity->setMainFax(null);
        } else {
            $entity->setMainFax($entity->getFaxes()->first()->getFax());
        }
    }

    /**
     * sets Entity's Main-Url.
     *
     * @param Contact|AccountInterface $entity
     */
    public function setMainUrl($entity)
    {
        // set main to first entry or to null
        if ($entity->getUrls()->isEmpty()) {
            $entity->setMainUrl(null);
        } else {
            $entity->setMainUrl($entity->getUrls()->first()->getUrl());
        }
    }

    /**
     * Returns AccountContact relation if exists.
     *
     * @param AccountInterface $account
     * @param Contact $contact
     *
     * @return null|AccountContact
     */
    public function getAccounContact(AccountInterface $account, Contact $contact)
    {
        foreach ($contact->getAccountContacts() as $accountContact) {
            /** @var AccountContact $accountContact */
            if ($accountContact->getAccount() === $account) {
                return $accountContact;
            }
        }

        return;
    }

    /**
     * returns the main account-contact relation.
     *
     * @param Contact|AccountInterface $contact
     *
     * @return AccountContact|bool
     */
    public function getMainAccountContact($contact)
    {
        foreach ($contact->getAccountContacts() as $accountContact) {
            /** @var AccountContact $accountContact */
            if ($accountContact->getMain()) {
                return $accountContact;
            }
        }

        return false;
    }

    /**
     * creates a new main Account Contacts relation.
     *
     * @param Contact $contact
     * @param AccountInterface $account
     * @param $position
     *
     * @return AccountContact
     */
    public function createMainAccountContact(Contact $contact, AccountInterface $account, $position = null)
    {
        $accountContact = new AccountContact();
        $accountContact->setAccount($account);
        $accountContact->setContact($contact);
        $accountContact->setMain(true);
        $this->em->persist($accountContact);
        $contact->addAccountContact($accountContact);
        $accountContact->setPosition($position);

        return $accountContact;
    }

    /**
     * Get a position object.
     *
     * @param int $id The position id
     *
     * @return mixed
     */
    public function getPosition($id)
    {
        if ($id) {
            return $this->em->getRepository(self::$positionEntityName)->find($id);
        }

        return;
    }

    /**
     * return address type by name.
     *
     * @param $name
     *
     * @return mixed
     */
    public function getAddressTypeByName($name)
    {
        return $this->em
            ->getRepository(self::$addressTypeEntityName)
            ->findOneByName($name);
    }

    /**
     * return url type by name.
     *
     * @param $name
     *
     * @return mixed
     */
    public function getUrlTypeByName($name)
    {
        return $this->em
            ->getRepository(self::$urlTypeEntityName)
            ->findOneByName($name);
    }

    /**
     * return phone type by name.
     *
     * @param $name
     *
     * @return mixed
     */
    public function getPhoneTypeByName($name)
    {
        return $this->em
            ->getRepository(self::$phoneTypeEntityName)
            ->findOneByName($name);
    }

    /**
     * return fax type by name.
     *
     * @param $name
     *
     * @return mixed
     */
    public function getFaxTypeByName($name)
    {
        return $this->em
            ->getRepository(self::$faxTypeEntityName)
            ->findOneByName($name);
    }

    /**
     * return email type by name.
     *
     * @param $name
     *
     * @return mixed
     */
    public function getEmailTypeByName($name)
    {
        return $this->em
            ->getRepository(self::$emailTypeEntityName)
            ->findOneByName($name);
    }

    /**
     * clears all relational data from entity and deletes it.
     *
     * @param $entity
     */
    public function deleteAllRelations($entity)
    {
        $this->deleteNotes($entity);
        $this->deleteAddresses($entity);
        $this->deleteEmails($entity);
        $this->deleteFaxes($entity);
        $this->deletePhones($entity);
        $this->deleteUrls($entity);
    }

    /**
     * deletes all notes that are assigned to entity.
     *
     * @param $entity
     */
    public function deleteNotes($entity)
    {
        if ($entity->getNotes()) {
            $this->deleteAllEntitiesOfCollection($entity->getNotes());
        }
    }

    /**
     * deletes all phones that are assigned to entity.
     *
     * @param $entity
     */
    public function deletePhones($entity)
    {
        if ($entity->getPhones()) {
            $this->deleteAllEntitiesOfCollection($entity->getPhones());
        }
    }

    /**
     * deletes all faxes that are assigned to entity.
     *
     * @param $entity
     */
    public function deleteFaxes($entity)
    {
        if ($entity->getFaxes()) {
            $this->deleteAllEntitiesOfCollection($entity->getFaxes());
        }
    }

    /**
     * deletes all urls that are assigned to entity.
     *
     * @param $entity
     */
    public function deleteUrls($entity)
    {
        if ($entity->getUrls()) {
            $this->deleteAllEntitiesOfCollection($entity->getUrls());
        }
    }

    /**
     * deletes all addresses that are assigned to entity.
     *
     * @param $entity
     */
    public function deleteAddresses($entity)
    {
        // clear addresses
        if ($entity->getAccountAddresses()) {
            foreach ($entity->getAccountAddresses() as $accountAddresses) {
                $this->em->remove($accountAddresses->getAddress());
                $this->em->remove($accountAddresses);
            }
        }
    }

    /**
     * deletes all emails that are assigned to entity.
     *
     * @param $entity
     */
    public function deleteEmails($entity)
    {
        if ($entity->getEmails()) {
            $this->deleteAllEntitiesOfCollection($entity->getEmails());
        }
    }

    /**
     * @param $arrayCollection
     */
    protected function deleteAllEntitiesOfCollection($arrayCollection)
    {
        foreach ($arrayCollection as $entity) {
            $this->em->remove($entity);
        }
    }

    /**
     * Returns the billing address of an account/contact.
     *
     * @param AccountInterface|Contact $entity
     * @param bool $force Forces function to return an address if any address is defined
     *          if no delivery address is defined it will first return the main address then any
     *
     * @return mixed
     */
    public function getBillingAddress($entity, $force = false)
    {
        /** @var Address $address */
        $conditionCallback = function ($address) {
            return $address->getBillingAddress();
        };

        return $this->getAddressByCondition($entity, $conditionCallback, $force);
    }

    /**
     * Returns the delivery address.
     *
     * @param AccountInterface|Contact $entity
     * @param bool $force Forces function to return an address if any address is defined
     *          if no delivery address is defined it will first return the main address then any
     *
     * @return mixed
     */
    public function getDeliveryAddress($entity, $force = false)
    {
        /** @var Address $address */
        $conditionCallback = function ($address) {
            return $address->getDeliveryAddress();
        };

        return $this->getAddressByCondition($entity, $conditionCallback, $force);
    }

    /**
     * checks if an account is employee of a company.
     *
     * @param $contact
     * @param $account
     *
     * @return bool
     */
    public function contactIsEmployeeOfAccount($contact, $account)
    {
        if ($contact->getAccountContacts() && !$contact->getAccountContacts()->isEmpty()) {
            foreach ($contact->getAccountContacts() as $accountContact) {
                if ($accountContact->getAccount()->getId() === $account->getId()) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * returns addresses from account or contact.
     *
     * @param AccountInterface|Contact $entity
     *
     * @return \Doctrine\Common\Collections\Collection|null
     */
    private function getAddresses($entity)
    {
        if ($entity instanceof AccountInterface) {
            return $entity->getAccountAddresses();
        } elseif ($entity instanceof Contact) {
            return $entity->getContactAddresses();
        }

        return;
    }

    /**
     * Returns an address by callback-condition.
     *
     * @param AccountInterface|Contact $entity
     * @param callable $conditionCallback
     * @param bool $force Forces function to return an address if any address is defined
     *          if no delivery address is defined it will first return the main address then any
     *
     * @return mixed
     */
    public function getAddressByCondition($entity, callable $conditionCallback, $force = false)
    {
        $addresses = $this->getAddresses($entity);
        $address = null;
        $main = null;

        if (!is_null($addresses)) {
            /** @var AccountAddress $accountAddress */
            foreach ($addresses as $address) {
                if ($conditionCallback($address->getAddress())) {
                    return $address->getAddress();
                }
                if ($address->getMain()) {
                    $main = $address->getAddress();
                }
            }
            if ($force) {
                // return main or first address
                if ($main === null && $addresses->first()) {
                    return $addresses->first()->getAddress();
                }
            }
        }

        return $main;
    }
}
