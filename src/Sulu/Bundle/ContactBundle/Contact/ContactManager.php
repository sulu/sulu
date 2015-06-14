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

use \DateTime;
use Doctrine\Common\Persistence\ObjectManager;
use Sulu\Bundle\ContactBundle\Api\Contact as ContactApi;
use Sulu\Bundle\ContactBundle\Entity\Address;
use Sulu\Bundle\ContactBundle\Entity\Contact;
use Sulu\Bundle\ContactBundle\Entity\ContactAddress;
use Sulu\Bundle\TagBundle\Tag\TagManagerInterface;
use Sulu\Bundle\ContactBundle\Entity\AccountRepository;
use Sulu\Bundle\ContactBundle\Entity\contactTitleRepository;
use Sulu\Component\Rest\Exception\EntityNotFoundException;
use Sulu\Bundle\ContactBundle\Entity\ContactRepository;

class ContactManager extends AbstractContactManager
{
    /**
     * @var AccountRepository
     */
    private $accountRepository;

    /**
     * @var ContactTitleRepository
     */
    private $contactTitleRepository;

    /**
     * @var ContactRepository
     */
    private $contactRepository;

    public function __construct(
        ObjectManager $em,
        TagManagerInterface $tagManager,
        AccountRepository $accountRepository,
        ContactTitleRepository $contactTitleRepository,
        ContactRepository $contactRepository,
        $accountEntityName
    ) {
        parent::__construct($em, $tagManager, $accountEntityName);
        $this->accountRepository = $accountRepository;
        $this->contactTitleRepository = $contactTitleRepository;
        $this->contactRepository = $contactRepository;
    }

    /**
     * Find a contact by it's id
     *
     * @param int $id
     */
    public function findById($id)
    {
        $contact = $this->contactRepository->findById($id);
        if (!$contact) {
            return null;
        }

        return $contact;
    }

    /**
     * Returns the tag manager
     *
     * @return TagManagerInterface
     */
    public function getTagManager()
    {
        return $this->tagManager;
    }

    /**
     * Deletes the contact for the given id
     *
     * @param int $id
     */
    public function delete($id)
    {
        /**
         * TODO: https://github.com/sulu-io/sulu/pull/1171
         * This method needs to be refactored since in the first
         * iteration the logic was just moved from the Controller
         * to this class due to better reusability.
        */
        $delete = function ($id) {
            /** @var Contact $contact */
            $contact = $this->em->getRepository(
                self::$contactEntityName
            )->findByIdAndDelete($id);

            if (!$contact) {
                throw new EntityNotFoundException(self::$contactEntityName, $id);
            }

            $addresses = $contact->getAddresses();
            /** @var Address $address */
            foreach ($addresses as $address) {
                if (!$address->hasRelations()) {
                    $this->em->remove($address);
                }
            }

            $phones = $contact->getPhones()->toArray();
            /** @var Phone $phone */
            foreach ($phones as $phone) {
                if ($phone->getAccounts()->count() == 0 && $phone->getContacts()->count() == 1) {
                    $this->em->remove($phone);
                }
            }
            $emails = $contact->getEmails()->toArray();
            /** @var Email $email */
            foreach ($emails as $email) {
                if ($email->getAccounts()->count() == 0 && $email->getContacts()->count() == 1) {
                    $this->em->remove($email);
                }
            }

            $urls = $contact->getUrls()->toArray();
            /** @var Url $url */
            foreach ($urls as $url) {
                if ($url->getAccounts()->count() == 0 && $url->getContacts()->count() == 1) {
                    $this->em->remove($url);
                }
            }

            $faxes = $contact->getFaxes()->toArray();
            /** @var Fax $fax */
            foreach ($faxes as $fax) {
                if ($fax->getAccounts()->count() == 0 && $fax->getContacts()->count() == 1) {
                    $this->em->remove($fax);
                }
            }

            $this->em->remove($contact);
            $this->em->flush();
        };

        return $delete;
    }

    /**
     * Creates a new contact for the given data
     *
     * @param array $data
     * @param int $id
     * @param bool $flush
     *
     * @return Contact
     */
    public function save(
        $data,
        $id = null,
        $patch = false,
        $flush = true
    ) {
        /**
         * TODO: https://github.com/sulu-io/sulu/pull/1171
         * This method needs to be refactored since in the first
         * iteration the logic was just moved from the Controller to this class due
         * to better reusability.
        */
        $firstName = $this->getProperty($data, 'firstName');
        $lastName = $this->getProperty($data, 'lastName');

        if ($id) {
            /** @var Contact $contact */
            $contact = $this->em
                ->getRepository(self::$contactEntityName)
                ->findById($id);

            if (!$contact) {
                throw new EntityNotFoundException(self::$contactEntityName, $id);
            }
            if (!$patch || $this->getProperty($data, 'account')) {
                $this->setMainAccount($contact, $data);
            }
            if (!$patch || $this->getProperty($data, 'emails')) {
                $this->processEmails($contact, $this->getProperty($data, 'emails', array()));
            }
            if (!$patch || $this->getProperty($data, 'phones')) {
                $this->processPhones($contact, $this->getProperty($data, 'phones', array()));
            }
            if (!$patch || $this->getProperty($data, 'addresses')) {
                $this->processAddresses($contact, $this->getProperty($data, 'addresses', array()));
            }
            if (!$patch || $this->getProperty($data, 'notes')) {
                $this->processNotes($contact, $this->getProperty($data, 'notes', array()));
            }
            if (!$patch || $this->getProperty($data, 'faxes')) {
                $this->processFaxes($contact, $this->getProperty($data, 'faxes', array()));
            }
            if (!$patch || $this->getProperty($data, 'tags')) {
                $this->processTags($contact, $this->getProperty($data, 'tags', array()));
            }
            if (!$patch || $this->getProperty($data, 'urls')) {
                $this->processUrls($contact, $this->getProperty($data, 'urls', array()));
            }
            if (!$patch || $this->getProperty($data, 'categories')) {
                $this->processCategories($contact, $this->getProperty($data, 'categories', array()));
            }
            if (!$patch || $this->getProperty($data, 'bankAccounts')) {
                $this->processBankAccounts($contact, $this->getProperty($data, 'bankAccounts', array()));
            }

        } else {
            $contact = new Contact();
        }

        if (!$patch || $firstName !== null) {
            $contact->setFirstName($firstName);
        }
        if (!$patch || $lastName !== null) {
            $contact->setLastName($lastName);
        }

        // Set title relation on contact
        $this->setTitleOnContact($contact, $this->getProperty($data, 'title'));
        $formOfAddress = $this->getProperty($data, 'formOfAddress');
        if (!is_null($formOfAddress) && is_array($formOfAddress) && array_key_exists('id', $formOfAddress)) {
            $contact->setFormOfAddress($formOfAddress['id']);
        }

        $disabled = $this->getProperty($data, 'disabled');
        if (!is_null($disabled)) {
            $contact->setDisabled($disabled);
        }

        $salutation = $this->getProperty($data, 'salutation');
        if (!empty($salutation)) {
            $contact->setSalutation($salutation);
        }

        $birthday = $this->getProperty($data, 'birthday');
        if (!empty($birthday)) {
            $contact->setBirthday(new DateTime($birthday));
        }

        if (!$id) {
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
                $position = $this->getPosition($this->getProperty($data, 'position'));

                // create new account-contact relation
                $this->createMainAccountContact(
                    $contact,
                    $parent,
                    $position
                );
            }
            // add urls, phones, emails, tags, bankAccounts, notes, addresses,..
            $this->addNewContactRelations($contact, $data);
            $this->processCategories($contact, $this->getProperty($data, 'categories', array()));
        }

        $this->em->persist($contact);

        if ($flush) {
            $this->em->flush();
        }

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
     * @throws EntityNotFoundException
     *
     * @return mixed
     */
    public function getById($id, $locale)
    {
        //$contact = $this->em->getRepository(self::$contactEntityName)->find($id);
        $contact = $this->contactRepository->find($id);
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
     * @throws EntityNotFoundException
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
