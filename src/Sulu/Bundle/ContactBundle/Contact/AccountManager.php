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
use Sulu\Bundle\ContactBundle\Api\Account;
use Sulu\Bundle\ContactBundle\Entity\Account as AccountEntity;
use Sulu\Bundle\ContactBundle\Entity\AccountAddress as AccountAddressEntity;
use Sulu\Bundle\ContactBundle\Entity\Address as AddressEntity;
use Sulu\Bundle\TagBundle\Tag\TagManagerInterface;
use Sulu\Component\Rest\Exception\EntityNotFoundException;

/**
 * This Manager handles Account functionality
 * Class AccountManager
 *
 * @package Sulu\Bundle\ContactBundle\Contact
 */
class AccountManager extends AbstractContactManager
{

    protected $accountEntity = 'SuluContactBundle:Account';
    protected $tagManager;

    function __construct(ObjectManager $em, TagmanagerInterface $tagManager)
    {
        parent::__construct($em);
        $this->tagManager = $tagManager;
    }

    /**
     * adds an address to the entity
     *
     * @param Account $account The entity to add the address to
     * @param AddressEntity $address The address to be added
     * @param Bool $isMain Defines if the address is the main Address of the contact
     * @return AccountAddressEntity
     * @throws \Exception
     */
    public function addAddress($account, AddressEntity $address, $isMain = false)
    {
        if (!$account || !$address) {
            throw new \Exception('Account and Address cannot be null');
        }
        $accountAddress = new AccountAddressEntity();
        $accountAddress->setAccount($account);
        $accountAddress->setAddress($address);
        if ($isMain) {
            $this->unsetMain($account->getAccountAddresses());
        }
        $accountAddress->setMain($isMain);
        $account->addAccountAddresse($accountAddress);
        $address->addAccountAddresse($accountAddress);
        $this->em->persist($accountAddress);

        return $accountAddress;
    }

    /**
     * removes the address relation from a contact and also deletes the address if it has no more relations
     *
     * @param AccountEntity $account
     * @param AccountAddressEntity $accountAddress
     * @return mixed|void
     * @throws \Exception
     */
    public function removeAddressRelation($account, $accountAddress)
    {
        if (!$account || !$accountAddress) {
            throw new \Exception('Account and AccountAddress cannot be null');
        }

        // reload address to get all data (including relational data)
        /** @var AddressEntity $address */
        $address = $accountAddress->getAddress();
        $address = $this->em->getRepository(
            'SuluContactBundle:Address'
        )->findById($address->getId());

        $isMain = $accountAddress->getMain();

        // remove relation
        $address->removeAccountAddresse($accountAddress);
        $account->removeAccountAddresse($accountAddress);

        // if was main, set a new one
        if ($isMain) {
            $this->setMainForCollection($account->getAccountContacts());
        }

        // delete address if it has no more relations
        if (!$address->hasRelations()) {
            $this->em->remove($address);
        }

        $this->em->remove($accountAddress);
    }

    /**
     * Returns a collection of relations to get addresses
     *
     * @param $entity
     * @return mixed
     */
    public function getAddressRelations($entity)
    {

        return $entity->getAccountAddresses();
    }

    /**
     * @param $id
     * @param $locale
     * @throws EntityNotFoundException
     * @return mixed
     */
    protected function getById($id, $locale)
    {
        $account = $this->em->getRepository($this->accountEntity)->findAccountById($id);
        if(!$account){
            throw new EntityNotFoundException($this->accountEntity, $id);
        }
        return new Account($account, $locale, $this->tagManager);
    }

    /**
     * Returns all api entities
     *
     * @param $locale
     * @return mixed
     */
    protected function getAll($locale)
    {
        $accounts = [];
        $contactsEntities = $this->em->getRepository($this->accountEntity)->findAll();
        foreach($contactsEntities as $contact){
            $accounts[] = new Account($contact, $locale, $this->tagManager);
        }
        return $accounts;
    }}
