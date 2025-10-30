<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContactBundle\Contact;

use Doctrine\Persistence\ObjectManager;
use Sulu\Bundle\ActivityBundle\Application\Collector\DomainEventCollectorInterface;
use Sulu\Bundle\ContactBundle\Api\Contact as ContactApi;
use Sulu\Bundle\ContactBundle\Domain\Event\AccountContactAddedEvent;
use Sulu\Bundle\ContactBundle\Domain\Event\AccountContactRemovedEvent;
use Sulu\Bundle\ContactBundle\Domain\Event\ContactCreatedEvent;
use Sulu\Bundle\ContactBundle\Domain\Event\ContactMediaAddedEvent;
use Sulu\Bundle\ContactBundle\Domain\Event\ContactMediaRemovedEvent;
use Sulu\Bundle\ContactBundle\Domain\Event\ContactModifiedEvent;
use Sulu\Bundle\ContactBundle\Domain\Event\ContactRemovedEvent;
use Sulu\Bundle\ContactBundle\Entity\AccountInterface;
use Sulu\Bundle\ContactBundle\Entity\AccountRepositoryInterface;
use Sulu\Bundle\ContactBundle\Entity\Address;
use Sulu\Bundle\ContactBundle\Entity\Contact;
use Sulu\Bundle\ContactBundle\Entity\ContactAddress;
use Sulu\Bundle\ContactBundle\Entity\ContactInterface;
use Sulu\Bundle\ContactBundle\Entity\ContactRepository;
use Sulu\Bundle\ContactBundle\Entity\ContactTitleRepository;
use Sulu\Bundle\ContactBundle\Entity\Email;
use Sulu\Bundle\ContactBundle\Entity\Fax;
use Sulu\Bundle\ContactBundle\Entity\Note;
use Sulu\Bundle\ContactBundle\Entity\Phone;
use Sulu\Bundle\ContactBundle\Entity\Position;
use Sulu\Bundle\ContactBundle\Entity\SocialMediaProfile;
use Sulu\Bundle\ContactBundle\Entity\Url;
use Sulu\Bundle\MediaBundle\Entity\MediaInterface;
use Sulu\Bundle\MediaBundle\Entity\MediaRepositoryInterface;
use Sulu\Bundle\MediaBundle\Media\Manager\MediaManagerInterface;
use Sulu\Bundle\SecurityBundle\Domain\Event\UserRemovedEvent;
use Sulu\Bundle\SecurityBundle\Entity\UserRepository;
use Sulu\Bundle\TagBundle\Tag\TagManagerInterface;
use Sulu\Bundle\TrashBundle\Application\TrashManager\TrashManagerInterface;
use Sulu\Component\Rest\Exception\EntityNotFoundException;
use Sulu\Component\Security\Authentication\UserInterface;

/**
 * @extends AbstractContactManager<ContactInterface, ContactApi, ContactAddress>
 */
class ContactManager extends AbstractContactManager
{
    public function __construct(
        ObjectManager $em,
        TagManagerInterface $tagManager,
        MediaManagerInterface $mediaManager,
        private AccountRepositoryInterface $accountRepository,
        private ContactTitleRepository $contactTitleRepository,
        private ContactRepository $contactRepository,
        protected MediaRepositoryInterface $mediaRepository,
        protected DomainEventCollectorInterface $domainEventCollector,
        protected UserRepository $userRepository,
        private ?TrashManagerInterface $trashManager
    ) {
        parent::__construct($em, $tagManager, $mediaManager);
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
     * @param array $ids
     * @param string $locale
     */
    public function getByIds($ids, $locale)
    {
        if (!\is_array($ids) || 0 === \count($ids)) {
            return [];
        }

        $contacts = $this->contactRepository->findByIds($ids);

        return \array_map(
            function($contact) use ($locale) {
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
        $delete = function($id) {
            /** @var Contact $contact */
            $contact = $this->contactRepository->findByIdAndDelete($id);

            if (!$contact) {
                throw new EntityNotFoundException($this->contactRepository->getClassName(), $id);
            }

            if ($this->trashManager) {
                $this->trashManager->store(ContactInterface::RESOURCE_KEY, $contact);
            }

            $contactId = $contact->getId();
            $contactFullName = $contact->getFullName();

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
                if (0 == $phone->getAccounts()->count() && 1 == $phone->getContacts()->count()) {
                    $this->em->remove($phone);
                }
            }
            $emails = $contact->getEmails()->toArray();
            /** @var Email $email */
            foreach ($emails as $email) {
                if (0 == $email->getAccounts()->count() && 1 == $email->getContacts()->count()) {
                    $this->em->remove($email);
                }
            }

            $urls = $contact->getUrls()->toArray();
            /** @var Url $url */
            foreach ($urls as $url) {
                if (0 == $url->getAccounts()->count() && 1 == $url->getContacts()->count()) {
                    $this->em->remove($url);
                }
            }

            $faxes = $contact->getFaxes()->toArray();
            /** @var Fax $fax */
            foreach ($faxes as $fax) {
                if (0 == $fax->getAccounts()->count() && 1 == $fax->getContacts()->count()) {
                    $this->em->remove($fax);
                }
            }

            $socialMediaProfiles = $contact->getSocialMediaProfiles()->toArray();
            /** @var SocialMediaProfile $socialMediaProfile */
            foreach ($socialMediaProfiles as $socialMediaProfile) {
                if (0 == $socialMediaProfile->getAccounts()->count()
                    && 1 == $socialMediaProfile->getContacts()->count()
                ) {
                    $this->em->remove($socialMediaProfile);
                }
            }

            $notes = $contact->getNotes()->toArray();
            /** @var Note $note */
            foreach ($notes as $note) {
                if (0 == $note->getAccounts()->count() && 1 == $note->getContacts()->count()) {
                    $this->em->remove($note);
                }
            }

            $this->em->remove($contact);

            $this->domainEventCollector->collect(
                new ContactRemovedEvent($contactId, $contactFullName)
            );

            /** @var UserInterface|null $user */
            $user = $this->userRepository->findUserByContact($contact->getId());
            if ($user) {
                $this->domainEventCollector->collect(new UserRemovedEvent($user->getId(), $user->getUserIdentifier()));
            }

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
     * @return ContactInterface
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

        $contactDetailsData = $data['contactDetails'] ?? [];
        $isNewContact = false;

        $contactModified = false;
        if ($id) {
            /** @var Contact $contact */
            $contact = $this->contactRepository->findById($id);

            if (!$contact) {
                throw new EntityNotFoundException($this->contactRepository->getClassName(), $id);
            }
            if (!$patch || ($data['account'] ?? null)) {
                $this->setMainAccount($contact, $data);
                $contactModified = true;
            }
            if (!$patch || ($contactDetailsData['emails'] ?? null)) {
                $this->processEmails($contact, $contactDetailsData['emails'] ?? []);
                $contactModified = true;
            }
            if (!$patch || ($contactDetailsData['phones'] ?? null)) {
                $this->processPhones($contact, $contactDetailsData['phones'] ?? []);
                $contactModified = true;
            }
            if (!$patch || ($data['addresses'] ?? null)) {
                $this->processAddresses($contact, $data['addresses'] ?? []);
                $contactModified = true;
            }
            if (!$patch || ($data['notes'] ?? null)) {
                $this->processNotes($contact, $data['notes'] ?? []);
                $contactModified = true;
            }
            if (!$patch || ($contactDetailsData['faxes'] ?? null)) {
                $this->processFaxes($contact, $contactDetailsData['faxes'] ?? []);
                $contactModified = true;
            }
            if (!$patch || ($contactDetailsData['socialMedia'] ?? null)) {
                $this->processSocialMediaProfiles(
                    $contact,
                    $contactDetailsData['socialMedia'] ?? []
                );
                $contactModified = true;
            }
            if (!$patch || ($data['tags'] ?? null)) {
                $this->processTags($contact, $data['tags'] ?? []);
                $contactModified = true;
            }
            if (!$patch || ($contactDetailsData['websites'] ?? null)) {
                $this->processUrls($contact, $contactDetailsData['websites'] ?? []);
                $contactModified = true;
            }
            if (!$patch || ($data['categories'] ?? null)) {
                $this->processCategories($contact, $data['categories'] ?? []);
                $contactModified = true;
            }
            if (!$patch || ($data['bankAccounts'] ?? null)) {
                $this->processBankAccounts($contact, $data['bankAccounts'] ?? []);
                $contactModified = true;
            }
        } else {
            $contact = $this->contactRepository->createNew();
            $isNewContact = true;
        }

        if (!$patch || null !== ($data['firstName'] ?? null)) {
            $contact->setFirstName($data['firstName'] ?? null);
            $contactModified = true;
        }
        if (!$patch || null !== ($data['lastName'] ?? null)) {
            $contact->setLastName($data['lastName'] ?? null);
            $contactModified = true;
        }
        if (!$patch || null !== ($data['avatar'] ?? null)) {
            $this->setAvatar($contact, $data['avatar'] ?? null);
            $contactModified = true;
        }
        if (!$patch || null !== ($data['note'] ?? null)) {
            $contact->setNote($data['note'] ?? null);
            $contactModified = true;
        }
        if (!$patch || null !== ($data['medias'] ?? null)) {
            /** @var int[] $medias */
            $medias = $data['medias'] ?? [];
            $this->setMedias($contact, $medias);
        }

        if (!$patch || ($data['title'] ?? null)) {
            $this->setTitleOnContact($contact, $data['title'] ?? null);
            $contactModified = true;
        }

        if (!$patch || ($data['formOfAddress'] ?? null)) {
            $formOfAddress = $data['formOfAddress'] ?? null;

            if (\is_numeric($formOfAddress) || \is_string($formOfAddress)) {
                $contact->setFormOfAddress($formOfAddress);
                $contactModified = true;
            }

            if (!\is_null($formOfAddress) && \is_array($formOfAddress) && \array_key_exists('id', $formOfAddress)) {
                @trigger_deprecation(
                    'sulu/sulu',
                    '1.x',
                    'Passing the "formOfAddress" as object is deprecated and will not be supported in Sulu 2.0'
                );
                $contact->setFormOfAddress($formOfAddress['id']);
                $contactModified = true;
            }
        }

        if (!$patch || ($data['salutation'] ?? null)) {
            $contact->setSalutation($data['salutation'] ?? null);
            $contactModified = true;
        }

        if (!$patch || ($data['birthday'] ?? null)) {
            $birthday = $data['birthday'] ?? null;
            if (!empty($birthday)) {
                $birthday = new \DateTime($birthday);
            } else {
                $birthday = null;
            }
            $contact->setBirthday($birthday);
            $contactModified = true;
        }

        if (!$id) {
            $parentData = $data['account'] ?? null;
            if (null !== $parentData) {
                /** @var AccountInterface $parent */
                $parent = $this->accountRepository->findAccountById($parentData);
                if (!$parent) {
                    throw new EntityNotFoundException(self::$accountContactEntityName, $parentData);
                }

                // Set position on contact
                $position = $this->getPosition($data['position'] ?? null);

                // create new account-contact relation
                $this->createMainAccountContact(
                    $contact,
                    $parent,
                    $position
                );
            }
            // add urls, phones, emails, tags, bankAccounts, notes, addresses,..
            $this->addNewContactRelations($contact, $data);
            $this->processCategories($contact, $data['categories'] ?? []);
        }

        $this->em->persist($contact);

        if ($isNewContact) {
            $this->domainEventCollector->collect(
                new ContactCreatedEvent($contact, $data)
            );
        } elseif ($contactModified) {
            $this->domainEventCollector->collect(
                new ContactModifiedEvent($contact, $data)
            );
        }

        if ($flush) {
            $this->em->flush();
        }

        return $contact;
    }

    /**
     * adds an address to the entity.
     *
     * @param ContactInterface $contact The entity to add the address to
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
     * @param ContactInterface $contact
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
        $address = $this->em->getRepository(Address::class)->findById($address->getId());

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
     * @param ContactInterface $entity
     */
    public function getAddressRelations($entity)
    {
        return $entity->getContactAddresses();
    }

    /**
     * @param int $id
     * @param string $locale
     *
     * @throws EntityNotFoundException
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
     * @param ContactInterface $contact
     * @param string $locale
     *
     * @return null|ContactApi
     */
    public function getContact($contact, $locale)
    {
        if ($contact) {
            return $this->getApiObject($contact, $locale);
        }

        return null;
    }

    /**
     * @throws EntityNotFoundException
     */
    public function setMainAccount(ContactInterface $contact, array $data)
    {
        $accountId = $data['account'] ?? null;
        if (null !== $accountId) {
            $account = $this->accountRepository->findAccountById($accountId);

            if (!$account) {
                throw new EntityNotFoundException($this->accountRepository->getClassName(), $accountId);
            }

            // get position
            $position = $this->getPosition($data['position'] ?? null);

            // check if relation between account and contact already exists
            $mainAccountContact = $this->getMainAccountContact($contact);
            $accountContact = $this->getAccounContact($account, $contact);

            // remove previous main accountContact
            if ($mainAccountContact && $mainAccountContact !== $accountContact) {
                // if this contact is the main-Contact - set mainContact to null
                if ($mainAccountContact->getAccount()->getMainContact() === $contact) {
                    $mainAccountContact->getAccount()->setMainContact(null);
                }

                $this->domainEventCollector->collect(
                    new AccountContactRemovedEvent($mainAccountContact->getAccount(), $mainAccountContact->getContact())
                );

                $contact->removeAccountContact($mainAccountContact);
                $this->em->remove($mainAccountContact);
            }

            // if account-contact relation existed set params
            if ($accountContact) {
                $accountContact->setMain(true);
                $accountContact->setPosition($position);
            } else {
                // else create new one
                $accountContact = $this->createMainAccountContact($contact, $account, $position);
                $this->domainEventCollector->collect(new AccountContactAddedEvent($accountContact));
            }
        } else {
            // if a main account exists - remove it
            if ($accountContact = $this->getMainAccountContact($contact)) {
                // if this contact is the main-Contact - set mainContact to null
                if ($accountContact->getAccount()->getMainContact() === $contact) {
                    $accountContact->getAccount()->setMainContact(null);
                }

                $contact->removeAccountContact($accountContact);

                $this->domainEventCollector->collect(
                    new AccountContactRemovedEvent($accountContact->getAccount(), $accountContact->getContact())
                );

                $this->em->remove($accountContact);
            }
        }
    }

    /**
     * Sets a media with a given id as the avatar of a given contact.
     *
     * @param array $avatar with id property
     *
     * @throws EntityNotFoundException
     */
    private function setAvatar(ContactInterface $contact, $avatar)
    {
        $mediaEntity = null;
        if (\is_array($avatar) && ($avatar['id'] ?? null)) {
            $mediaId = $avatar['id'] ?? null;
            $mediaEntity = $this->mediaRepository->findMediaById($mediaId);

            if (!$mediaEntity) {
                throw new EntityNotFoundException($this->mediaRepository->getClassName(), $mediaId);
            }
        }
        $contact->setAvatar($mediaEntity);
    }

    /**
     * Sets the medias of the given contact to the given medias.
     * Currently associated medias are replaced.
     *
     * @param int[] $mediaIds
     *
     * @throws EntityNotFoundException
     */
    private function setMedias(ContactInterface $contact, $mediaIds)
    {
        /** @var MediaInterface[] $foundMedias */
        $foundMedias = [];
        if (\count($mediaIds) > 0) {
            /** @var MediaInterface[] $foundMedias */
            $foundMedias = $this->mediaRepository->findById($mediaIds);
        }
        /** @var int[] $foundMediaIds */
        $foundMediaIds = \array_map(
            function(MediaInterface $mediaEntity) {
                return $mediaEntity->getId();
            },
            $foundMedias
        );

        if ($missingMediaIds = \array_diff($mediaIds, $foundMediaIds)) {
            throw new EntityNotFoundException($this->mediaRepository->getClassName(), \reset($missingMediaIds));
        }

        foreach ($contact->getMedias() as $media) {
            if (!\in_array($media->getId(), $foundMediaIds)) {
                $contact->removeMedia($media);

                $this->domainEventCollector->collect(
                    new ContactMediaRemovedEvent($contact, $media)
                );
            }
        }

        foreach ($foundMedias as $media) {
            if (!$contact->getMedias()->contains($media)) {
                $contact->addMedia($media);

                $this->domainEventCollector->collect(
                    new ContactMediaAddedEvent($contact, $media)
                );
            }
        }
    }

    /**
     * Takes a contact entity and a locale and returns the api object.
     *
     * @param ContactInterface $contact
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
     * @param ContactInterface $contact
     * @param int $titleId
     */
    public function setTitleOnContact($contact, $titleId)
    {
        if ($titleId && \is_numeric($titleId)) {
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
     * Get a position object.
     */
    public function getPosition(?int $id): ?Position
    {
        if (null === $id) {
            return null;
        }

        return $this->em->getRepository(self::$positionEntityName)->find($id);
    }
}
