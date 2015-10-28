<?php

/*
 * This file is part of the Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContactBundle\Contact;

use DateTime;
use Doctrine\Common\Persistence\ObjectManager;
use Sulu\Bundle\ContactBundle\Api\Contact as ContactApi;
use Sulu\Bundle\ContactBundle\Entity\AccountInterface;
use Sulu\Bundle\ContactBundle\Entity\AccountRepository;
use Sulu\Bundle\ContactBundle\Entity\Address;
use Sulu\Bundle\ContactBundle\Entity\Contact;
use Sulu\Bundle\ContactBundle\Entity\ContactAddress;
use Sulu\Bundle\ContactBundle\Entity\ContactRepository;
use Sulu\Bundle\ContactBundle\Entity\contactTitleRepository;
use Sulu\Bundle\ContactBundle\Entity\Fax;
use Sulu\Bundle\ContactBundle\Entity\Url;
use Sulu\Bundle\ContentBundle\Content\Types\Email;
use Sulu\Bundle\ContentBundle\Content\Types\Phone;
use Sulu\Bundle\MediaBundle\Media\Manager\MediaManagerInterface;
use Sulu\Bundle\TagBundle\Tag\TagManagerInterface;
use Sulu\Component\Rest\Exception\EntityNotFoundException;
use Sulu\Component\SmartContent\Orm\DataProviderRepositoryInterface;

class ContactManager extends AbstractContactManager implements DataProviderRepositoryInterface
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

    /**
     * @param ObjectManager $em
     * @param TagManagerInterface $tagManager
     * @param MediaManagerInterface $mediaManager
     * @param AccountRepository $accountRepository
     * @param ContactTitleRepository $contactTitleRepository
     * @param ContactRepository $contactRepository
     */
    public function __construct(
        ObjectManager $em,
        TagManagerInterface $tagManager,
        MediaManagerInterface $mediaManager,
        AccountRepository $accountRepository,
        ContactTitleRepository $contactTitleRepository,
        ContactRepository $contactRepository
    ) {
        parent::__construct($em, $tagManager, $mediaManager);
        $this->accountRepository = $accountRepository;
        $this->contactTitleRepository = $contactTitleRepository;
        $this->contactRepository = $contactRepository;
    }

    /**
     * Find a contact by it's id.
     *
     * @param int $id
     *
     * @return mixed|null
     */
    public function findById($id)
    {
        $contact = $this->contactRepository->findById($id);
        if (!$contact) {
            return;
        }

        return $contact;
    }

    /**
     * Returns contact entities by ids.
     *
     * @param $ids
     * @param $locale
     *
     * @return mixed
     */
    public function getByIds($ids, $locale)
    {
        if (!is_array($ids) || count($ids) === 0) {
            return [];
        }

        $contacts = $this->contactRepository->findByIds($ids);

        return array_map(
            function ($contact) use ($locale) {
                return $this->getApiObject($contact, $locale);
            },
            $contacts
        );
    }

    /**
     * Deletes the contact for the given id.
     *
     * @return \Closure
     */
    public function delete()
    {
        /*
         * TODO: https://github.com/sulu-io/sulu/pull/1171
         * This method needs to be refactored since in the first
         * iteration the logic was just moved from the Controller
         * to this class due to better reusability.
         */
        $delete = function ($id) {
            /** @var Contact $contact */
            $contact = $this->contactRepository->findByIdAndDelete($id);

            if (!$contact) {
                throw new EntityNotFoundException($this->contactRepository->getClassName(), $id);
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
     * Creates a new contact for the given data.
     *
     * @param array $data
     * @param int|null $id
     * @param bool $patch
     * @param bool $flush
     *
     * @return Contact
     *
     * @throws EntityNotFoundException
     */
    public function save(
        $data,
        $id = null,
        $patch = false,
        $flush = true
    ) {
        /*
         * TODO: https://github.com/sulu-io/sulu/pull/1171
         * This method needs to be refactored since in the first
         * iteration the logic was just moved from the Controller to this class due
         * to better reusability.
         */
        $firstName = $this->getProperty($data, 'firstName');
        $lastName = $this->getProperty($data, 'lastName');
        $avatar = $this->getProperty($data, 'avatar');

        if ($id) {
            /** @var Contact $contact */
            $contact = $this->contactRepository->findById($id);

            if (!$contact) {
                throw new EntityNotFoundException($this->contactRepository->getClassName(), $id);
            }
            if (!$patch || $this->getProperty($data, 'account')) {
                $this->setMainAccount($contact, $data);
            }
            if (!$patch || $this->getProperty($data, 'emails')) {
                $this->processEmails($contact, $this->getProperty($data, 'emails', []));
            }
            if (!$patch || $this->getProperty($data, 'phones')) {
                $this->processPhones($contact, $this->getProperty($data, 'phones', []));
            }
            if (!$patch || $this->getProperty($data, 'addresses')) {
                $this->processAddresses($contact, $this->getProperty($data, 'addresses', []));
            }
            if (!$patch || $this->getProperty($data, 'notes')) {
                $this->processNotes($contact, $this->getProperty($data, 'notes', []));
            }
            if (!$patch || $this->getProperty($data, 'faxes')) {
                $this->processFaxes($contact, $this->getProperty($data, 'faxes', []));
            }
            if (!$patch || $this->getProperty($data, 'tags')) {
                $this->processTags($contact, $this->getProperty($data, 'tags', []));
            }
            if (!$patch || $this->getProperty($data, 'urls')) {
                $this->processUrls($contact, $this->getProperty($data, 'urls', []));
            }
            if (!$patch || $this->getProperty($data, 'categories')) {
                $this->processCategories($contact, $this->getProperty($data, 'categories', []));
            }
            if (!$patch || $this->getProperty($data, 'bankAccounts')) {
                $this->processBankAccounts($contact, $this->getProperty($data, 'bankAccounts', []));
            }
        } else {
            $contact = $this->contactRepository->createNew();
        }

        if (!$patch || $firstName !== null) {
            $contact->setFirstName($firstName);
        }
        if (!$patch || $lastName !== null) {
            $contact->setLastName($lastName);
        }
        if (!$patch || $avatar !== null) {
            $this->setAvatar($contact, $avatar);
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
                        self::$accountContactEntityName,
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
            $this->processCategories($contact, $this->getProperty($data, 'categories', []));
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
     * @param bool $isMain Defines if the address is the main Address of the contact
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

        $contact->addContactAddress($contactAddress);

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
        $contact->removeContactAddress($contactAddress);
        $address->removeContactAddress($contactAddress);

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
        $contact = $this->contactRepository->find($id);
        if (!$contact) {
            throw new EntityNotFoundException($this->contactRepository->getClassName(), $id);
        }

        return $this->getApiObject($contact, $locale);
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
            return $this->getApiObject($contact, $locale);
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

            $account = $this->accountRepository->findAccountById($accountId);

            if (!$account) {
                throw new EntityNotFoundException($this->accountRepository->getClassName(), $accountId);
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
     * Sets a media with a given id as the avatar of a given contact.
     *
     * @param Contact $contact
     * @param array $avatar with id property
     */
    private function setAvatar(Contact $contact, $avatar)
    {
        $mediaEntity = null;
        if (is_array($avatar) && $this->getProperty($avatar, 'id')) {
            $mediaEntity = $this->mediaManager->getEntityById($this->getProperty($avatar, 'id'));
        }
        $contact->setAvatar($mediaEntity);
    }

    /**
     * Takes a contact entity and a locale and returns the api object.
     *
     * @param Contact $contact
     * @param string $locale
     *
     * @return ContactApi
     */
    protected function getApiObject($contact, $locale)
    {
        $apiObject = new ContactApi($contact, $locale);
        if ($contact->getAvatar()) {
            $apiAvatar = $this->mediaManager->getById($contact->getAvatar()->getId(), $locale);
            $apiObject->setAvatar($apiAvatar);
        }

        return $apiObject;
    }

    /**
     * Return property for key or given default value.
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

    /**
     * Get contact entity name.
     *
     * @return string
     */
    public function getContactEntityName()
    {
        return $this->contactRepository->getClassName();
    }

    /**
     * {@inheritdoc}
     */
    public function findByFilters($filters, $page, $pageSize, $limit, $locale)
    {
        $entities = $this->contactRepository->findByFilters($filters, $page, $pageSize, $limit, $locale);

        return array_map(
            function ($contact) use ($locale) {
                return $this->getApiObject($contact, $locale);
            },
            $entities
        );
    }
}
