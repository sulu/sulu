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
use Sulu\Bundle\MediaBundle\Entity\File;
use Sulu\Bundle\MediaBundle\Entity\FileVersion;
use Sulu\Bundle\MediaBundle\Entity\FileVersionContentLanguage;
use Sulu\Bundle\MediaBundle\Entity\FileVersionMeta;
use Sulu\Bundle\MediaBundle\Entity\CollectionRepository;
use Sulu\Bundle\MediaBundle\Entity\Media;
use Sulu\Bundle\MediaBundle\Entity\MediaRepository;
use Sulu\Bundle\MediaBundle\Media\Exception\CollectionNotFoundException;
use Sulu\Bundle\MediaBundle\Media\Exception\FileVersionNotFoundException;
use Sulu\Bundle\MediaBundle\Media\Exception\UploadFileValidationException;
use Sulu\Bundle\MediaBundle\Media\Storage\StorageInterface;
use Sulu\Bundle\MediaBundle\Media\Validator\FileValidatorInterface;
use Sulu\Component\Security\UserRepositoryInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class DefaultMediaManager implements MediaManagerInterface
{
    /**
     * The repository for communication with the database
     * @var MediaRepository
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
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @var FileValidatorInterface
     */
    private $validator;

    /**
     * @var StorageInterface
     */
    private $storage;

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
     * @param MediaRepository $mediaRepository
     * @param CollectionRepository $collectionRepository
     * @param UserRepositoryInterface $userRepository
     * @param ObjectManager $em
     * @param EventDispatcherInterface $eventDispatcher
     * @param StorageInterface $storage
     * @param FileValidatorInterface $validator
     * @param $maxFileSize
     * @param $blockedMimeTypes
     */
    public function __construct(
        MediaRepository $mediaRepository,
        CollectionRepository $collectionRepository,
        UserRepositoryInterface $userRepository,
        ObjectManager $em,
        EventDispatcherInterface $eventDispatcher,
        StorageInterface $storage,
        FileValidatorInterface $validator,
        $maxFileSize,
        $blockedMimeTypes
    )
    {
        $this->mediaRepository = $mediaRepository;
        $this->collectionRepository = $collectionRepository;
        $this->em = $em;
        $this->eventDispatcher = $eventDispatcher;
        $this->userRepository = $userRepository;
        $this->storage = $storage;
        $this->validator = $validator;
        $this->maxFileSize = $maxFileSize;
        $this->blockedMimeTypes = $blockedMimeTypes;
    }

    /**
     * {@inheritdoc}
     */
    public function load($id)
    {
        $media = $this->mediaRepository->findMediaById($id);

        return $media;
    }

    /**
     * {@inheritdoc}
     */
    public function add(UploadedFile $uploadedFile, $userId, $collectionId, $properties = array())
    {
        $this->validator->validate($uploadedFile);

        $storageOptions = $this->storage->save($uploadedFile->getPathname(), $uploadedFile->getFilename(), 1);

        // create media object
        $media = new Media();
        $user = $this->userRepository->findUserById($userId);
        $media->setChanged(new \Datetime());
        $media->setCreated(new \Datetime());
        $media->setChanger($user);
        $media->setCreator($user);

        $collection = $this->collectionRepository->find($collectionId);
        if (!$collection) {
            throw new CollectionNotFoundException('Collection not found');
        } else {
            $media->setCollection($collection);
        }

        // create file
        $file = new File();
        $file->setChanged(new \Datetime());
        $file->setCreated(new \Datetime());
        $file->setChanger($user);
        $file->setCreator($user);

        $file->setVersion(1);

        // create file version
        $fileVersion = new FileVersion();
        $fileVersion->setChanged(new \Datetime());
        $fileVersion->setCreated(new \Datetime());
        $fileVersion->setChanger($user);
        $fileVersion->setCreator($user);
        $fileVersion->setSize($uploadedFile->getSize());
        $fileVersion->setName($uploadedFile->getFilename());

        $fileVersion->setStorageOptions($storageOptions);

        // add file version to file
        $file->addFileVersion($fileVersion);

        // update properties
        $this->setProperties($file->getFileVersions(), $properties);

        // add file to media
        $media->addFile($file);

        $this->em->persist($fileVersion);
        $this->em->persist($file);
        $this->em->persist($media);
        $this->em->flush();

        return $media;
    }

    /**
     * {@inheritdoc}
     */
    public function update(UploadedFile $uploadedFile, $userId, $id, $collectionId = null, $properties = array())
    {
        $media = $this->mediaRepository->findMediaById($id);
        $user = $this->userRepository->findUserById($userId);

        $media->setChanged(new \Datetime());
        $media->setChanger($user);

        if ($collectionId !== null) { // collection not changed
            $collection = $this->collectionRepository->find($collectionId);
            if (!$collection) {
                throw new CollectionNotFoundException ('Collection not found with the ID: ' . $collectionId);
            } else {
                $media->setCollection($collection);
            }
        }

        /**
         * @var File $file
         */
        $file = $media->getFiles()[0];

        $file->setChanged(new \Datetime());
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
                break;
            }
        }

        if (!$fileName) {
            throw new FileVersionNotFoundException ('Actual Version not found('.$version.')');
        } else {
            if ($uploadedFile) {
                $version++; // Update Version
                $this->validator->validate($uploadedFile);
                $storageOptions = $this->storage->save($uploadedFile->getPathname(), $uploadedFile->getFilename(), $version, $oldStorageOptions);

                $fileVersion = new FileVersion();
                $fileVersion->setChanged(new \Datetime());
                $fileVersion->setCreated(new \Datetime());
                $fileVersion->setChanger($user);
                $fileVersion->setCreator($user);
                $fileVersion->setSize($uploadedFile->getSize());
                $fileVersion->setName($uploadedFile->getFilename());
                $fileVersion->setVersion($version);
                $file->setVersion($version);

                $fileVersion->setStorageOptions($storageOptions);
            }

            if ($uploadedFile) {
                $file->addFileVersion($fileVersion);
            }
        }

        // update properties
        $this->setProperties($file->getFileVersions(), $properties);

        $this->em->persist($fileVersion);
        $this->em->persist($file);
        $this->em->persist($media);
        $this->em->flush();

        return $media;
    }

    /**
     * @param $fileVersions
     * @param $properties
     */
    protected function setProperties($fileVersions, $properties)
    {
        foreach ($fileVersions as $fileVersion) {
            foreach ($properties as $fileVersionProperties) {
                $propertiesFileVersionId = $fileVersionProperties['id'] != null ? $fileVersionProperties['id'] : null;
                if ($fileVersion->getId() == $propertiesFileVersionId) {
                    foreach ($fileVersionProperties as $key => $value) {
                        switch ($key) {
                            case 'metas':
                                $this->updateMetas($fileVersion, $fileVersionProperties);
                                break;
                            case 'contentLanguages':
                                $this->updateContentLanguages($fileVersion, $fileVersionProperties);
                                break;
                            case 'publishLanguages':
                                $this->updatePublishLanguages($fileVersion, $fileVersionProperties);
                                break;
                        }
                    }
                }
            }
        }
    }

    /**
     * @param FileVersion $fileVersion
     * @param $metas
     */
    protected function updateMetas(&$fileVersion, $metas)
    {
        /**
         * @var FileVersionMeta $oldMeta
         */
        // Update Old Meta
        foreach ($fileVersion->getMetas() as $oldMeta) {
            $exists = false;
            foreach ($metas as $key => $meta) {
                if ($oldMeta->getId() == $meta['id']) {
                    if (isset($meta['title'])) {
                        $oldMeta->setTitle($meta['title']);
                    }
                    if (isset($meta['description'])) {
                        $oldMeta->setDescription($meta['description']);
                    }
                    if (isset($meta['locale'])) {
                        $oldMeta->setLocale($meta['locale']);
                    }
                    $this->em->persist($oldMeta);

                    unset($metas[$key]);
                    $exists = true;
                    break;
                }
            }
            if (!$exists) {
                // Remove Old Meta
                $fileVersion->removeMeta($oldMeta);
            }
        }
        // Add New Meta
        foreach ($metas as $metaData) {
            $meta = new FileVersionMeta();
            $meta->setTitle($metaData['title']);
            $meta->setDescription($metaData['description']);
            $meta->setLocale($metaData['locale']);

            $fileVersion->addMeta($meta);
        }
    }

    /**
     * @param FileVersion $fileVersion
     * @param $contentLanguages
     */
    protected function updateContentLanguages(&$fileVersion, $contentLanguages)
    {
        /**
         * @var FileVersionContentLanguage $oldContentLanguage
         */
        // Update Old ContentLanguages
        foreach ($fileVersion->getFileVersionContentLanguages() as $oldContentLanguage) {
            $exists = false;
            foreach ($contentLanguages as $key => $contentLanguage) {
                if ($oldContentLanguage->getId() == $contentLanguage['id']) {
                    if (isset($contentLanguage['locale'])) {
                        $oldContentLanguage->setLocale($contentLanguage['locale']);
                    }
                    $this->em->persist($oldContentLanguage);

                    unset($contentLanguage[$key]);
                    $exists = true;
                    break;
                }
            }
            if (!$exists) {
                // Remove Old ContentLanguages
                $this->em->remove($oldContentLanguage);
            }
        }
        // Add New ContentLanguages
        foreach ($contentLanguages as $contentLanguageData) {
            $contentLanguage = new FileVersionContentLanguage();
            $contentLanguage->setLocale($contentLanguageData['locale']);

            $fileVersion->addFileVersionContentLanguage($contentLanguage);
        }
    }

    /**
     * @param FileVersion $fileVersion
     * @param $publishLanguages
     */
    protected function updatePublishLanguages(&$fileVersion, $publishLanguages)
    {
        /**
         * @var FileVersionPublishLanguage $oldPublishLanguage
         */
        // Update Old PublishLanguages
        foreach ($fileVersion->getFileVersionPublishLanguages() as $oldPublishLanguage) {
            $exists = false;
            foreach ($publishLanguages as $key => $publishLanguage) {
                if ($oldPublishLanguage->getId() == $publishLanguage['id']) {
                    if (isset($publishLanguage['locale'])) {
                        $oldPublishLanguage->setLocale($publishLanguage['locale']);
                    }
                    $this->em->persist($oldPublishLanguage);

                    unset($publishLanguage[$key]);
                    $exists = true;
                    break;
                }
            }
            if (!$exists) {
                // Remove Old PublishLanguages
                $this->em->remove($oldPublishLanguage);
            }
        }
        // Add New PublishLanguages
        foreach ($publishLanguages as $publishLanguageData) {
            $publishLanguage = new FileVersionPublishLanguage();
            $publishLanguage->setLocale($publishLanguageData['locale']);

            $fileVersion->addFileVersionPublishLanguage($publishLanguage);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function remove($id, $userId)
    {
        $media = $this->mediaRepository->findMediaByIdForDelete($id);
        /**
         * @var File $file
         */
        foreach ($media->getFiles() as $file) {
            /**
             * @var FileVersion $fileVersion
             */
            foreach ($file->getFileVersions() as $fileVersion) {
                $this->storage->remove($fileVersion->getStorageOption());
            }
        }
        $this->em->remove($media);
        $this->em->flush();
    }
}
