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
use Sulu\Bundle\ContactBundle\Api\Contact as ContactApi;
use Sulu\Bundle\ContactBundle\Entity\Address;
use Sulu\Bundle\ContactBundle\Entity\Contact;
use Sulu\Bundle\ContactBundle\Entity\ContactAddress;
use Sulu\Bundle\TagBundle\Tag\TagManagerInterface;
use Sulu\Bundle\ContactBundle\Entity\AccountRepository;
use Sulu\Bundle\ContactBundle\Entity\contactTitleRepository;

class ContactManager extends AbstractContactManager
{
    /**
     * @var TagManagerInterface
     */
    private $tagManager;

    /**
     * @var AccountRepository
     */
    private $accountRepository;

    /**
     * @var contactTitleRepository
     */
    private $contactTitleRepository;

    public function __construct(
        ObjectManager $em,
        TagmanagerInterface $tagManager,
        AccountRepository $accountRepository,
        ContactTitleRepository $contactTitleRepository,
        $accountEntityName
    ) {
        parent::__construct($em, $accountEntityName);
        $this->tagManager = $tagManager;
        $this->accountRepository = $accountRepository;
        $this->contactTitleRepository = $contactTitleRepository;
    }

    /**
     * Creates a new contact for the given data
     *
     * @param array $data
     * @param bool $flush
     *
     * @return Contact
     */
    public function save($data, $flush = true)
    {
        $firstName = $data['firstName'];
        $lastName = $data['lastName'];
        $disabled = $data['disabled'];
        $formOfAddress = $data['formOfAddress'];

        // Standard contact fields
        $contact = new Contact();
        $contact->setFirstName($firstName);
        $contact->setLastName($lastName);

        $this->setTitleOnContact($contact, $data['title']);

        $parentData = $this->getProperty($data, 'account');
        if ($parentData != null &&
            $parentData['id'] != null &&
            $parentData['id'] != 'null' &&
            $parentData['id'] != ''
        ) {
            /** @var AccountInterface $parent */
            $parent = $this->accountRepository->findAccountById($parentData['id']);
            if (!$parent) {
                throw new EntityNotFoundException(
                    $this->getAccountEntityName(),
                    $parentData['id']
                );
            }

            // Set position on contact
            $position = $this->getPosition($data['position']);

            // create new account-contact relation
            $this->createMainAccountContact(
                $contact,
                $parent,
                $position
            );
        }
        $birthday = $this->getProperty($data, 'birthday');
        if ($birthday) {
            $contact->setBirthday(new DateTime($birthday));
        }

        $contact->setFormOfAddress($formOfAddress['id']);
        $contact->setDisabled($disabled);

        $salutation = $this->getProperty($data, 'salutation');
        if ($salutation) {
            $contact->setSalutation($salutation);
        }

        // add urls, phones, emails, tags, bankAccounts, notes, addresses,..
        $this->addNewContactRelations($contact, $data);
        $this->processCategories($contact, $this->getProperty($data, 'categories', array()));

        $this->em->persist($contact);
        $this->em->flush();

        return $contact;
    }

    /**
     * adds an address to the entity.
     *
     * @param Contact $contact The entity to add the address to
     * @param Address $address The address to be added
     * @param Bool $isMain Defines if the address is the main Address of the contact
     *
     * @return ContactAddress
     *
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
     * removes the address relation from a contact and also deletes the address if it has no more relations.
     *
     * @param Contact $contact
     * @param ContactAddress $contactAddress
     *
     * @return mixed|void
     *
     * @throws \Exception
     */
    public function removeAddressRelation($contact, $contactAddress)
    {
        if (!$contact || !$contactAddress) {
            throw new \Exception('Contact and ContactAddress cannot be null');
        }

        // reload address to get all data (including relational data)
        /** @var Address $address */
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
     * Returns a collection of relations to get addresses.
     *
     * @param $entity
     *
     * @return mixed
     */
    public function getAddressRelations($entity)
    {
        return $entity->getContactAddresses();
    }

    /**
     * @param $id
     * @param $locale
     *
     * @throws \Doctrine\ORM\EntityNotFoundException
     *
     * @return mixed
     */
    public function getById($id, $locale)
    {
        $contact = $this->em->getRepository(self::$contactEntityName)->find($id);
        if (!$contact) {
            return;
        }

        return new ContactApi($contact, $locale, $this->tagManager);
    }

    /**
     * Returns an api entity for an doctrine entity.
     *
     * @param $contact
     * @param $locale
     *
     * @return null|Contact
     */
    public function getContact($contact, $locale)
    {
        if ($contact) {
            return new ContactApi($contact, $locale, $this->tagManager);
        } else {
            return;
        }
    }

    /**
     * @param Contact $contact
     * @param $data
     *
     * @throws \Doctrine\ORM\EntityNotFoundException
     */
    public function setMainAccount(Contact $contact, $data)
    {
        // set account relation
        if (isset($data['account']) &&
            isset($data['account']['id']) &&
            $data['account']['id'] != 'null'
        ) {
            $accountId = $data['account']['id'];

            $account = $this->em
                ->getRepository($this->accountEntityName)
                ->findAccountById($accountId);

            if (!$account) {
                throw new EntityNotFoundException($this->accountEntityName, $accountId);
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

    /**
     * Return property for key or given default value
     *
     * @param array $data
     * @param string $key
     * @param string $default
     *
     * @return string|null
     */
    private function getProperty($data, $key, $default = null)
    {
        if (array_key_exists($key, $data)) {
            return $data[$key];
        }

        return $default;
    }

    /**
     * @param $contact
     * @param $titleId
     */
    public function setTitleOnContact($contact, $titleId)
    {
        if ($titleId && is_numeric($titleId)) {
            $title = $this->contactTitleRepository->find($titleId);
            if ($title) {
                $contact->setTitle($title);
            }
        } else {
            $contact->setTitle(null);
        }
    }
}
