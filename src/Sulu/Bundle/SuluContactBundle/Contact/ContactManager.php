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

use Sulu\Bundle\ContactBundle\Entity\ContactAddress;
use Sulu\Bundle\ContactBundle\Entity\Address;

/**
 * This Manager handles Contact functionality
 * Class ContactManager
 *
 * @package Sulu\Bundle\ContactBundle\Contact
 */
class ContactManager extends AbstractContactManager
{
    /**
     * adds an address to the entity
     *
     * @param Contact $contact The entity to add the address to
     * @param Address $address The address to be added
     * @param Bool $isMain Defines if the address is the main Address of the contact
     * @return ContactAddress
     * @throws \Exception
     */
    public function addAddress($contact, Address $address, $isMain)
    {
        if (!$contact || !$address) {
            throw new \Exception('Contact and Address cannot be null');
        }
        $contactAddress = new ContactAddress();
        $contactAddress->setContact($contact);
        $contactAddress->setAddress($address);
        if ($isMain) {
            $this->unsetMain($contact->getContactAddresses());
        }
        $contactAddress->setMain($isMain);
        $this->em->persist($contactAddress);

        $contact->addContactAddresse($contactAddress);

        return $contactAddress;
    }

    /**
     * removes the address relation from a contact and also deletes the address if it has no more relations
     *
     * @param $contact
     * @param $contactAddress
     * @return mixed|void
     * @throws \Exception
     */
    public function removeAddressRelation($contact, $contactAddress)
    {
        if (!$contact || !$contactAddress) {
            throw new \Exception('Contact and ContactAddress cannot be null');
        }

        // reload address to get all data (including relational data)
        $address = $contactAddress->getAddress();
        $address = $this->em->getRepository(
            'SuluContactBundle:Address'
        )->findById($address->getId());

        $isMain = $contactAddress->getMain();

        // remove relation
        $contact->removeContactAddresse($contactAddress);
        $address->removeContactAddresse($contactAddress);

        // if was main, set a new one
        if ($isMain) {
            $this->setMainForCollection($contact->getContactAddresses());
        }

        // delete address if it has no more relations
        if (!$address->hasRelations()) {
            $this->em->remove($address);
        }

        $this->em->remove($contactAddress);
    }

    /**
     * Returns a collection of relations to get addresses
     *
     * @param $entity
     * @return mixed
     */
    public function getAddressRelations($entity)
    {
        return $entity->getContactAddresses();
    }

    /**
     * Returns the main address
     *
     * @param $entity
     * @return mixed
     */
    public function getMainAddress($entity)
    {
        $contactAddresses = $entity->getContactAddresses();

        if (!is_null($contactAddresses)) {
            /** @var ContactAddress $contactAddress */
            foreach ($contactAddresses as $contactAddress) {
                if (!!$contactAddress->getMain()) {
                    return $contactAddress->getAddress();
                }
            }
        }
        return null;
    }
}
