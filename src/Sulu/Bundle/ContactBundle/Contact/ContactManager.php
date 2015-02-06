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
use Doctrine\ORM\EntityNotFoundException;
use Sulu\Bundle\ContactBundle\Api\Contact;
use Sulu\Bundle\ContactBundle\Entity\Contact as ContactEntity;
use Sulu\Bundle\ContactBundle\Entity\ContactAddress as ContactAddressEntity;
use Sulu\Bundle\ContactBundle\Entity\Address as AddressEntity;
use Sulu\Bundle\TagBundle\Tag\TagManagerInterface;

/**
 * This Manager handles Contact functionality
 * Class ContactManager
 *
 * @package Sulu\Bundle\ContactBundle\Contact
 */
class ContactManager extends AbstractContactManager
{
    protected $contactEntity = 'SuluContactBundle:Contact';
    protected $tagManager;

    public function __construct(ObjectManager $em, TagmanagerInterface $tagManager)
    {
        parent::__construct($em);
        $this->tagManager = $tagManager;
    }

    /**
     * adds an address to the entity
     *
     * @param Contact $contact The entity to add the address to
     * @param AddressEntity $address The address to be added
     * @param Bool $isMain Defines if the address is the main Address of the contact
     * @return ContactAddressEntity
     * @throws \Exception
     */
    public function addAddress($contact, AddressEntity $address, $isMain)
    {
        if (!$contact || !$address) {
            throw new \Exception('Contact and Address cannot be null');
        }
        $contactAddress = new ContactAddressEntity();
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
     * @param ContactEntity $contact
     * @param ContactAddressEntity $contactAddress
     * @return mixed|void
     * @throws \Exception
     */
    public function removeAddressRelation($contact, $contactAddress)
    {
        if (!$contact || !$contactAddress) {
            throw new \Exception('Contact and ContactAddress cannot be null');
        }

        // reload address to get all data (including relational data)
        /** @var AddressEntity $address */
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
     * @param $id
     * @param $locale
     * @throws \Doctrine\ORM\EntityNotFoundException
     * @return mixed
     */
    public function getById($id, $locale)
    {
        $contact = $this->em->getRepository($this->contactEntity)->find($id);
        if (!$contact) {
            return null;
        }

        return new Contact($contact, $locale, $this->tagManager);
    }

    /**
     * Returns an api entity for an doctrine entity
     * @param $contact
     * @param $locale
     * @return null|Contact
     */
    public function getContact($contact, $locale)
    {
        if ($contact) {
            return new Contact($contact, $locale, $this->tagManager);
        } else {
            return null;
        }
    }

    /**
     * @param ContactEntity $contact
     * @param $data
     * @throws \Doctrine\ORM\EntityNotFoundException
     */
    public function setMainAccount(ContactEntity $contact, $data)
    {
        // set account relation
        if (isset($data['account']) &&
            isset($data['account']['id']) &&
            $data['account']['id'] != 'null'
        ) {
            $accountId = $data['account']['id'];

            /** @var Account $account */
            $account = $this->em
                ->getRepository(self::$accountEntityName)
                ->findAccountById($accountId);

            if (!$account) {
                throw new EntityNotFoundException(self::$accountEntityName, $accountId);
            }

            // get position
            $position = null;
            if (isset($data['position'])) {
                $position = $this->getPosition($data['position']);
            }

            // check if relation between account and contact already exists
            $mainAccountContact = $this->getMainAccountContact($contact);
            $accountContact = $this->getAccounContact($account, $contact);

            // remove previous main accountContact
            if ($mainAccountContact && $mainAccountContact !== $accountContact) {

                // if this contact is the main-Contact - set mainContact to null
                if ($mainAccountContact->getAccount()->getMainContact() === $contact) {
                    $mainAccountContact->getAccount()->setMainContact(null);
                }
                $this->em->remove($mainAccountContact);
            }

            // if account-contact relation existed set params
            if ($accountContact) {
                $accountContact->setMain(true);
                $accountContact->setPosition($position);
            } else {
                // else create new one
                $this->createMainAccountContact($contact, $account, $position);
            }

        } else {
            // if a main account exists - remove it
            if ($accountContact = $this->getMainAccountContact($contact)) {

                // if this contact is the main-Contact - set mainContact to null
                if ($accountContact->getAccount()->getMainContact() === $contact) {
                    $accountContact->getAccount()->setMainContact(null);
                }

                $this->em->remove($accountContact);
            }
        }
    }
}
