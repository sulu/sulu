<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Media\Manager;

use Doctrine\Common\Persistence\ObjectManager;

use Sulu\Bundle\MediaBundle\Entity\MediaRepositoryInterface;
use Sulu\Bundle\MediaBundle\Media\FormatCache\FormatCacheInterface;
use Sulu\Bundle\MediaBundle\Media\FormatManager\FormatManagerInterface;
use Sulu\Bundle\TagBundle\Entity\Tag;
use Sulu\Component\Rest\Exception\EntityNotFoundException;
use Sulu\Bundle\MediaBundle\Entity\File;
use Sulu\Bundle\MediaBundle\Entity\FileVersion;
use Sulu\Bundle\MediaBundle\Entity\FileVersionContentLanguage;
use Sulu\Bundle\MediaBundle\Entity\FileVersionMeta;
use Sulu\Bundle\MediaBundle\Entity\CollectionRepository;
use Sulu\Bundle\MediaBundle\Entity\FileVersionPublishLanguage;
use Sulu\Bundle\MediaBundle\Entity\Media;
use Sulu\Bundle\MediaBundle\Entity\MediaRepository;
use Sulu\Bundle\MediaBundle\Media\Exception\CollectionNotFoundException;
use Sulu\Bundle\MediaBundle\Media\Exception\FileVersionNotFoundException;
use Sulu\Bundle\MediaBundle\Media\Exception\InvalidFileException;
use Sulu\Bundle\MediaBundle\Media\Exception\InvalidMediaTypeException;
use Sulu\Bundle\MediaBundle\Media\Storage\StorageInterface;
use Sulu\Bundle\MediaBundle\Media\FileValidator\FileValidatorInterface;
use Sulu\Component\Security\UserInterface;
use Sulu\Component\Security\UserRepositoryInterface;
use DateTime;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Sulu\Bundle\MediaBundle\Entity\Media as MediaEntity;
use Sulu\Bundle\MediaBundle\Api\Media as MediaWrapper;

/**
 * @package Sulu\Bundle\MediaBundle\Media\Manager
 */
class DefaultMediaManager implements MediaManagerInterface
{
    /**
     * The repository for communication with the database
     * @var MediaRepositoryInterface
     */
    private $mediaRepository;

    /**
     * The repository for communication with the database
     * @var CollectionRepository
     */
    private $collectionRepository;

    /**
     * @var ObjectManager
     */
    private $em;

    /**
     * @var FileValidatorInterface
     */
    private $validator;

    /**
     * @var FormatManagerInterface
     */
    private $formatManager;

    /**
     * @var StorageInterface
     */
    private $storage;

    /**
     * @var FormatCacheInterface
     */
    private $formatCache;

    /**
     * @var UserRepositoryInterface
     */
    private $userRepository;

    /**
     * @var int
     */
    private $maxFileSize;

    /**
     * @var array
     */
    private $blockedMimeTypes;

    /**
     * @var array
     */
    private $mediaTypes;

    /**
     * @param MediaRepositoryInterface $mediaRepository
     * @param CollectionRepository $collectionRepository
     * @param UserRepositoryInterface $userRepository
     * @param ObjectManager $em
     * @param StorageInterface $storage
     * @param FormatCacheInterface $formatCache
     * @param FileValidatorInterface $validator
     * @param FormatManagerInterface $formatManager
     * @param string $maxFileSize
     * @param array $blockedMimeTypes
     * @param array $mediaTypes
     */
    public function __construct(
        MediaRepositoryInterface $mediaRepository,
        CollectionRepository $collectionRepository,
        UserRepositoryInterface $userRepository,
        ObjectManager $em,
        StorageInterface $storage,
        FormatCacheInterface $formatCache,
        FileValidatorInterface $validator,
        FormatManagerInterface $formatManager,
        $maxFileSize,
        $blockedMimeTypes,
        $mediaTypes
    ) {
        $this->mediaRepository = $mediaRepository;
        $this->collectionRepository = $collectionRepository;
        $this->em = $em;
        $this->userRepository = $userRepository;
        $this->storage = $storage;
        $this->formatCache = $formatCache;
        $this->validator = $validator;
        $this->formatManager = $formatManager;
        $this->maxFileSize = $maxFileSize;
        $this->blockedMimeTypes = $blockedMimeTypes;
        $this->mediaTypes = $mediaTypes;
    }

    /**
     * {@inheritdoc}
     */
    public function find($collection = null, $ids = null, $limit = null)
    {
        return $this->mediaRepository->findMedia($collection, $ids, $limit);
    }

    /**
     * {@inheritdoc}
     */
    public function findById($id)
    {
        return $this->mediaRepository->findMediaById($id);
    }

    /**
     * {@inheritdoc}
     */
    public function save($uploadedFile, $data, $userId)
    {
        if (isset($data['id'])) {
            return $this->modifyMedia($uploadedFile, $data, $this->getUser($userId));
        } else {
            return $this->createMedia($uploadedFile, $data, $this->getUser($userId));
        }
    }

    /**
     * Modified an exists media
     * @param UploadedFile $uploadedFile
     * @param $data
     * @param $user
     * @return object|Media
     * @throws \Sulu\Component\Rest\Exception\EntityNotFoundException
     * @throws \Sulu\Bundle\MediaBundle\Media\Exception\FileVersionNotFoundException
     */
    private function modifyMedia($uploadedFile, $data, $user)
    {

        $mediaEntity = $this->findById($data['id']);
        if (!$mediaEntity) {
            throw new EntityNotFoundException($mediaEntity, $data['id']);
        }

        $mediaEntity->setChanged(new \DateTime());
        $mediaEntity->setChanger($user);


        /**
         * @var File $file
         */
        $file = $mediaEntity->getFiles()[0]; // currently a media can only have one file

        $file->setChanged(new Datetime());
        $file->setChanger($user);

        $version = $file->getVersion();

        $fileName = null;
        $oldStorageOptions = null;

        /**
         * @var FileVersion $fileVersion
         */
        foreach ($file->getFileVersions() as $fileVersion) {
            if ($version == $file->getVersion()) {
                $fileName = $fileVersion->getName();
                $oldStorageOptions = $fileVersion->getStorageOptions();

                // delete old fileversion from cache
                $this->formatCache->purge($mediaEntity->getId(), $fileVersion->getName(), $fileVersion->getStorageOptions());
                break;
            }
        }

        if (!$fileName) {
            throw new FileVersionNotFoundException ('Actual Version not found(' . $version . ')');
        }

        if ($uploadedFile) {
            // new uploaded file
            $version++;
            $this->validator->validate($uploadedFile);

            $data['storageOptions'] = $this->storage->save(
                $uploadedFile->getPathname(),
                $uploadedFile->getFilename(),
                $version,
                $oldStorageOptions
            );
            $data['name'] = $uploadedFile->getClientOriginalName();
            $data['size'] = $uploadedFile->getSize();

            $fileVersion = new FileVersion();
            $fileVersion->setChanged(new Datetime());
            $fileVersion->setCreated(new Datetime());
            $fileVersion->setChanger($user);
            $fileVersion->setCreator($user);
            $file->setVersion($version);
            $fileVersion->setVersion($version);
            $fileVersion->setFile($file);
            $file->addFileVersion($fileVersion);
        }

        $mediaWrapper = $this->setDataToMediaWrapper(
            $this->getApiObject($mediaEntity, $data['locale']),
            $data
        );

        $mediaEntity = $mediaWrapper->getEntity();
        $this->em->persist($mediaEntity);
        $this->em->flush();

        return $mediaEntity;
    }

    /**
     * Create a new media
     * @param $uploadedFile
     * @param $data
     * @param $user
     * @return MediaEntity
     * @throws \Sulu\Bundle\MediaBundle\Media\Exception\InvalidFileException
     */
    private function createMedia($uploadedFile, $data, $user)
    {
        if (!($uploadedFile instanceof UploadedFile)) {
            throw new InvalidFileException('given uploadfile is not of instance UploadFile');
        }

        $this->validator->validate($uploadedFile);

        $data['storageOptions'] = $this->storage->save($uploadedFile->getPathname(), $uploadedFile->getClientOriginalName(), 1);
        $data['name'] = $uploadedFile->getClientOriginalName();
        $data['size'] = $uploadedFile->getSize();
        $data['type'] = $this->getMediaType($uploadedFile);

        $mediaEntity = new MediaEntity();
        $mediaEntity->setCreator($user);
        $mediaEntity->setChanger($user);
        $mediaEntity->setCreated(new \DateTime());
        $mediaEntity->setChanged(new \DateTime());

        $file = new File();
        $file->setCreator($user);
        $file->setChanger($user);
        $file->setCreated(new \DateTime());
        $file->setChanged(new \DateTime());
        $file->setMedia($mediaEntity);

        $fileVersion = new FileVersion();
        $fileVersion->setCreator($user);
        $fileVersion->setChanger($user);
        $fileVersion->setCreated(new \DateTime());
        $fileVersion->setChanged(new \DateTime());
        $fileVersion->setFile($file);

        $file->addFileVersion($fileVersion);
        $mediaEntity->addFile($file);

        $collectionWrapper = $this->setDataToMediaWrapper(
            $this->getApiObject($mediaEntity, $data['locale']),
            $data
        );

        $collectionEntity = $collectionWrapper->getEntity();
        $this->em->persist($collectionEntity);
        $this->em->flush();

        return $collectionEntity;
    }

    /**
     * @param UploadedFile|null $uploadedFile
     * @return object
     */
    protected function getMediaType(UploadedFile $uploadedFile)
    {
        $extension = $uploadedFile->getExtension();
        $id = null;
        foreach ($this->mediaTypes as $mediaType) {
            if (in_array($extension, $mediaType['extensions']) || in_array('*', $mediaType['extensions'])) {
                $id = $mediaType['id'];
            }
        }

        return $this->em->getRepository('SuluMediaBundle:MediaType')->find($id);
    }

    /**
     * Data can be set over by array
     * @param $mediaWrapper
     * @param $data
     * @return MediaWrapper
     */
    protected function setDataToMediaWrapper(MediaWrapper $mediaWrapper, $data)
    {
        foreach ($data as $key => $value) {
            if ($value) {
                switch ($key) {
                    case 'collection':
                        $value = $this->getCollectionById($value);
                        break;
                }
                $setDataMethod = 'set' . ucfirst($key);
                if (method_exists($mediaWrapper, $setDataMethod)) {
                    $mediaWrapper->$setDataMethod($value);
                }
            }
        }

        return $mediaWrapper;
    }

    /**
     * @param $collectionId
     * @return object
     * @throws \Sulu\Component\Rest\Exception\EntityNotFoundException
     */
    protected function getCollectionById($collectionId)
    {
        $collection = $this->collectionRepository->find($collectionId);
        if (!$collection) {
            throw new EntityNotFoundException('SuluMediaBundle:Collection', $collectionId);
        }
        return $collection;
    }

    /**
     * {@inheritdoc}
     */
    public function delete($id)
    {
        $mediaEntity = $this->mediaRepository->findMediaById($id);

        if (!$mediaEntity) {
            throw new EntityNotFoundException('SuluMediaBundle:Collection', $id);
        }

        $this->em->remove($mediaEntity);
        $this->em->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function getApiObject($media, $locale)
    {
        if ($media instanceof MediaEntity) {
            return $this->addFormatsAndUrl(new MediaWrapper($media, $locale));
        } else {
            return null;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getApiObjects($media, $locale)
    {
        $arrReturn = [];
        foreach($media as $mediaEntity) {
            array_push($arrReturn, $this->getApiObject($mediaEntity, $locale));
        }
        return $arrReturn;
    }

    /**
     * @param MediaWrapper $mediaWrapper
     * @return MediaWrapper
     */
    protected function addFormatsAndUrl(MediaWrapper $mediaWrapper)
    {
        $mediaWrapper->setFormats(
            $this->getFormats($mediaWrapper->getId(), $mediaWrapper->getName(), $mediaWrapper->getStorageOptions())
        );

        $mediaWrapper->setUrl(
            $this->getUrl($mediaWrapper->getId(), $mediaWrapper->getVersion(), $mediaWrapper->getStorageOptions())
        );

        return $mediaWrapper;
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
     * @param $id
     * @param $name
     * @param $storageOptions
     * @return mixed
     */
    public function getFormats($id, $name, $storageOptions)
    {
        return $this->formatManager->getFormats($id, $name, $storageOptions);
    }

    /**
     * @param $id
     * @param $version
     * @param $storageOptions
     * @return mixed
     */
    public function getUrl($id, $version, $storageOptions)
    {
        return $this->formatManager->getOriginal($id, $version, $storageOptions);
    }


}
