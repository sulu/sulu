<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
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
use Sulu\Bundle\MediaBundle\Entity\FileVersionMeta;
use Sulu\Bundle\MediaBundle\Entity\MediaRepositoryInterface;
use Sulu\Bundle\MediaBundle\Media\Exception\CollectionNotFoundException;
use Sulu\Bundle\MediaBundle\Media\Exception\CollectionTypeNotFoundException;
use Sulu\Bundle\MediaBundle\Media\FormatManager\FormatManagerInterface;
use Sulu\Component\Rest\ListBuilder\Doctrine\FieldDescriptor\DoctrineFieldDescriptor;
use Sulu\Component\Rest\ListBuilder\Doctrine\FieldDescriptor\DoctrineJoinDescriptor;
use Sulu\Component\Security\Authentication\UserRepositoryInterface;

/**
 * Default implementation of collection manager.
 */
class CollectionManager implements CollectionManagerInterface
{
    private static $entityName = 'SuluMediaBundle:Collection';
    private static $entityCollectionType = 'SuluMediaBundle:Collection';
    private static $entityCollectionMeta = 'SuluMediaBUndle:CollectionMeta';
    private static $entityUser = 'Sulu\Component\Security\Authentication\UserInterface';
    private static $entityContact = 'Sulu\Component\Contact\Model\ContactInterface';

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

    public function __construct(
        CollectionRepositoryInterface $collectionRepository,
        MediaRepositoryInterface $mediaRepository,
        FormatManagerInterface $formatManager,
        UserRepositoryInterface $userRepository,
        EntityManager $em,
        $collectionPreviewFormat
    ) {
        $this->em = $em;
        $this->userRepository = $userRepository;
        $this->collectionRepository = $collectionRepository;
        $this->mediaRepository = $mediaRepository;
        $this->formatManager = $formatManager;
        $this->collectionPreviewFormat = $collectionPreviewFormat;
    }

    /**
     * {@inheritdoc}
     */
    public function getById($id, $locale, $depth = 0, $breadcrumb = false, $filter = array(), $sortBy = array())
    {
        $collectionEntity = $this->collectionRepository->findCollectionById($id);
        if ($collectionEntity === null) {
            throw new CollectionNotFoundException($id);
        }
        $filter['locale'] = $locale;
        $collectionChildren = $this->collectionRepository->findCollectionSet(
            $depth,
            $filter,
            $collectionEntity,
            $sortBy
        );

        $breadcrumbEntities = null;
        if ($breadcrumb) {
            $breadcrumbEntities = $this->collectionRepository->findCollectionBreadcrumbById($id);
        }

        return $this->getApiEntity($collectionEntity, $locale, $collectionChildren, $breadcrumbEntities);
    }

    /**
     * {@inheritdoc}
     */
    public function get($locale, $filter = array(), $limit = null, $offset = null, $sortBy = array())
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
    public function getTree($locale, $offset, $limit, $search, $depth = 0, $sortBy = array())
    {
        /** @var Paginator $collectionSet */
        $collectionSet = $this->collectionRepository->findCollectionSet(
            $depth,
            array('offset' => $offset, 'limit' => $limit, 'search' => $search, 'locale' => $locale),
            null,
            $sortBy
        );

        $collections = [];
        /** @var CollectionEntity[] $entities */
        $entities = iterator_to_array($collectionSet);
        foreach ($entities as $entity) {
            if ($entity->getParent() === null) {
                $collections[] = $this->getApiEntity($entity, $locale, $entities);
            }
        }

        $this->count = $collectionSet->count();

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
        $fieldDescriptors = array();

        $this->fieldDescriptors = $fieldDescriptors;

        $this->fieldDescriptors['id'] = new DoctrineFieldDescriptor(
            'id',
            'id',
            self::$entityName,
            'id',
            array(),
            true,
            false,
            '',
            '50px'
        );
        $this->fieldDescriptors['type_name'] = new DoctrineFieldDescriptor(
            'name',
            'type_name',
            self::$entityCollectionType,
            'locale',
            array(
                self::$entityCollectionType => new DoctrineJoinDescriptor(
                    self::$entityCollectionType,
                    self::$entityName . '.type'
                ),
            ),
            true,
            false
        );
        $this->fieldDescriptors['title'] = new DoctrineFieldDescriptor(
            'title',
            'title',
            self::$entityCollectionMeta,
            'title',
            array(
                self::$entityName => new DoctrineJoinDescriptor(
                    self::$entityCollectionMeta,
                    self::$entityName . '.meta'
                ),
            ),
            false,
            true,
            'title',
            '50px'
        );
        $this->fieldDescriptors['description'] = new DoctrineFieldDescriptor(
            'description',
            'description',
            self::$entityCollectionMeta,
            'description',
            array(
                self::$entityName => new DoctrineJoinDescriptor(
                    self::$entityCollectionMeta,
                    self::$entityName . '.meta'
                ),
            ),
            true,
            false,
            'description'
        );
        $this->fieldDescriptors['changer'] = new DoctrineFieldDescriptor(
            'firstname',
            'changer',
            self::$entityContact,
            'changer',
            array(
                self::$entityUser => new DoctrineJoinDescriptor(
                    self::$entityUser,
                    self::$entityName . '.changer'
                ),
                self::$entityContact => new DoctrineJoinDescriptor(
                    self::$entityContact,
                    self::$entityUser . '.contact'
                ),
            ),
            true,
            false
        );
        $this->fieldDescriptors['creator'] = new DoctrineFieldDescriptor(
            'firstname',
            'creator',
            self::$entityContact,
            'creator',
            array(
                self::$entityUser => new DoctrineJoinDescriptor(
                    self::$entityUser,
                    self::$entityName . '.creator'
                ),
                self::$entityContact => new DoctrineJoinDescriptor(
                    self::$entityContact,
                    self::$entityUser . '.contact'
                ),
            ),
            true,
            false
        );
        $this->fieldDescriptors['thumbnails'] = new DoctrineFieldDescriptor(
            'thumbnails',
            'thumbnails',
            self::$entityName,
            'thumbnails',
            array(),
            false,
            true,
            'thumbnails'
        );

        return $this->fieldDescriptors;
    }

    /**
     * {@inheritdoc}
     */
    public function getFieldDescriptors()
    {
        if ($this->fieldDescriptors === null) {
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
    public function save($data, $userId)
    {
        if (isset($data['id'])) {
            return $this->modifyCollection($data, $this->getUser($userId));
        } else {
            return $this->createCollection($data, $this->getUser($userId));
        }
    }

    /**
     * Modified an exists collection.
     *
     * @param $data
     * @param $user
     *
     * @return Collection
     *
     * @throws \Sulu\Component\Rest\Exception\EntityNotFoundException
     */
    private function modifyCollection($data, $user)
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

    /**
     * @param $data
     * @param $user
     *
     * @return Collection
     */
    private function createCollection($data, $user)
    {
        $data['changer'] = $user;
        $data['creator'] = $user;
        $data['changed'] = new \DateTime();
        $data['created'] = new \DateTime();

        $collectionEntity = new CollectionEntity();
        $collection = $this->getApiEntity($collectionEntity, $data['locale']);

        $collection = $this->setDataToCollection(
            $collection,
            $data
        );

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
            $collection->setParent($this->getApiEntity($collectionEntity, $data['locale'])); // set parent
        } else {
            $collection->setParent(null); // is collection in root
        }

        // set other data
        foreach ($data as $attribute => $value) {
            if ($value) {
                switch ($attribute) {
                    case 'title':
                        $collection->setTitle($value);
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

            if ($collectionEntity === null) {
                throw new CollectionNotFoundException($id);
            }

            $destinationEntity = null;
            if ($destinationId !== null) {
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
        $media = null;
        $medias = $this->mediaRepository
            ->findMedia(array('collection' => $id, 'paginator' => false), 1);

        if (count($medias) > 0) {
            $media = $medias[0];
            foreach ($media->getFiles() as $file) {
                foreach ($file->getFileVersions() as $fileVersion) {
                    if ($fileVersion->getVersion() == $file->getVersion()) {
                        $format = $this->getPreviewsFromFileVersion($media->getId(), $fileVersion, $locale);
                        if (!empty($format)) {
                            $media = $format;
                        }
                        break;
                    }
                }
                break;
            }
        }

        return $media;
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
        /**
         * @var FileVersionMeta
         */
        foreach ($fileVersion->getMeta() as $key => $meta) {
            if ($meta->getLocale() == $locale) {
                $title = $meta->getTitle();
                break;
            } elseif ($key == 0) { // fallback title
                $title = $meta->getTitle();
            }
        }

        $mediaFormats = $this->formatManager->getFormats(
            $mediaId,
            $fileVersion->getName(),
            $fileVersion->getStorageOptions(),
            $fileVersion->getVersion(),
            $fileVersion->getMimeType()
        );

        foreach ($mediaFormats as $formatName => $formatUrl) {
            if ($formatName == $this->collectionPreviewFormat) {
                return array(
                    'url' => $formatUrl,
                    'title' => $title,
                );
                break;
            }
        }

        return array();
    }

    /**
     * prepare an api entity.
     *
     * @param CollectionEntity $entity
     * @param string $locale
     * @param CollectionEntity[] $entities nested set
     * @param array $breadcrumbEntities
     *
     * @return Collection
     */
    protected function getApiEntity(CollectionInterface $entity, $locale, $entities = null, $breadcrumbEntities = null)
    {
        $apiEntity = new Collection($entity, $locale);

        $children = array();

        if ($entities !== null) {
            foreach ($entities as $possibleChild) {
                if (($parent = $possibleChild->getParent()) !== null && $parent->getId() === $entity->getId()) {
                    $children[] = $this->getApiEntity($possibleChild, $locale, $entities);
                }
            }
        }

        $apiEntity->setChildren($children);
        if ($entity->getParent() !== null) {
            $apiEntity->setParent($this->getApiEntity($entity->getParent(), $locale));
        }

        if ($breadcrumbEntities !== null) {
            $breadcrumbApiEntities = array();
            foreach ($breadcrumbEntities as $entity) {
                $breadcrumbApiEntities[] = $this->getApiEntity($entity, $locale);
            }
            $apiEntity->setBreadcrumb($breadcrumbApiEntities);
        }

        return $this->addPreview($apiEntity);
    }
}
