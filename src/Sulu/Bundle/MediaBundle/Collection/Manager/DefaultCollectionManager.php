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

use Sulu\Bundle\MediaBundle\Entity\CollectionRepositoryInterface;
use Sulu\Bundle\MediaBundle\Entity\CollectionType;
use Sulu\Bundle\MediaBundle\Entity\FileVersion;
use Sulu\Bundle\MediaBundle\Entity\FileVersionMeta;
use Sulu\Bundle\MediaBundle\Entity\MediaRepositoryInterface;
use Sulu\Bundle\MediaBundle\Media\FormatManager\FormatManagerInterface;
use Sulu\Component\Rest\Exception\EntityNotFoundException;
use Sulu\Component\Rest\ListBuilder\FieldDescriptor\DoctrineFieldDescriptor;
use Sulu\Component\Security\UserRepositoryInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Sulu\Bundle\MediaBundle\Entity\Collection as CollectionEntity;
use Sulu\Bundle\MediaBundle\Api\Collection as CollectionWrapper;

class DefaultCollectionManager implements CollectionManagerInterface, CollectionFieldDescriptorInterface
{
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
     * TODO
     * @return DoctrineFieldDescriptor[]
     */
    private function initializeFieldDescriptors()
    {
        $fieldDescriptors = array();

        $this->fieldDescriptors = $fieldDescriptors;

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
    public function find($parent = null, $depth = null)
    {
        return $this->collectionRepository->findCollections($parent, $depth);
    }

    /**
     * {@inheritdoc}
     */
    public function findById($id)
    {
        return $this->collectionRepository->findCollectionById($id);
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
        $collectionEntity = $this->findById($data['id']);
        if (!$collectionEntity) {
            throw new EntityNotFoundException($collectionEntity, $data['id']);
        }

        $collectionEntity->setChanged(new \DateTime());
        $collectionEntity->setChanger($user);

        $collectionWrapper = $this->setDataToCollectionWrapper(
            $this->getApiObject($collectionEntity, $data['locale']),
            $data
        );

        $collectionEntity = $collectionWrapper->getEntity();
        $this->em->persist($collectionEntity);
        $this->em->flush();

        return $collectionEntity;
    }

    /**
     * @param $data
     * @param $user
     * @return CollectionEntity
     */
    private function createCollection($data, $user)
    {
        $collectionEntity = new CollectionEntity();
        $collectionEntity->setCreator($user);
        $collectionEntity->setChanger($user);
        $collectionEntity->setCreated(new \DateTime());
        $collectionEntity->setChanged(new \DateTime());

        $collectionWrapper = $this->setDataToCollectionWrapper(
            $this->getApiObject($collectionEntity, $data['locale']),
            $data
        );

        $collectionEntity = $collectionWrapper->getEntity();
        $this->em->persist($collectionEntity);
        $this->em->flush();

        return $collectionEntity;
    }

    /**
     * Data can be set over by array
     * @param $collectionWrapper
     * @param $data
     * @return $this
     */
    protected function setDataToCollectionWrapper(CollectionWrapper $collectionWrapper, $data)
    {
        foreach ($data as $key => $value) {
            if ($value) {
                switch ($key) {
                    case 'parent':
                        $value = $this->findById($value);
                        break;
                    case 'type':
                        $value = $this->getTypeById($value);
                        break;
                }
                $setDataMethod = 'set' . ucfirst($key);
                if (method_exists($collectionWrapper, $setDataMethod)) {
                    $collectionWrapper->$setDataMethod($value);
                }
            }
        }

        return $collectionWrapper;
    }

    /**
     * @param $typeId
     * @return CollectionType
     * @throws \Sulu\Component\Rest\Exception\EntityNotFoundException
     */
    protected function getTypeById($typeId)
    {
        $type = $this->em->getRepository('SuluMediaBundle:CollectionType')->find($typeId);
        if (!$type) {
            throw new EntityNotFoundException('SuluMediaBundle:CollectionType', $typeId);
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
            throw new EntityNotFoundException('SuluMediaBundle:Collection', $id);
        }

        $this->em->remove($collectionEntity);
        $this->em->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function getApiObject($collection, $locale)
    {
        if ($collection instanceof CollectionEntity) {
            return $this->addPreviews(new CollectionWrapper($collection, $locale));
        } else {
            return null;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getApiObjects($collections, $locale)
    {
        $arrReturn = [];
        foreach($collections as $collection) {
            array_push($arrReturn, $this->getApiObject($collection, $locale));
        }
        return $arrReturn;
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
     * @param CollectionWrapper $collectionWrapper
     * @return CollectionWrapper
     */
    protected function addPreviews(CollectionWrapper $collectionWrapper)
    {
        return $collectionWrapper->setPreviews(
            $this->getPreviews($collectionWrapper->getId(), $collectionWrapper->getLocale())
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
            ->findMedia($id, null, $this->previewLimit);

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

        $mediaFormats = $this->formatManager->getFormats($mediaId, $fileVersion->getName(), $fileVersion->getStorageOptions());

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
