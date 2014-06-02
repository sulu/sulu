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
use Sulu\Bundle\MediaBundle\Media\Exception\UploadFileValidationException;
use Sulu\Bundle\MediaBundle\Media\Storage\StorageInterface;
use Sulu\Bundle\MediaBundle\Media\FileValidator\FileValidatorInterface;
use Sulu\Component\Security\UserRepositoryInterface;
use DateTime;
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
     * @var array
     */
    private $mediaTypes;

    /**
     * @param MediaRepository $mediaRepository
     * @param CollectionRepository $collectionRepository
     * @param UserRepositoryInterface $userRepository
     * @param ObjectManager $em
     * @param StorageInterface $storage
     * @param FileValidatorInterface $validator
     * @param string $maxFileSize
     * @param array $blockedMimeTypes
     * @param array $mediaTypes
     */
    public function __construct(
        MediaRepository $mediaRepository,
        CollectionRepository $collectionRepository,
        UserRepositoryInterface $userRepository,
        ObjectManager $em,
        StorageInterface $storage,
        FileValidatorInterface $validator,
        $maxFileSize,
        $blockedMimeTypes,
        $mediaTypes
    )
    {
        $this->mediaRepository = $mediaRepository;
        $this->collectionRepository = $collectionRepository;
        $this->em = $em;
        $this->userRepository = $userRepository;
        $this->storage = $storage;
        $this->validator = $validator;
        $this->maxFileSize = $maxFileSize;
        $this->blockedMimeTypes = $blockedMimeTypes;
        $this->mediaTypes = $mediaTypes;
    }

    /**
     * {@inheritdoc}
     */
    public function get($id)
    {
        $media = $this->mediaRepository->findMediaById($id);

        return $media;
    }

    /**
     * {@inheritdoc}
     */
    public function add($uploadedFile, $userId, $collectionId, $properties = array())
    {
        if (!($uploadedFile instanceof UploadedFile)) {
            throw new InvalidFileException('given uploadfile is not of instance UploadFile');
        }

        $this->validator->validate($uploadedFile);

        $storageOptions = $this->storage->save($uploadedFile->getPathname(), $uploadedFile->getClientOriginalName(), 1);

        // create media object
        $media = new Media();
        $user = $this->userRepository->findUserById($userId);
        $media->setChanged(new Datetime());
        $media->setCreated(new Datetime());
        $media->setChanger($user);
        $media->setCreator($user);
        $media->setType($this->getMediaType($uploadedFile));

        $collection = $this->collectionRepository->find($collectionId);
        if (!$collection) {
            throw new CollectionNotFoundException('Collection not found');
        } else {
            $media->setCollection($collection);
        }

        // create file
        $version = 1;
        $file = new File();
        $file->setChanged(new Datetime());
        $file->setCreated(new Datetime());
        $file->setChanger($user);
        $file->setCreator($user);

        $file->setVersion($version);

        // create file version
        $fileVersion = new FileVersion();
        $fileVersion->setChanged(new Datetime());
        $fileVersion->setCreated(new Datetime());
        $fileVersion->setChanger($user);
        $fileVersion->setCreator($user);
        $fileVersion->setVersion($version);
        $fileVersion->setSize($uploadedFile->getSize());
        $fileVersion->setName($uploadedFile->getClientOriginalName());
        $fileVersion->setStorageOptions($storageOptions);

        // add file version to file
        $fileVersion->setFile($file);
        $file->addFileVersion($fileVersion);

        // update properties
        $this->setProperties($file->getFileVersions(), $properties, $user, $version);

        // add file to media
        $file->setMedia($media);
        $media->addFile($file);

        $this->em->persist($fileVersion);
        $this->em->persist($file);
        $this->em->persist($media);
        $this->em->flush();

        return $media;
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
     * {@inheritdoc}
     */
    public function update($uploadedFile, $userId, $id, $collectionId = null, $properties = array())
    {
        $media = $this->mediaRepository->findMediaById($id);
        $user = $this->userRepository->findUserById($userId);

        $media->setChanged(new Datetime());
        $media->setChanger($user);

        if ($uploadedFile !== null) {
            if (!($uploadedFile instanceof UploadedFile)) {
                throw new InvalidFileException('given uploadfile is not of instance UploadFile');
            }
        }

        if ($uploadedFile) {
            $mediaType = $this->getMediaType($uploadedFile);
            if ($media->getType()->getId() != $mediaType->getId()) {
                throw new InvalidMediaTypeException('Media must be of type ' . $media->getType()->getId() . '('.$media->getType()->getName().'), ' . $mediaType->getId() . '('.$mediaType->getName().') was given');
            }
        }

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

        $file->setChanged(new Datetime());
        $file->setChanger($user);

        $version = $file->getVersion();

        $fileName = null;
        $oldStorageOptions = null;

        /**
         * @var FileVersion $fileVersion
         */
        $oldMeta = array();
        $oldTags = array();
        $oldContentLanguages = array();
        $oldPublishLanguages = array();
        foreach ($file->getFileVersions() as $fileVersion) {
            if ($version == $file->getVersion()) {
                $fileName = $fileVersion->getName();
                $oldStorageOptions = $fileVersion->getStorageOptions();

                $oldMeta = $fileVersion->getMeta();
                $oldTags = $fileVersion->getTags();
                $oldContentLanguages = $fileVersion->getContentLanguages();
                $oldPublishLanguages = $fileVersion->getPublishLanguages();

                break;
            }
        }

        if (!$fileName) {
            throw new FileVersionNotFoundException ('Actual Version not found('.$version.')');
        }

        if ($uploadedFile) {
            $version++; // Update Version
            $this->validator->validate($uploadedFile);
            $storageOptions = $this->storage->save($uploadedFile->getPathname(), $uploadedFile->getFilename(), $version, $oldStorageOptions);

            $fileVersion = new FileVersion();
            $fileVersion->setChanged(new Datetime());
            $fileVersion->setCreated(new Datetime());
            $fileVersion->setChanger($user);
            $fileVersion->setCreator($user);
            $fileVersion->setSize($uploadedFile->getSize());
            $fileVersion->setName($uploadedFile->getClientOriginalName());
            $fileVersion->setVersion($version);
            $file->setVersion($version);
            $fileVersion->setStorageOptions($storageOptions);
            $fileVersion->setFile($file);

            $this->setNewVersionProperties($fileVersion, $oldMeta, $oldTags, $oldContentLanguages, $oldPublishLanguages);
        }

        if ($uploadedFile) {
            $file->addFileVersion($fileVersion);
        }

        // update properties
        $this->setProperties($file->getFileVersions(), $properties, $user, $version);

        $this->em->persist($fileVersion);
        $this->em->persist($file);
        $this->em->persist($media);
        $this->em->flush();

        return $media;
    }

    /**
     * @param FileVersion $fileVersion
     * @param FileVersionMeta[] $metas
     * @param Tag[] $tags
     * @param FileVersionContentLanguage[] $contentLanguages
     * @param FileVersionPublishLanguage[] $publishLanguages
     */
    protected function setNewVersionProperties(&$fileVersion, $metas = array(), $tags = array(), $contentLanguages = array(), $publishLanguages = array())
    {
        foreach ($metas as $meta) {
            $newMedia = clone $meta;
            $newMedia->setFileVersion($fileVersion);
            $this->em->persist($newMedia);
            $fileVersion->addMeta($newMedia);
        }

        foreach ($tags as $tag) {
            $fileVersion->addTag($tag);
        }

        foreach ($contentLanguages as $contentLanguage) {
            $newContentLanguage = clone $contentLanguage;
            $newContentLanguage->setFileVersion($fileVersion);
            $this->em->persist($newContentLanguage);
            $fileVersion->addContentLanguage($newContentLanguage);
        }

        foreach ($publishLanguages as $publishLanguage) {
            $newPublishLanguage = clone $publishLanguage;
            $newPublishLanguage->setFileVersion($fileVersion);
            $this->em->persist($newPublishLanguage);
            $fileVersion->addPublishLanguage($newPublishLanguage);
        }
    }

    /**
     * @param $fileVersions
     * @param $properties
     * @param $user
     * @param $version
     */
    protected function setProperties($fileVersions, $properties, $user, $version)
    {
        /**
         * @var FileVersion $fileVersion
         */
        foreach ($fileVersions as &$fileVersion) {
            $changed = false;
            foreach ($properties as $fileVersionProperties) {
                $propertiesFileVersionId = isset($fileVersionProperties['version']) ? $fileVersionProperties['version'] : $version; // update old version or actual version
                if ($fileVersion->getVersion() == $propertiesFileVersionId) {
                    $changed = $this->updateFileVersionProperties($fileVersion, $fileVersionProperties);
                }
            }

            if ($changed) {
                $fileVersion->setChanged(new Datetime());
                $fileVersion->setChanger($user);

                $this->em->persist($fileVersion);
            }
        }
    }

    /**
     * @param $fileVersion
     * @param $fileVersionProperties
     * @return bool
     */
    protected function updateFileVersionProperties(&$fileVersion, &$fileVersionProperties)
    {
        $changed = false;
        foreach ($fileVersionProperties as $key => $value) {
            switch ($key) {
                case 'meta':
                    $this->updateMeta($fileVersion, $value);
                    $changed = true;
                    break;
                case 'contentLanguages':
                    $this->updateContentLanguages($fileVersion, $value);
                    $changed = true;
                    break;
                case 'publishLanguages':
                    $this->updatePublishLanguages($fileVersion, $value);
                    $changed = true;
                    break;
            }
        }
        return $changed;
    }


    /**
     * @param FileVersion $fileVersion
     * @param $metaList
     */
    protected function updateMeta(&$fileVersion, $metaList)
    {
        /**
         * @var FileVersionMeta $oldMeta
         */
        // Update Old Meta
        if ($fileVersion->getMeta()) {
            foreach ($fileVersion->getMeta() as $oldMeta) {
                foreach ($metaList as $key => $meta) {
                    if (isset($meta['locale']) && $oldMeta->getLocale() == $meta['locale']) {
                        if (isset($meta['title'])) {
                            $oldMeta->setTitle($meta['title']);
                        }
                        if (isset($meta['description'])) {
                            $oldMeta->setDescription($meta['description']);
                        }
                        $fileVersion->addMeta($oldMeta);
                        $this->em->persist($oldMeta);

                        unset($metaList[$key]);
                        break;
                    }
                }
            }
        }
        // Add New Meta
        foreach ($metaList as $metaData) {
            if (
                !empty($metaData['locale']) && // http://www.php.net/manual/en/function.empty.php#refsect1-function.empty-parameters
                !empty($metaData['title'])
            ) {
                $meta = new FileVersionMeta();
                $meta->setTitle($metaData['title']);
                $meta->setLocale($metaData['locale']);
                if (isset($metaData['description'])) {
                    $meta->setDescription($metaData['description']);
                }
                $meta->setFileVersion($fileVersion);

                $fileVersion->addMeta($meta);
                $this->em->persist($meta);
            }
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
        if ($fileVersion->getContentLanguages()) {
            foreach ($fileVersion->getContentLanguages() as $oldContentLanguage) {
                $exists = false;
                foreach ($contentLanguages as $key => $contentLanguage) {
                    if ($oldContentLanguage->getId() == $contentLanguage) {
                        unset($contentLanguages[$key]);
                        $exists = true;
                        break;
                    }
                }
                if (!$exists) {
                    // Remove Old ContentLanguages
                    $this->em->remove($oldContentLanguage);
                }
            }
        }
        // Add New ContentLanguages
        foreach ($contentLanguages as $contentLanguageData) {
            $contentLanguage = new FileVersionContentLanguage();
            $contentLanguage->setLocale($contentLanguageData);
            $contentLanguage->setFileVersion($fileVersion);

            $fileVersion->addContentLanguage($contentLanguage);
            $this->em->persist($contentLanguage);
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
        if ($fileVersion->getPublishLanguages()) {
            foreach ($fileVersion->getPublishLanguages() as $oldPublishLanguage) {
                $exists = false;
                foreach ($publishLanguages as $key => $publishLanguage) {
                    if ($oldPublishLanguage->getLocale() == $publishLanguage) {
                        unset($publishLanguages[$key]);
                        $exists = true;
                        break;
                    }
                }
                if (!$exists) {
                    // Remove Old PublishLanguages
                    $this->em->remove($oldPublishLanguage);
                }
            }
        }
        // Add New PublishLanguages
        foreach ($publishLanguages as $publishLanguageData) {
            $publishLanguage = new FileVersionPublishLanguage();
            $publishLanguage->setFileVersion($fileVersion);
            $publishLanguage->setLocale($publishLanguageData);

            $fileVersion->addPublishLanguage($publishLanguage);
            $this->em->persist($publishLanguage);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function remove($id, $userId)
    {
        $media = $this->mediaRepository->findMediaByIdForDelete($id);

        if (!$media) {
            throw new EntityNotFoundException('SuluMediaBundle:Media', $id);
        }
        /**
         * @var File $file
         */
        foreach ($media->getFiles() as $file) {
            /**
             * @var FileVersion $fileVersion
             */
            foreach ($file->getFileVersions() as $fileVersion) {
                $this->storage->remove($fileVersion->getStorageOptions());
            }
        }
        $this->em->remove($media);
        $this->em->flush();
    }
}
