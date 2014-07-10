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
use Sulu\Bundle\ContactBundle\Entity\AccountAddress;
use Sulu\Bundle\ContactBundle\Entity\Address;

/**
 * This Manager handles Account functionality
 * Class AccountManager
 * @package Sulu\Bundle\ContactBundle\Contact
 */
class AccountManager extends AbstractContactManager
{
    /**
     * adds an address to the entity
     * @param Account $account The entity to add the address to
     * @param Address $address The address to be added
     * @param Bool $isMain Defines if the address is the main Address of the contact
     * @return AccountAddress
     * @throws \Exception
     */
    public function addAddress($account, Address $address, $isMain)
    {
        if (!$account || !$address) {
            throw new \Exception('Account and Address cannot be null');
        }
        $accountAddress = new AccountAddress();
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
     * @param $entity
     * @param AccountAddress $accountAddress
     * @return mixed
     */
    public function removeAddressRelation($entity, $accountAddress)
    {
        $address = $accountAddress->getAddress();
        $isMain = $accountAddress->getMain();

        // remove relation
        $entity->removeAccountAddresse($accountAddress);
        $address->removeAccountAddresse($accountAddress);

        // if was main, set a new one
        if ($isMain) {
            $this->setMainForCollection($entity->getAccountContacts());
        }

        // delete address if it has no more relations
        if (!$address->hasRelations()) {
            $this->$em->remove($address);
        }

        $this->$em->remove($accountAddress);
    }

    /**
     * Returns a collection of relations to get addresses
     * @param $entity
     * @return mixed
     */
    public function getAddressRelations($entity)
    {
        return $entity->getAccountAddresses();
    }
}
