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

use Doctrine\ORM\Tools\Pagination\Paginator;
use Sulu\Bundle\MediaBundle\Entity\CollectionRepositoryInterface;
use Sulu\Bundle\MediaBundle\Entity\CollectionType;
use Sulu\Bundle\MediaBundle\Entity\FileVersion;
use Sulu\Bundle\MediaBundle\Entity\FileVersionMeta;
use Sulu\Bundle\MediaBundle\Entity\MediaRepositoryInterface;
use Sulu\Bundle\MediaBundle\Media\Exception\CollectionNotFoundException;
use Sulu\Bundle\MediaBundle\Media\Exception\CollectionTypeNotFoundException;
use Sulu\Bundle\MediaBundle\Media\FormatManager\FormatManagerInterface;
use Sulu\Component\Rest\Exception\EntityNotFoundException;
use Sulu\Component\Rest\ListBuilder\Doctrine\FieldDescriptor\DoctrineJoinDescriptor;
use Sulu\Component\Rest\ListBuilder\Doctrine\FieldDescriptor\DoctrineFieldDescriptor;
use Sulu\Component\Security\UserRepositoryInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Sulu\Bundle\MediaBundle\Entity\Collection as CollectionEntity;
use Sulu\Bundle\MediaBundle\Api\Collection;

class DefaultCollectionManager implements CollectionManagerInterface
{
    private static $entityName = 'SuluMediaBundle:Collection';
    private static $entityCollectionType = 'SuluMediaBundle:Collection';
    private static $entityCollectionMeta = 'SuluMediaBUndle:CollectionMeta';
    private static $entityUser = 'SuluSecurityBundle:User';
    private static $entityContact = 'SuluContactBundle:Contact';

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
     * @var ObjectManager
     */
    private $em;

    /**
     * @var int
     */
    private $previewLimit;

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
        ObjectManager $em,
        $previewLimit,
        $collectionPreviewFormat
    )
    {
        $this->em = $em;
        $this->userRepository = $userRepository;
        $this->collectionRepository = $collectionRepository;
        $this->mediaRepository = $mediaRepository;
        $this->formatManager = $formatManager;
        $this->previewLimit = $previewLimit;
        $this->collectionPreviewFormat = $collectionPreviewFormat;

        $this->initializeFieldDescriptors();
    }

    /**
     * {@inheritdoc}
     */
    public function getById($id, $locale)
    {
        $collection = $this->collectionRepository->findCollectionById($id);
        if (!$collection) {
            throw new CollectionNotFoundException($id);
        }

        return $this->addPreviews(new Collection($collection, $locale));
    }

    /**
     * {@inheritdoc}
     */
    public function get($locale, $filter = array(), $limit = null, $offset = null, $sortBy = array())
    {
        $collectionEntities = $this->collectionRepository->findCollections($filter, $limit, $offset, $sortBy);
        $this->count = $collectionEntities instanceof Paginator ? $collectionEntities->count() : count($collectionEntities);
        $collections = [];
        foreach ($collectionEntities as $entity) {
            $collections[] =  $this->addPreviews(new Collection($entity, $locale));
        }

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
                )
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
                )
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
                )
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
                )
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
                )
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
        $this->fieldDescriptors['mediaNumber'] = new DoctrineFieldDescriptor(
            'mediaNumber',
            'mediaNumber',
            self::$entityName,
            'mediaNumber',
            array(),
            false,
            true,
            'count'
        );

        return $this->fieldDescriptors;
    }

    /**
     * {@inheritdoc}
     */
    public function getFieldDescriptors()
    {
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
     * Modified an exists collection
     * @param $data
     * @param $user
     * @return object|\Sulu\Bundle\MediaBundle\Entity\Collection
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
     * @return CollectionEntity
     */
    private function createCollection($data, $user)
    {
        $data['changer'] = $user;
        $data['creator'] = $user;
        $data['changed'] = new \DateTime();
        $data['created'] = new \DateTime();

        $collectionEntity = new CollectionEntity();
        $collection = new Collection($collectionEntity, $data['locale']);

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
     * Data can be set over by array
     * @param $collection
     * @param $data
     * @return $this
     */
    protected function setDataToCollection(Collection $collection, $data)
    {
        // set parent
        if (!empty($data['parent'])) {
            $collectionEntity = $this->collectionRepository->findCollectionById($data['parent']);
            $collection->setParent($collectionEntity); // set parent
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
                    case 'changed':
                        $collection->setChanged($value);
                        break;
                    case 'created':
                        $collection->setCreated($value);
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
     * @return CollectionType
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
     * Returns a user for a given user-id
     * @param $userId
     * @return \Sulu\Component\Security\UserInterface
     */
    protected function getUser($userId)
    {
        return $this->userRepository->findUserById($userId);
    }

    /**
     * @param Collection $collection
     * @return Collection
     */
    protected function addPreviews(Collection $collection)
    {
        return $collection->setPreviews(
            $this->getPreviews($collection->getId(), $collection->getLocale())
        );
    }

    /**
     * @param int $id
     * @param string $locale
     * @return array
     */
    protected function getPreviews($id, $locale)
    {
        $formats = array();

        $medias = $this->mediaRepository
            ->findMedia(array('collection' => $id), $this->previewLimit);

        foreach ($medias as $media) {
            foreach ($media->getFiles() as $file) {
                foreach ($file->getFileVersions() as $fileVersion) {
                    if ($fileVersion->getVersion() == $file->getVersion()) {
                        $format = $this->getPreviewsFromFileVersion($media->getId(), $fileVersion, $locale);
                        if (!empty($format)) {
                            $formats[] = $format;
                        }
                        break;
                    }
                }
                break;
            }
        }

        return $formats;
    }

    /**
     * @param int $mediaId
     * @param FileVersion $fileVersion
     * @param string $locale
     * @return array
     */
    protected function getPreviewsFromFileVersion($mediaId, $fileVersion, $locale)
    {
        $title = '';
        /**
         * @var FileVersionMeta $meta
         */
        foreach ($fileVersion->getMeta() as $key => $meta) {
            if ($meta->getLocale() == $locale) {
                $title = $meta->getTitle();
                break;
            } elseif ($key == 0) { // fallback title
                $title = $meta->getTitle();
            }
        }

        $mediaFormats = $this->formatManager->getFormats($mediaId, $fileVersion->getName(), $fileVersion->getStorageOptions(), $fileVersion->getVersion());

        foreach ($mediaFormats as $formatName => $formatUrl) {
            if ($formatName == $this->collectionPreviewFormat) {
                return array(
                    'url' => $formatUrl,
                    'title' => $title
                );
                break;
            }
        }

        return array();
    }
}
