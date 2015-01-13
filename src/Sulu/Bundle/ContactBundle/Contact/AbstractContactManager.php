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
use Sulu\Bundle\ContactBundle\Entity\Account;
use Sulu\Bundle\ContactBundle\Entity\AccountContact;
use Sulu\Bundle\ContactBundle\Entity\Contact;

/**
 * This Manager handles general Account and Contact functionality
 * Class AbstractContactManager
 * @package Sulu\Bundle\ContactBundle\Contact
 */
abstract class AbstractContactManager implements ContactManagerInterface
{
    protected static $contactEntityName = 'SuluContactBundle:Contact';
    protected static $accountEntityName = 'SuluContactBundle:Account';
    protected static $accountContactEntityName = 'SuluContactBundle:AccountContact';
    protected static $positionEntityName = 'SuluContactBundle:Position';
    protected static $addressTypeEntityName = 'SuluContactBundle:AddressType';

    /**
     * @var ObjectManager $em
     */
    public $em;

    /**
     * @param ObjectManager $em
     */
    public function __construct(ObjectManager $em)
    {
        $this->em = $em;
    }

    /**
     * unsets main of all elements of an ArrayCollection | PersistanceCollection
     * @param $arrayCollection
     * @return boolean returns true if a element was unset
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
     * sets the first element to main, if none is set
     * @param $arrayCollection
     */
    public function setMainForCollection($arrayCollection)
    {
        if ($arrayCollection && !$arrayCollection->isEmpty() && !$this->hasMain($arrayCollection)) {
            $arrayCollection->first()->setMain(true);
        }
    }

    /**
     * checks if a collection for main attribute
     * @param $arrayCollection
     * @param $mainEntity will be set, if found
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
     * sets Entity's Main-Email
     * @param Contact|Account $entity
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
     * sets Entity's Main-Phone
     * @param Contact|Account $entity
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
     * sets Entity's Main-Fax
     * @param Contact|Account $entity
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
     * sets Entity's Main-Url
     * @param Contact|Account $entity
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
     * Returns AccountContact relation if exists
     * @param Account $account
     * @param Contact $contact
     * @return null|AccountContact
     */
    public function getAccounContact(Account $account, Contact $contact)
    {
        foreach ($contact->getAccountContacts() as $accountContact) {
            /** @var AccountContact $accountContact */
            if ($accountContact->getAccount() === $account) {
                return $accountContact;
            }
        }

        return null;
    }

    /**
     * returns the main account-contact relation
     *
     * @param Contact|Account $contact
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
     * creates a new main Account Contacts relation
     *
     * @param Contact $contact
     * @param Account $account
     * @param $position
     * @return AccountContact
     */
    public function createMainAccountContact(Contact $contact, Account $account, $position = null)
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
     * Get a position object
     * @param $id The position id
     * @return mixed
     */
    public function getPosition($id)
    {
        if ($id) {
            return $this->em->getRepository(self::$positionEntityName)->find($id);
        }

        return null;
    }

    /**
     * return address type by name
     * @param $name
     * @param bool $strict defines if only part of string needs to be matched
     * @return mixed
     */
    public function getAddressTypeByName($name, $strict = false)
    {
        return $this->em
            ->getRepository(self::$addressTypeEntityName)
            ->findOneByName($name, $strict);
    }
}
