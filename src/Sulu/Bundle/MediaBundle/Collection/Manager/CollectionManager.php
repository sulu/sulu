<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Collection\Manager;

use Doctrine\DBAL\DBALException;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Sulu\Bundle\MediaBundle\Api\Collection;
use Sulu\Bundle\MediaBundle\Entity\Collection as CollectionEntity;
use Sulu\Bundle\MediaBundle\Entity\CollectionInterface;
use Sulu\Bundle\MediaBundle\Entity\CollectionRepositoryInterface;
use Sulu\Bundle\MediaBundle\Entity\CollectionType;
use Sulu\Bundle\MediaBundle\Entity\FileVersion;
use Sulu\Bundle\MediaBundle\Entity\MediaRepositoryInterface;
use Sulu\Bundle\MediaBundle\Media\Exception\CollectionNotFoundException;
use Sulu\Bundle\MediaBundle\Media\Exception\CollectionTypeNotFoundException;
use Sulu\Bundle\MediaBundle\Media\FormatManager\FormatManagerInterface;
use Sulu\Component\Rest\ListBuilder\Doctrine\FieldDescriptor\DoctrineFieldDescriptor;
use Sulu\Component\Rest\ListBuilder\Doctrine\FieldDescriptor\DoctrineJoinDescriptor;
use Sulu\Component\Rest\ListBuilder\FieldDescriptorInterface;
use Sulu\Component\Security\Authentication\UserInterface;
use Sulu\Component\Security\Authentication\UserRepositoryInterface;
use Sulu\Component\Security\Authorization\PermissionTypes;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Default implementation of collection manager.
 */
class CollectionManager implements CollectionManagerInterface
{
    private static $entityName = 'SuluMediaBundle:Collection';

    private static $entityCollectionType = 'SuluMediaBundle:Collection';

    private static $entityCollectionMeta = 'SuluMediaBUndle:CollectionMeta';

    private static $entityUser = 'Sulu\Component\Security\Authentication\UserInterface';

    private static $entityContact = 'Sulu\Bundle\ContactBundle\Entity\ContactInterface';

    /**
     * @var CollectionRepositoryInterface
     */
    private $collectionRepository;

    /**
     * @var MediaRepositoryInterface
     */
    private $mediaRepository;

    /**
     * @var FormatManagerInterface
     */
    private $formatManager;

    /**
     * @var UserRepositoryInterface
     */
    private $userRepository;

    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * @var DoctrineFieldDescriptor[]
     */
    private $fieldDescriptors;

    /**
     * @var int
     */
    private $count;

    /**
     * @var int
     */
    private $collectionPreviewFormat;

    /**
     * @var array
     */
    private $permissions;

    public function __construct(
        CollectionRepositoryInterface $collectionRepository,
        MediaRepositoryInterface $mediaRepository,
        FormatManagerInterface $formatManager,
        UserRepositoryInterface $userRepository,
        EntityManager $em,
        TokenStorageInterface $tokenStorage = null,
        $collectionPreviewFormat,
        $permissions
    ) {
        $this->collectionRepository = $collectionRepository;
        $this->mediaRepository = $mediaRepository;
        $this->formatManager = $formatManager;
        $this->userRepository = $userRepository;
        $this->em = $em;
        $this->tokenStorage = $tokenStorage;
        $this->collectionPreviewFormat = $collectionPreviewFormat;
        $this->permissions = $permissions;
    }

    /**
     * {@inheritdoc}
     */
    public function getById($id, $locale, $depth = 0, $breadcrumb = false, $filter = [], $sortBy = [], $children = false)
    {
        $collectionEntity = $this->collectionRepository->findCollectionById($id);
        if (null === $collectionEntity) {
            throw new CollectionNotFoundException($id);
        }
        $filter['locale'] = $locale;
        $collectionChildren = null;
        if ($children) {
            $collectionChildren = $this->collectionRepository->findCollectionSet(
                $depth,
                $filter,
                $collectionEntity,
                $sortBy,
                $this->getCurrentUser(),
                $this->permissions[PermissionTypes::VIEW]
            );
        }

        $breadcrumbEntities = null;
        if ($breadcrumb) {
            $breadcrumbEntities = $this->collectionRepository->findCollectionBreadcrumbById($id);
        }

        return $this->getApiEntity($collectionEntity, $locale, $collectionChildren, $breadcrumbEntities);
    }

    /**
     * {@inheritdoc}
     */
    public function get($locale, $filter = [], $limit = null, $offset = null, $sortBy = [])
    {
        $collectionEntities = $this->collectionRepository->findCollections($filter, $limit, $offset, $sortBy);
        $this->count = $collectionEntities instanceof Paginator ?
            $collectionEntities->count() : count($collectionEntities);

        $collections = [];
        foreach ($collectionEntities as $entity) {
            $collections[] = $this->getApiEntity($entity, $locale);
        }

        return $collections;
    }

    /**
     * {@inheritdoc}
     */
    public function getByKey($key, $locale)
    {
        $collection = $this->collectionRepository->findCollectionByKey($key);

        if (!$collection) {
            return;
        }

        return $this->getApiEntity($collection, $locale);
    }

    /**
     * {@inheritdoc}
     */
    public function getTreeById($id, $locale)
    {
        $collectionSet = $this->collectionRepository->findTree($id, $locale);

        /** @var Collection[] $collections sorted by id */
        $collections = [];
        /** @var Collection[] $result collections without parent */
        $result = [];
        foreach ($collectionSet as $collection) {
            $apiEntity = new Collection($collection, $locale);
            $this->addPreview($apiEntity);

            $collections[$collection->getId()] = $apiEntity;

            if (null !== $collection->getParent()) {
                $collections[$collection->getParent()->getId()]->addChild($apiEntity);
            } else {
                $result[] = $apiEntity;
            }
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function getTree($locale, $offset, $limit, $search, $depth = 0, $sortBy = [], $systemCollections = true)
    {
        $filter = [
            'offset' => $offset,
            'limit' => $limit,
            'search' => $search,
            'locale' => $locale,
            'systemCollections' => $systemCollections,
        ];

        /** @var CollectionEntity[] $entities */
        $entities = $this->collectionRepository->findCollectionSet(
            $depth,
            $filter,
            null,
            $sortBy,
            $this->getCurrentUser(),
            $this->permissions[PermissionTypes::VIEW]
        );

        $collections = [];
        foreach ($entities as $entity) {
            if (null === $entity->getParent()) {
                $collections[] = $this->getApiEntity($entity, $locale, $entities);
            }
        }

        unset($filter['limit']);
        unset($filter['offset']);
        $this->count = $this->collectionRepository->countCollections($depth, $filter);

        return $collections;
    }

    /**
     * @return int
     */
    public function getCount()
    {
        return $this->count;
    }

    /**
     * @return DoctrineFieldDescriptor[]
     */
    private function initializeFieldDescriptors()
    {
        $fieldDescriptors = [];

        $this->fieldDescriptors = $fieldDescriptors;

        $this->fieldDescriptors['id'] = new DoctrineFieldDescriptor(
            'id',
            'id',
            self::$entityName,
            'id',
            [],
            FieldDescriptorInterface::VISIBILITY_NO,
            FieldDescriptorInterface::SEARCHABILITY_NO,
            'string',
            '50px'
        );
        $this->fieldDescriptors['type_name'] = new DoctrineFieldDescriptor(
            'name',
            'type_name',
            self::$entityCollectionType,
            'locale',
            [
                self::$entityCollectionType => new DoctrineJoinDescriptor(
                    self::$entityCollectionType,
                    self::$entityName . '.type'
                ),
            ],
            FieldDescriptorInterface::VISIBILITY_NO
        );
        $this->fieldDescriptors['title'] = new DoctrineFieldDescriptor(
            'title',
            'title',
            self::$entityCollectionMeta,
            'title',
            [
                self::$entityName => new DoctrineJoinDescriptor(
                    self::$entityCollectionMeta,
                    self::$entityName . '.meta'
                ),
            ],
            FieldDescriptorInterface::VISIBILITY_NO,
            FieldDescriptorInterface::SEARCHABILITY_YES,
            'title',
            '50px'
        );
        $this->fieldDescriptors['description'] = new DoctrineFieldDescriptor(
            'description',
            'description',
            self::$entityCollectionMeta,
            'description',
            [
                self::$entityName => new DoctrineJoinDescriptor(
                    self::$entityCollectionMeta,
                    self::$entityName . '.meta'
                ),
            ],
            FieldDescriptorInterface::VISIBILITY_NO,
            FieldDescriptorInterface::SEARCHABILITY_YES,
            'description'
        );
        $this->fieldDescriptors['changer'] = new DoctrineFieldDescriptor(
            'firstname',
            'changer',
            self::$entityContact,
            'changer',
            [
                self::$entityUser => new DoctrineJoinDescriptor(
                    self::$entityUser,
                    self::$entityName . '.changer'
                ),
                self::$entityContact => new DoctrineJoinDescriptor(
                    self::$entityContact,
                    self::$entityUser . '.contact'
                ),
            ],
            FieldDescriptorInterface::VISIBILITY_NO,
            FieldDescriptorInterface::SEARCHABILITY_NO
        );
        $this->fieldDescriptors['creator'] = new DoctrineFieldDescriptor(
            'firstname',
            'creator',
            self::$entityContact,
            'creator',
            [
                self::$entityUser => new DoctrineJoinDescriptor(
                    self::$entityUser,
                    self::$entityName . '.creator'
                ),
                self::$entityContact => new DoctrineJoinDescriptor(
                    self::$entityContact,
                    self::$entityUser . '.contact'
                ),
            ],
            FieldDescriptorInterface::VISIBILITY_NO,
            FieldDescriptorInterface::SEARCHABILITY_NO
        );
        $this->fieldDescriptors['thumbnails'] = new DoctrineFieldDescriptor(
            'thumbnails',
            'thumbnails',
            self::$entityName,
            'thumbnails',
            [],
            FieldDescriptorInterface::VISIBILITY_NO,
            FieldDescriptorInterface::SEARCHABILITY_NO,
            'thumbnails'
        );

        return $this->fieldDescriptors;
    }

    /**
     * {@inheritdoc}
     */
    public function getFieldDescriptors()
    {
        if (null === $this->fieldDescriptors) {
            $this->initializeFieldDescriptors();
        }

        return $this->fieldDescriptors;
    }

    /**
     * {@inheritdoc}
     */
    public function getFieldDescriptor($key)
    {
        return $this->fieldDescriptors[$key];
    }

    /**
     * {@inheritdoc}
     */
    public function save(array $data, ?int $userId, bool $breadcrumb = false)
    {
        if (isset($data['id'])) {
            $collection = $this->modifyCollection($data, $this->getUser($userId), $breadcrumb);
        } else {
            $collection = $this->createCollection($data, $this->getUser($userId), $breadcrumb);
        }

        if ($breadcrumb) {
            $breadcrumbEntities = $this->collectionRepository->findCollectionBreadcrumbById($collection->getId());
            $this->setBreadcrumbToCollection($collection, $data['locale'], $breadcrumbEntities);
        }

        return $collection;
    }

    private function modifyCollection($data, $user, $breadcrumb): Collection
    {
        $collection = $this->getById($data['id'], $data['locale']);
        $data['changer'] = $user;
        $data['changed'] = new \DateTime();

        $collection = $this->setDataToCollection(
            $collection,
            $data
        );

        $collectionEntity = $collection->getEntity();
        $this->em->persist($collectionEntity);
        $this->em->flush();

        return $collection;
    }

    private function createCollection($data, $user, $breadcrumb): Collection
    {
        $data['changer'] = $user;
        $data['creator'] = $user;
        $data['changed'] = new \DateTime();
        $data['created'] = new \DateTime();

        $collectionEntity = new CollectionEntity();
        $collection = $this->getApiEntity($collectionEntity, $data['locale']);

        $collection = $this->setDataToCollection($collection, $data);

        /** @var CollectionEntity $collectionEntity */
        $collectionEntity = $collection->getEntity();
        $collectionEntity->setDefaultMeta($collectionEntity->getMeta()->first());
        $this->em->persist($collectionEntity);
        $this->em->flush();

        return $collection;
    }

    /**
     * Data can be set over by array.
     *
     * @param Collection $collection
     * @param array $data
     *
     * @return Collection
     */
    protected function setDataToCollection(Collection $collection, $data)
    {
        // set parent
        if (!empty($data['parent'])) {
            $collectionEntity = $this->collectionRepository->findCollectionById($data['parent']);
            $collection->setParent($this->getApiEntity($collectionEntity, $data['locale']));
        }

        // set other data
        foreach ($data as $attribute => $value) {
            if ($value) {
                switch ($attribute) {
                    case 'title':
                        $collection->setTitle($value);
                        break;
                    case 'key':
                        $collection->setKey($value);
                        break;
                    case 'description':
                        $collection->setDescription($value);
                        break;
                    case 'style':
                        $collection->setStyle($value);
                        break;
                    case 'type':
                        if (!isset($value['id'])) {
                            break;
                        }
                        $type = $this->getTypeById($value['id']);
                        $collection->setType($type);
                        break;
                    case 'changer':
                        $collection->setChanger($value);
                        break;
                    case 'creator':
                        $collection->setCreator($value);
                        break;
                    case 'properties':
                        $collection->setProperties($value);
                        break;
                }
            }
        }

        return $collection;
    }

    /**
     * Sets the given breadcrumb entities as breadcrum on the given collection.
     *
     * @return Collection
     */
    protected function setBreadcrumbToCollection(Collection $collection, $locale, $breadcrumbEntities)
    {
        $breadcrumbApiEntities = [];
        foreach ($breadcrumbEntities as $entity) {
            $breadcrumbApiEntities[] = $this->getApiEntity($entity, $locale);
        }
        $collection->setBreadcrumb($breadcrumbApiEntities);

        return $collection;
    }

    /**
     * @param $typeId
     *
     * @return CollectionType
     *
     * @throws CollectionTypeNotFoundException
     */
    protected function getTypeById($typeId)
    {
        /** @var CollectionType $type */
        $type = $this->em->getRepository('SuluMediaBundle:CollectionType')->find($typeId);
        if (!$type) {
            throw new CollectionTypeNotFoundException('Collection Type with the ID ' . $typeId . ' not found');
        }

        return $type;
    }

    /**
     * {@inheritdoc}
     */
    public function delete($id)
    {
        $collectionEntity = $this->collectionRepository->findCollectionById($id);

        if (!$collectionEntity) {
            throw new CollectionNotFoundException($id);
        }

        $this->em->remove($collectionEntity);
        $this->em->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function move($id, $locale, $destinationId = null)
    {
        try {
            $collectionEntity = $this->collectionRepository->findCollectionById($id);

            if (null === $collectionEntity) {
                throw new CollectionNotFoundException($id);
            }

            $destinationEntity = null;
            if (null !== $destinationId) {
                $destinationEntity = $this->collectionRepository->findCollectionById($destinationId);
            }

            $collectionEntity->setParent($destinationEntity);
            $this->em->flush();

            return $this->getApiEntity($collectionEntity, $locale);
        } catch (DBALException $ex) {
            throw new CollectionNotFoundException($destinationId);
        }
    }

    /**
     * Returns a user for a given user-id.
     *
     * @param $userId
     *
     * @return \Sulu\Component\Security\Authentication\UserInterface
     */
    protected function getUser($userId)
    {
        return $this->userRepository->findUserById($userId);
    }

    /**
     * @param Collection $collection
     *
     * @return Collection
     */
    protected function addPreview(Collection $collection)
    {
        return $collection->setPreview(
            $this->getPreview($collection->getId(), $collection->getLocale())
        );
    }

    /**
     * @param int $id
     * @param string $locale
     *
     * @return array
     */
    protected function getPreview($id, $locale)
    {
        $medias = $this->mediaRepository
            ->findMedia(['collection' => $id, 'paginator' => false], 1);

        if (count($medias) > 0) {
            $media = $medias[0];
            foreach ($media->getFiles() as $file) {
                foreach ($file->getFileVersions() as $fileVersion) {
                    if ($fileVersion->getVersion() == $file->getVersion()) {
                        $format = $this->getPreviewsFromFileVersion($media->getId(), $fileVersion, $locale);

                        if (!empty($format)) {
                            return $format;
                        }
                    }
                }
            }
        }

        return;
    }

    /**
     * @param int $mediaId
     * @param FileVersion $fileVersion
     * @param string $locale
     *
     * @return array
     */
    protected function getPreviewsFromFileVersion($mediaId, $fileVersion, $locale)
    {
        $title = '';
        /*
         * @var FileVersionMeta
         */
        foreach ($fileVersion->getMeta() as $key => $meta) {
            if ($meta->getLocale() == $locale) {
                $title = $meta->getTitle();
                break;
            } elseif (0 == $key) { // fallback title
                $title = $meta->getTitle();
            }
        }

        $mediaFormats = $this->formatManager->getFormats(
            $mediaId,
            $fileVersion->getName(),
            $fileVersion->getStorageOptions(),
            $fileVersion->getVersion(),
            $fileVersion->getSubVersion(),
            $fileVersion->getMimeType()
        );

        foreach ($mediaFormats as $formatName => $formatUrl) {
            if ($formatName == $this->collectionPreviewFormat) {
                return [
                    'url' => $formatUrl,
                    'title' => $title,
                ];
            }
        }

        return [];
    }

    /**
     * Prepare an api entity.
     *
     * @param CollectionInterface $entity
     * @param string $locale
     * @param CollectionEntity[] $entities nested set
     * @param array $breadcrumbEntities
     *
     * @return Collection
     */
    protected function getApiEntity(CollectionInterface $entity, $locale, $entities = null, $breadcrumbEntities = null)
    {
        $apiEntity = new Collection($entity, $locale);

        $children = null;

        if (null !== $entities) {
            $children = [];
            foreach ($entities as $possibleChild) {
                if (null !== ($parent = $possibleChild->getParent()) && $parent->getId() === $entity->getId()) {
                    $children[] = $this->getApiEntity($possibleChild, $locale, $entities);
                }
            }
        }

        $apiEntity->setChildren($children);
        if (null !== $entity->getParent()) {
            $apiEntity->setParent($this->getApiEntity($entity->getParent(), $locale));
        }

        if (null !== $breadcrumbEntities) {
            $this->setBreadcrumbToCollection($apiEntity, $locale, $breadcrumbEntities);
        }

        if ($entity && $entity->getId()) {
            $apiEntity->setMediaCount($this->collectionRepository->countMedia($entity));
            $apiEntity->setSubCollectionCount($this->collectionRepository->countSubCollections($entity));
        }

        return $this->addPreview($apiEntity);
    }

    /**
     * Returns the current user from the token storage.
     *
     * @return UserInterface|null
     */
    protected function getCurrentUser()
    {
        if ($this->tokenStorage && ($token = $this->tokenStorage->getToken())) {
            $user = $token->getUser();

            if ($user instanceof UserInterface) {
                return $user;
            }
        }

        return;
    }
}
