<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContactBundle\Contact;

use Doctrine\Common\Persistence\ObjectManager;
use Sulu\Bundle\ContactBundle\Api\Account as AccountApi;
use Sulu\Bundle\ContactBundle\Api\Contact;
use Sulu\Bundle\ContactBundle\Entity\Account;
use Sulu\Bundle\ContactBundle\Entity\AccountAddress as AccountAddressEntity;
use Sulu\Bundle\ContactBundle\Entity\AccountInterface;
use Sulu\Bundle\ContactBundle\Entity\AccountRepository;
use Sulu\Bundle\ContactBundle\Entity\Address as AddressEntity;
use Sulu\Bundle\ContactBundle\Entity\ContactRepository;
use Sulu\Bundle\MediaBundle\Entity\MediaRepositoryInterface;
use Sulu\Bundle\MediaBundle\Media\Manager\MediaManagerInterface;
use Sulu\Bundle\TagBundle\Tag\TagManagerInterface;
use Sulu\Component\Rest\Exception\EntityNotFoundException;
use Sulu\Component\SmartContent\Orm\DataProviderRepositoryInterface;

/**
 * This Manager handles Account functionality.
 */
class AccountManager extends AbstractContactManager implements DataProviderRepositoryInterface
{
    protected $addressEntity = 'SuluContactBundle:Address';

    /**
     * @var AccountFactory
     */
    private $accountFactory;

    /**
     * @var AccountRepository
     */
    private $accountRepository;

    /**
     * @var ContactRepository
     */
    private $contactRepository;

    /**
     * @var MediaRepositoryInterface
     */
    protected $mediaRepository;

    /**
     * @param ObjectManager $em
     * @param TagManagerInterface $tagManager
     * @param MediaManagerInterface $mediaManager
     * @param AccountFactory $accountFactory
     * @param AccountRepository $accountRepository
     * @param ContactRepository $contactRepository
     * @param MediaRepositoryInterface $mediaRepository
     */
    public function __construct(
        ObjectManager $em,
        TagManagerInterface $tagManager,
        MediaManagerInterface $mediaManager,
        AccountFactory $accountFactory,
        AccountRepository $accountRepository,
        ContactRepository $contactRepository,
        MediaRepositoryInterface $mediaRepository
    ) {
        parent::__construct($em, $tagManager, $mediaManager);
        $this->accountFactory = $accountFactory;
        $this->accountRepository = $accountRepository;
        $this->contactRepository = $contactRepository;
        $this->mediaRepository = $mediaRepository;
    }

    /**
     * Adds an address to the entity.
     *
     * @param AccountApi $account The entity to add the address to
     * @param AddressEntity $address The address to be added
     * @param bool $isMain Defines if the address is the main Address of the contact
     *
     * @throws \Exception
     *
     * @return AccountAddressEntity
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
        $account->addAccountAddress($accountAddress);
        $address->addAccountAddress($accountAddress);
        $this->em->persist($accountAddress);

        return $accountAddress;
    }

    /**
     * Removes the address relation from a contact and also deletes the address
     * if it has no more relations.
     *
     * @param AccountInterface $account
     * @param AccountAddressEntity $accountAddress
     *
     * @throws \Exception
     *
     * @return mixed|void
     */
    public function removeAddressRelation($account, $accountAddress)
    {
        if (!$account || !$accountAddress) {
            throw new \Exception('Account and AccountAddress cannot be null');
        }

        // Reload address to get all data (including relational data).
        /** @var AddressEntity $address */
        $address = $accountAddress->getAddress();
        $address = $this->em->getRepository('SuluContactBundle:Address')
            ->findById($address->getId());

        $isMain = $accountAddress->getMain();

        // Remove relation.
        $address->removeAccountAddress($accountAddress);
        $account->removeAccountAddress($accountAddress);

        // If was main, set a new one.
        if ($isMain) {
            $this->setMainForCollection($account->getAccountContacts());
        }

        // Delete address if it has no more relations.
        if (!$address->hasRelations()) {
            $this->em->remove($address);
        }

        $this->em->remove($accountAddress);
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
        return $entity->getAccountAddresses();
    }

    /**
     * Gets account by id.
     *
     * @param int $id
     * @param string $locale
     *
     * @throws EntityNotFoundException
     *
     * @return mixed
     */
    public function getById($id, $locale)
    {
        $account = $this->accountRepository->findAccountById($id);
        if (!$account) {
            throw new EntityNotFoundException($this->accountRepository->getClassName(), $id);
        }

        return $this->getApiObject($account, $locale);
    }

    /**
     * Returns account entities by ids.
     *
     * @param array $ids
     * @param string $locale
     *
     * @return array
     */
    public function getByIds($ids, $locale)
    {
        if (!is_array($ids) || count($ids) === 0) {
            return [];
        }

        $accounts = $this->accountRepository->findByIds($ids);

        return array_map(
            function ($account) use ($locale) {
                return $this->getApiObject($account, $locale);
            },
            $accounts
        );
    }

    /**
     * Gets account by id - can include relations.
     *
     * @param int $id
     * @param string $locale
     * @param array $includes
     *
     * @throws EntityNotFoundException
     *
     * @return AccountApi
     */
    public function getByIdAndInclude($id, $locale, $includes)
    {
        $account = $this->accountRepository->findAccountById($id, in_array('contacts', $includes));

        if (!$account) {
            throw new EntityNotFoundException($this->accountRepository->getClassName(), $id);
        }

        return $this->getApiObject($account, $locale);
    }

    /**
     * Returns contacts by account id.
     *
     * @param int $id
     * @param string $locale
     * @param bool $onlyFetchMainAccounts
     *
     * @return array|null
     */
    public function findContactsByAccountId($id, $locale, $onlyFetchMainAccounts = false)
    {
        $contactsEntities = $this->contactRepository->findByAccountId(
            $id,
            null,
            false,
            $onlyFetchMainAccounts
        );

        if (!empty($contactsEntities)) {
            $contacts = [];
            foreach ($contactsEntities as $contact) {
                $contacts[] = new Contact($contact, $locale, $this->tagManager);
            }

            return $contacts;
        }

        return;
    }

    /**
     * Takes an account-entity and the id of a media and adds the media as the logo of the account.
     * TODO: handle logo adding differently and remove this method (or make it private).
     *
     * @param Account $account
     * @param int $mediaId
     */
    public function setLogo($account, $mediaId)
    {
        $media = $this->mediaManager->getEntityById($mediaId);
        $account->setLogo($media);
    }

    /**
     * Sets the medias of the given account to the given medias.
     * Currently associated medias are replaced.
     *
     * @param Account $account
     * @param $medias
     *
     * @throws EntityNotFoundException
     */
    public function setMedias(Account $account, $medias)
    {
        $mediaIds = array_map(
            function ($media) {
                return $media['id'];
            },
            $medias
        );

        $foundMedias = $this->mediaRepository->findById($mediaIds);
        $foundMediaIds = array_map(
            function ($mediaEntity) {
                return $mediaEntity->getId();
            },
            $foundMedias
        );

        if ($missingMediaIds = array_diff($mediaIds, $foundMediaIds)) {
            throw new EntityNotFoundException($this->mediaRepository->getClassName(), reset($missingMediaIds));
        }

        $account->getMedias()->clear();
        foreach ($foundMedias as $media) {
            $account->addMedia($media);
        }
    }

    /**
     * Returns all accounts.
     *
     * @param string $locale
     * @param null $filter
     *
     * @return array|null
     */
    public function findAll($locale, $filter = null)
    {
        if ($filter) {
            $accountEntities = $this->accountRepository->findByFilter($filter);
        } else {
            $accountEntities = $this->accountRepository->findAll();
        }

        if (!empty($accountEntities)) {
            $accounts = [];
            foreach ($accountEntities as $account) {
                $accounts[] = $this->getApiObject($account, $locale);
            }

            return $accounts;
        }

        return;
    }

    /**
     * Returns an api entity for an doctrine entity.
     *
     * @param Account $account
     * @param string $locale
     *
     * @return null|AccountApi
     */
    public function getAccount($account, $locale)
    {
        if ($account) {
            return $this->getApiObject($account, $locale);
        }

        return;
    }

    /**
     * {@inheritdoc}
     */
    public function deleteAllRelations($entity)
    {
        parent::deleteAllRelations($entity);
        $this->deleteBankAccounts($entity);
    }

    /**
     * Deletes (not just removes) all bank-accounts which are assigned to a contact.
     *
     * @param $entity
     */
    public function deleteBankAccounts($entity)
    {
        /** @var Account $entity */
        if ($entity->getBankAccounts()) {
            $this->deleteAllEntitiesOfCollection($entity->getBankAccounts());
        }
    }

    /**
     * {@inheritdoc}
     */
    public function findByFilters($filters, $page, $pageSize, $limit, $locale, $options = [])
    {
        $entities = $this->accountRepository->findByFilters($filters, $page, $pageSize, $limit, $locale, $options);

        return array_map(
            function ($contact) use ($locale) {
                return $this->getApiObject($contact, $locale);
            },
            $entities
        );
    }

    /**
     * Takes a account entity and a locale and returns the api object.
     *
     * @param Account $account
     * @param string $locale
     *
     * @return AccountApi
     */
    protected function getApiObject($account, $locale)
    {
        $apiObject = $this->accountFactory->createApiEntity($account, $locale);
        if ($account->getLogo()) {
            $apiLogo = $this->mediaManager->getById($account->getLogo()->getId(), $locale);
            $apiObject->setLogo($apiLogo);
        }

        return $apiObject;
    }
}
