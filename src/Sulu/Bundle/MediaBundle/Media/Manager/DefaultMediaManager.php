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
use Sulu\Bundle\MediaBundle\Media\FileValidatorInterface;
use Sulu\Bundle\MediaBundle\Media\Manager\MediaManagerInterface;
use Sulu\Bundle\MediaBundle\Entity\CollectionRepository;
use Sulu\Bundle\MediaBundle\Entity\Media;
use Sulu\Bundle\MediaBundle\Entity\MediaRepository;
use Sulu\Bundle\MediaBundle\Media\DefaultFileValidator;
use Sulu\Bundle\MediaBundle\Media\Exception\UploadFileValidationException;
use Sulu\Bundle\MediaBundle\Media\Storage\StorageInterface;
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
        $this->maxFileSize = $maxFileSize;
        $this->blockedMimeTypes = $blockedMimeTypes;
    }

    /**
     * {@inheritdoc}
     */
    public function add(UploadedFile $file, $userId, $collectionId, $properties = array())
    {
        $validator = new DefaultFileValidator($this->maxFileSize, $this->blockedMimeTypes);
        $validator->validate($file);

        $storageOptions = $this->storage->save($file->getPathname(), $file->getFilename(), 1);

        // create media object
        $media = new Media();
        $user = $this->userRepository->findUserById($userId);
        $media->setChanged(new \Datetime());
        $media->setCreated(new \Datetime());
        $media->setChanger($user);
        $media->setCreator($user);

        $collection = $this->collectionRepository->findCollectionById($collectionId);
        $media->setCollection($collection);

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

        $fileVersion->setStorageOptions($storageOptions);

        // add file version to file
        $file->addFileVersion($fileVersion);

        // add file to media
        $media->addFile($file);

        $this->em->persist($fileVersion);
        $this->em->persist($file);
        $this->em->persist($media);
        $this->em->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function update(UploadedFile $file, $userId, $id, $collectionId = null, $properties = array())
    {

    }

    /**
     * {@inheritdoc}
     */
    public function remove($id, $userId)
    {

    }
}
