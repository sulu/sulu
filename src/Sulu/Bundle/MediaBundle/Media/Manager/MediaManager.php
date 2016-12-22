<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Media\Manager;

use Doctrine\DBAL\DBALException;
use Doctrine\ORM\EntityManager;
use FFMpeg\Exception\ExecutableNotFoundException;
use FFMpeg\FFProbe;
use Sulu\Bundle\CategoryBundle\Entity\CategoryRepositoryInterface;
use Sulu\Bundle\MediaBundle\Api\Media;
use Sulu\Bundle\MediaBundle\Entity\Collection;
use Sulu\Bundle\MediaBundle\Entity\CollectionRepository;
use Sulu\Bundle\MediaBundle\Entity\CollectionRepositoryInterface;
use Sulu\Bundle\MediaBundle\Entity\File;
use Sulu\Bundle\MediaBundle\Entity\FileVersion;
use Sulu\Bundle\MediaBundle\Entity\MediaRepositoryInterface;
use Sulu\Bundle\MediaBundle\Media\Exception\CollectionNotFoundException;
use Sulu\Bundle\MediaBundle\Media\Exception\FileVersionNotFoundException;
use Sulu\Bundle\MediaBundle\Media\Exception\InvalidFileException;
use Sulu\Bundle\MediaBundle\Media\Exception\InvalidMediaTypeException;
use Sulu\Bundle\MediaBundle\Media\Exception\MediaNotFoundException;
use Sulu\Bundle\MediaBundle\Media\FileValidator\FileValidatorInterface;
use Sulu\Bundle\MediaBundle\Media\FormatManager\FormatManagerInterface;
use Sulu\Bundle\MediaBundle\Media\Storage\StorageInterface;
use Sulu\Bundle\MediaBundle\Media\TypeManager\TypeManagerInterface;
use Sulu\Bundle\TagBundle\Tag\TagManagerInterface;
use Sulu\Component\PHPCR\PathCleanupInterface;
use Sulu\Component\Security\Authentication\UserInterface;
use Sulu\Component\Security\Authentication\UserRepositoryInterface;
use Sulu\Component\Security\Authorization\PermissionTypes;
use Sulu\Component\Security\Authorization\SecurityCheckerInterface;
use Sulu\Component\Security\Authorization\SecurityCondition;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Default implementation of media manager.
 */
class MediaManager implements MediaManagerInterface
{
    const ENTITY_NAME_COLLECTION = 'SuluMediaBundle:Collection';

    /**
     * The repository for communication with the database.
     *
     * @var MediaRepositoryInterface
     */
    protected $mediaRepository;

    /**
     * The repository for communication with the database.
     *
     * @var CollectionRepository
     */
    protected $collectionRepository;

    /**
     * @var CategoryRepositoryInterface
     */
    protected $categoryRepository;

    /**
     * @var EntityManager
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
     * @var TypeManagerInterface
     */
    protected $typeManager;

    /**
     * @var StorageInterface
     */
    protected $storage;

    /**
     * @var UserRepositoryInterface
     */
    private $userRepository;

    /**
     * @var int
     */
    private $maxFileSize;

    /**
     * @var TagManagerInterface
     */
    private $tagManager;

    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * @var SecurityCheckerInterface
     */
    private $securityChecker;

    /**
     * @var PathCleanupInterface
     */
    private $pathCleaner;

    /**
     * @var array
     */
    private $permissions;

    /**
     * @var string
     */
    private $downloadPath;

    /**
     * @var FFProbe
     */
    private $ffprobe;

    /**
     * @var int
     */
    public $count;

    /**
     * @param MediaRepositoryInterface $mediaRepository
     * @param CollectionRepositoryInterface $collectionRepository
     * @param UserRepositoryInterface $userRepository
     * @param EntityManager $em
     * @param StorageInterface $storage
     * @param FileValidatorInterface $validator
     * @param FormatManagerInterface $formatManager
     * @param TagManagerInterface $tagManager
     * @param TypeManagerInterface $typeManager
     * @param PathCleanupInterface $pathCleaner
     * @param TokenStorageInterface $tokenStorage
     * @param SecurityCheckerInterface $securityChecker
     * @param FFProbe $ffprobe
     * @param array $permissions
     * @param string $downloadPath
     * @param string $maxFileSize
     */
    public function __construct(
        MediaRepositoryInterface $mediaRepository,
        CollectionRepositoryInterface $collectionRepository,
        UserRepositoryInterface $userRepository,
        CategoryRepositoryInterface $categoryRepository,
        EntityManager $em,
        StorageInterface $storage,
        FileValidatorInterface $validator,
        FormatManagerInterface $formatManager,
        TagManagerInterface $tagManager,
        TypeManagerInterface $typeManager,
        PathCleanupInterface $pathCleaner,
        TokenStorageInterface $tokenStorage = null,
        SecurityCheckerInterface $securityChecker = null,
        FFProbe $ffprobe,
        $permissions,
        $downloadPath,
        $maxFileSize
    ) {
        $this->mediaRepository = $mediaRepository;
        $this->collectionRepository = $collectionRepository;
        $this->userRepository = $userRepository;
        $this->categoryRepository = $categoryRepository;
        $this->em = $em;
        $this->storage = $storage;
        $this->validator = $validator;
        $this->formatManager = $formatManager;
        $this->tagManager = $tagManager;
        $this->typeManager = $typeManager;
        $this->pathCleaner = $pathCleaner;
        $this->tokenStorage = $tokenStorage;
        $this->securityChecker = $securityChecker;
        $this->ffprobe = $ffprobe;
        $this->permissions = $permissions;
        $this->downloadPath = $downloadPath;
        $this->maxFileSize = $maxFileSize;
    }

    /**
     * {@inheritdoc}
     */
    public function getById($id, $locale)
    {
        $mediaEntity = $this->getEntityById($id);

        return $this->addFormatsAndUrl(new Media($mediaEntity, $locale, null));
    }

    /**
     * {@inheritdoc}
     */
    public function getEntityById($id)
    {
        $mediaEntity = $this->mediaRepository->findMediaById($id);
        if (!$mediaEntity) {
            throw new MediaNotFoundException($id);
        }

        return $mediaEntity;
    }

    /**
     * {@inheritdoc}
     */
    public function getByIds(array $ids, $locale)
    {
        $media = [];
        $mediaEntities = $this->mediaRepository->findMedia(['pagination' => false, 'ids' => $ids]);
        $this->count = count($mediaEntities);
        foreach ($mediaEntities as $mediaEntity) {
            $media[array_search($mediaEntity->getId(), $ids)] = $this->addFormatsAndUrl(
                new Media($mediaEntity, $locale, null)
            );
        }

        ksort($media);

        return array_values($media);
    }

    /**
     * {@inheritdoc}
     */
    public function get($locale, $filter = [], $limit = null, $offset = null)
    {
        $media = [];
        $mediaEntities = $this->mediaRepository->findMedia(
            $filter,
            $limit,
            $offset,
            $this->getCurrentUser(),
            $this->permissions[PermissionTypes::VIEW]
        );
        $this->count = $this->mediaRepository->count($filter);

        foreach ($mediaEntities as $mediaEntity) {
            $media[] = $this->addFormatsAndUrl(new Media($mediaEntity, $locale, null));
        }

        return $media;
    }

    /**
     * {@inheritdoc}
     */
    public function getCount()
    {
        return $this->count;
    }

    /**
     * {@inheritdoc}
     */
    public function save($uploadedFile, $data, $userId)
    {
        if (isset($data['id'])) {
            $media = $this->modifyMedia($uploadedFile, $data, $this->getUser($userId));
        } else {
            $media = $this->buildData($uploadedFile, $data, $this->getUser($userId));
        }

        return $this->addFormatsAndUrl($media);
    }

    /**
     * @param UploadedFile $uploadedFile
     *
     * @return array
     */
    private function getProperties(UploadedFile $uploadedFile)
    {
        $mimeType = $uploadedFile->getMimeType();
        $properties = [];

        try {
            // if the file is a video we add the duration
            if (fnmatch('video/*', $mimeType)) {
                $properties['duration'] = $this->ffprobe->format($uploadedFile->getPathname())->get('duration');
            }
        } catch (ExecutableNotFoundException $e) {
            // Exception is thrown if ffmpeg is not installed -> duration is not set
        }

        return $properties;
    }

    /**
     * Modifies an existing media.
     *
     * @param UploadedFile $uploadedFile
     * @param $data
     * @param UserInterface $user
     *
     * @throws FileVersionNotFoundException
     * @throws InvalidMediaTypeException
     *
     * @return Media
     */
    private function modifyMedia($uploadedFile, $data, $user)
    {
        $mediaEntity = $this->getEntityById($data['id']);
        $mediaEntity->setChanger($user);
        $mediaEntity->setChanged(new \DateTime());

        $files = $mediaEntity->getFiles();
        if (!isset($files[0])) {
            throw new FileNotFoundException('File was not found in media entity with the id . ' . $data['id']);
        }

        /** @var File $file */
        $file = $files[0]; // currently a media can only have one file

        $file->setChanger($user);
        $file->setChanged(new \DateTime());

        $version = $file->getVersion();

        $currentFileVersion = null;

        foreach ($file->getFileVersions() as $fileVersion) {
            /** @var FileVersion $fileVersion */
            if ($version == $fileVersion->getVersion()) {
                $currentFileVersion = $fileVersion;
                break;
            }
        }

        if (!$currentFileVersion) {
            throw new FileVersionNotFoundException($mediaEntity->getId(), $version);
        }

        if ($uploadedFile) {
            // new uploaded file
            ++$version;
            $this->validator->validate($uploadedFile);
            $type = $this->typeManager->getMediaType($uploadedFile->getMimeType());
            if ($type !== $mediaEntity->getType()->getId()) {
                throw new InvalidMediaTypeException('New media version must have the same media type.');
            }

            $data['storageOptions'] = $this->storage->save(
                $uploadedFile->getPathname(),
                $this->getNormalizedFileName($uploadedFile->getClientOriginalName()),
                $version,
                $currentFileVersion->getStorageOptions()
            );
            $data['name'] = $uploadedFile->getClientOriginalName();
            $data['size'] = intval($uploadedFile->getSize());
            $data['mimeType'] = $uploadedFile->getMimeType();
            $data['properties'] = $this->getProperties($uploadedFile);
            $data['type'] = [
                'id' => $type,
            ];
            $data['version'] = $version;

            $fileVersion = clone $currentFileVersion;
            $this->em->persist($fileVersion);

            $fileVersion->setChanged(new \DateTime());
            $fileVersion->setChanger($user);
            $fileVersion->setCreated(new \DateTime());
            $fileVersion->setCreator($user);
            $fileVersion->setDownloadCounter(0);

            $file->setVersion($version);
            $fileVersion->setVersion($version);
            $fileVersion->setFile($file);
            $file->addFileVersion($fileVersion);

            // delete old fileversion from cache
            $this->formatManager->purge(
                $mediaEntity->getId(),
                $currentFileVersion->getName(),
                $currentFileVersion->getMimeType(),
                $currentFileVersion->getStorageOptions()
            );
        } else {
            // not setable in update
            $data['name'] = null;
            $data['size'] = null;
            $data['type'] = null;
            $data['version'] = null;
            $data['mimeType'] = null;
            $data['storageOptions'] = null;
            $data['changed'] = date('Y-m-d H:i:s');

            if ((isset($data['focusPointX']) && $data['focusPointX'] != $currentFileVersion->getFocusPointX())
                || (isset($data['focusPointY']) && $data['focusPointY'] != $currentFileVersion->getFocusPointY())
            ) {
                $currentFileVersion->increaseSubVersion();
                $this->formatManager->purge(
                    $mediaEntity->getId(),
                    $currentFileVersion->getName(),
                    $currentFileVersion->getMimeType(),
                    $currentFileVersion->getStorageOptions()
                );
            }
        }

        $media = new Media($mediaEntity, $data['locale'], null);

        $media = $this->setDataToMedia(
            $media,
            $data,
            $user
        );

        $this->em->persist($media->getEntity());
        $this->em->flush();

        return $media;
    }

    /**
     * Prepares data.
     *
     * @param UploadedFile $uploadedFile
     * @param array $data
     * @param UserInterface $user
     *
     * @return Media
     *
     * @throws InvalidFileException
     */
    private function buildData($uploadedFile, $data, $user)
    {
        if (!($uploadedFile instanceof UploadedFile)) {
            throw new InvalidFileException('Given uploaded file is not of instance UploadedFile');
        }

        $this->validator->validate($uploadedFile);

        $data['storageOptions'] = $this->storage->save(
            $uploadedFile->getPathname(),
            $this->getNormalizedFileName($uploadedFile->getClientOriginalName()),
            1
        );

        $data['name'] = $uploadedFile->getClientOriginalName();
        $data['size'] = $uploadedFile->getSize();
        $data['mimeType'] = $uploadedFile->getMimeType();
        $data['properties'] = $this->getProperties($uploadedFile);
        $data['type'] = [
            'id' => $this->typeManager->getMediaType($uploadedFile->getMimeType()),
        ];

        return $this->createMedia($data, $user);
    }

    /**
     * Create a new media.
     *
     * @param $data
     * @param $user
     *
     * @return Media
     */
    protected function createMedia($data, $user)
    {
        $mediaEntity = $this->mediaRepository->createNew();
        $mediaEntity->setCreator($user);
        $mediaEntity->setChanger($user);

        $file = new File();
        $file->setCreator($user);
        $file->setChanger($user);
        $file->setVersion(1);
        $file->setMedia($mediaEntity);

        $fileVersion = new FileVersion();
        $fileVersion->setCreator($user);
        $fileVersion->setChanger($user);
        $fileVersion->setVersion(1);
        $fileVersion->setFile($file);

        $file->addFileVersion($fileVersion);
        $mediaEntity->addFile($file);

        $media = new Media($mediaEntity, $data['locale'], null);

        $media = $this->setDataToMedia(
            $media,
            $data,
            $user
        );

        $fileVersion->setDefaultMeta($fileVersion->getMeta()->first());

        $mediaEntity = $media->getEntity();
        $this->em->persist($mediaEntity);
        $this->em->flush();

        return $media;
    }

    /**
     * Data can be set over by array.
     *
     * @param $media
     * @param $data
     * @param UserInterface $user
     *
     * @return Media
     */
    protected function setDataToMedia(Media $media, $data, $user)
    {
        foreach ($data as $attribute => $value) {
            if ($value ||
                ($attribute === 'tags' && $value !== null) ||
                ($attribute === 'size' && $value !== null) ||
                ($attribute === 'description' && $value !== null) ||
                ($attribute === 'copyright' && $value !== null) ||
                ($attribute === 'credits' && $value !== null) ||
                ($attribute === 'categories' && $value !== null) ||
                ($attribute === 'focusPointX' && $value !== null) ||
                ($attribute === 'focusPointY' && $value !== null)
            ) {
                switch ($attribute) {
                    case 'size':
                        $media->setSize($value);
                        break;
                    case 'title':
                        $media->setTitle($value);
                        break;
                    case 'description':
                        $media->setDescription($value);
                        break;
                    case 'copyright':
                        $media->setCopyright($value);
                        break;
                    case 'credits':
                        $media->setCredits($value);
                        break;
                    case 'version':
                        $media->setVersion($value);
                        break;
                    case 'name':
                        $media->setName($value);
                        break;
                    case 'url':
                        $media->setUrl($value);
                        break;
                    case 'formats':
                        $media->setFormats($value);
                        break;
                    case 'storageOptions':
                        $media->setStorageOptions($value);
                        break;
                    case 'publishLanguages':
                        $media->setPublishLanguages($value);
                        break;
                    case 'contentLanguages':
                        $media->setContentLanguages($value);
                        break;
                    case 'tags':
                        $media->removeTags();
                        if (count($value)) {
                            foreach ($value as $tag) {
                                $tagEntity = $this->tagManager->findOrCreateByName($tag, $user->getId());
                                $media->addTag($tagEntity);
                            }
                        }
                        break;
                    case 'properties':
                        $media->setProperties($value);
                        break;
                    case 'changed':
                        $media->setChanged($value);
                        break;
                    case 'created':
                        break;
                    case 'changer':
                        if ($value instanceof UserInterface) {
                            $media->setChanger($value);
                        }
                        break;
                    case 'creator':
                        if ($value instanceof UserInterface) {
                            $media->setCreator($value);
                        }
                        break;
                    case 'mimeType':
                        $media->setMimeType($value);
                        break;
                    case 'collection':
                        $collectionEntity = $this->getCollectionById($value);
                        $media->setCollection($collectionEntity); // set parent
                        break;
                    case 'type':
                        if (isset($value['id'])) {
                            $type = $this->typeManager->get($value['id']);
                            $media->setType($type);
                        }
                        break;
                    case 'categories':
                        $categoryIds = $value;
                        $media->removeCategories();

                        if (is_array($categoryIds) && !empty($categoryIds)) {
                            /** @var CategoryRepositoryInterface $repository */
                            $categories = $this->categoryRepository->findCategoriesByIds($categoryIds);

                            foreach ($categories as $category) {
                                $media->addCategory($category);
                            }
                        }
                        break;
                    case 'focusPointX':
                        $media->setFocusPointX($value);
                        break;
                    case 'focusPointY':
                        $media->setFocusPointY($value);
                        break;
                }
            }
        }

        return $media;
    }

    /**
     * @param $collectionId
     *
     * @return object
     *
     * @throws CollectionNotFoundException
     */
    public function getCollectionById($collectionId)
    {
        $collection = $this->collectionRepository->find($collectionId);
        if (!$collection) {
            throw new CollectionNotFoundException($collectionId);
        }

        return $collection;
    }

    /**
     * {@inheritdoc}
     */
    public function delete($id, $checkSecurity = false)
    {
        $mediaEntity = $this->getEntityById($id);

        if ($checkSecurity) {
            $this->securityChecker->checkPermission(
                new SecurityCondition(
                    'sulu.media.collections',
                    null,
                    Collection::class,
                    $mediaEntity->getCollection()->getId()
                ),
                PermissionTypes::DELETE
            );
        }

        /** @var File $file */
        foreach ($mediaEntity->getFiles() as $file) {
            /** @var FileVersion $fileVersion */
            foreach ($file->getFileVersions() as $fileVersion) {
                $this->formatManager->purge(
                    $mediaEntity->getId(),
                    $fileVersion->getName(),
                    $fileVersion->getMimeType(),
                    $fileVersion->getStorageOptions()
                );

                $this->storage->remove($fileVersion->getStorageOptions());
            }
        }

        $this->em->remove($mediaEntity);
        $this->em->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function move($id, $locale, $destCollection)
    {
        try {
            $mediaEntity = $this->mediaRepository->findMediaById($id);

            if ($mediaEntity === null) {
                throw new MediaNotFoundException($id);
            }

            $mediaEntity->setCollection($this->em->getReference(self::ENTITY_NAME_COLLECTION, $destCollection));

            $this->em->flush();

            return $this->addFormatsAndUrl(new Media($mediaEntity, $locale, null));
        } catch (DBALException $ex) {
            throw new CollectionNotFoundException($destCollection);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function increaseDownloadCounter($fileVersionId)
    {
        $query = $this->em->createQueryBuilder()->update('SuluMediaBundle:FileVersion', 'fV')
            ->set('fV.downloadCounter', 'fV.downloadCounter + 1')
            ->where('fV.id = :id')
            ->setParameter('id', $fileVersionId)
            ->getQuery();

        $query->execute();
    }

    /**
     * {@inheritdoc}
     */
    public function getFormatUrls($ids, $locale)
    {
        $mediaArray = $this->getByIds($ids, $locale);
        $formatUrls = [];
        foreach ($mediaArray as $media) {
            if ($media->getEntity()->getPreviewImage()) {
                $previewImage = new Media($media->getEntity()->getPreviewImage(), $locale);

                $formatUrls[$media->getId()] = $this->formatManager->getFormats(
                    $previewImage->getId(),
                    $previewImage->getName(),
                    $previewImage->getStorageOptions(),
                    $previewImage->getVersion(),
                    $previewImage->getSubVersion(),
                    $previewImage->getMimeType()
                );
            } else {
                $formatUrls[$media->getId()] = $this->formatManager->getFormats(
                    $media->getId(),
                    $media->getName(),
                    $media->getStorageOptions(),
                    $media->getVersion(),
                    $media->getSubVersion(),
                    $media->getMimeType()
                );
            }
        }

        return $formatUrls;
    }

    /**
     * @param Media $media
     *
     * @return Media
     */
    public function addFormatsAndUrl(Media $media)
    {
        // Get preview image and set either preview thumbnails if set, else rendered images
        /** @var \Sulu\Bundle\MediaBundle\Entity\MediaInterface $previewImage */
        $previewImage = $media->getEntity()->getPreviewImage();

        if ($previewImage !== null) {
            /** @var FileVersion $latestVersion */
            $latestVersion = null;

            /** @var File $file */
            foreach ($previewImage->getFiles() as $file) {
                $latestVersion = $file->getLatestFileVersion();

                // currently only one file per media exists
                break;
            }

            if ($latestVersion !== null) {
                $media->setFormats(
                    $this->formatManager->getFormats(
                        $previewImage->getId(),
                        $latestVersion->getName(),
                        $latestVersion->getStorageOptions(),
                        $latestVersion->getVersion(),
                        $latestVersion->getSubVersion(),
                        $latestVersion->getMimeType()
                    )
                );
            }
        } else {
            $media->setFormats(
                $this->formatManager->getFormats(
                    $media->getId(),
                    $media->getName(),
                    $media->getStorageOptions(),
                    $media->getVersion(),
                    $media->getSubVersion(),
                    $media->getMimeType()
                )
            );
        }

        // Set Version Urls
        $versionData = [];
        foreach ($media->getFile()->getFileVersions() as $fileVersion) {
            $versionData[$fileVersion->getVersion()] = [];
            $versionData[$fileVersion->getVersion()]['url'] = $this->getUrl(
                $media->getId(),
                $fileVersion->getName(),
                $fileVersion->getVersion()
            );
        }

        $media->setAdditionalVersionData($versionData);

        // set properties
        $properties = $media->getFileVersion()->getProperties();
        if ($properties !== null) {
            $media->setProperties($properties);
        }

        // Set Current Url
        if (isset($versionData[$media->getVersion()], $versionData[$media->getVersion()]['url'])) {
            $media->setUrl($versionData[$media->getVersion()]['url']);
        }

        return $media;
    }

    /**
     * Returns a user for a given user-id.
     *
     * @param $userId
     *
     * @return UserInterface
     */
    protected function getUser($userId)
    {
        return $this->userRepository->findUserById($userId);
    }

    /**
     * {@inheritdoc}
     */
    public function getUrl($id, $fileName, $version)
    {
        return str_replace(
            [
                '{id}',
                '{slug}',
            ],
            [
                $id,
                $fileName,
            ],
            $this->downloadPath
        ) . '?v=' . $version;
    }

    /**
     * Returns current user or null if no user is loggedin.
     *
     * @return UserInterface|void
     */
    protected function getCurrentUser()
    {
        if (!$this->tokenStorage) {
            return;
        }

        if (!$this->tokenStorage->getToken()) {
            return;
        }

        return $this->tokenStorage->getToken()->getUser();
    }

    /**
     * Returns file name without special characters and preserves file extension.
     *
     * @param $originalFileName
     *
     * @return string
     */
    private function getNormalizedFileName($originalFileName)
    {
        if (strpos($originalFileName, '.') !== false) {
            $pathParts = pathinfo($originalFileName);
            $fileName = $this->pathCleaner->cleanup($pathParts['filename']);
            $fileName .= '.' . $pathParts['extension'];
        } else {
            $fileName = $this->pathCleaner->cleanup($originalFileName);
        }

        return $fileName;
    }
}
