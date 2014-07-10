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
 * @package Sulu\Bundle\ContactBundle\Contact
 */
class ContactManager extends AbstractContactManager
{
    /**
     * adds an address to the entity
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

        $contact->addAccountAddresse($contactAddress);
        $address->addAccountAddresse($contactAddress);

        return $contactAddress;
    }

    /**
     * Returns a collection of relations to get addresses
     * @param $entity
     * @return mixed
     */
    public function getAddressRelations($entity)
    {
        return $entity->getContactAddresses();
    }

    /**
     * removes the address relation from a contact and also deletes the address if it has no more relations
     * @param $entity
     * @param ContactAddress $contactAddress
     * @return mixed
     */
    public function removeAddressRelation($entity, $contactAddress)
    {
        $address = $contactAddress->getAddress();
        $isMain = $contactAddress->getMain();

        // remove relation
        $entity->removeContactAddresse($contactAddress);
        $address->removeContactAddresse($contactAddress);

        // if was main, set a new one
        if ($isMain) {
            $this->setMainForCollection($entity->getContactContacts());
        }

        // delete address if it has no more relations
        if (!$address->hasRelations()) {
            $this->$em->remove($address);
        }

        $this->$em->remove($contactAddress);
    }
}
