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

use Doctrine\ORM\Tools\Pagination\Paginator;
use Sulu\Bundle\MediaBundle\Entity\MediaRepositoryInterface;
use Sulu\Bundle\MediaBundle\Entity\MediaType;
use Sulu\Bundle\MediaBundle\Media\Exception\MediaNotFoundException;
use Sulu\Bundle\MediaBundle\Media\Exception\MediaTypeNotFoundException;
use Sulu\Bundle\MediaBundle\Media\FormatManager\FormatManagerInterface;
use Sulu\Bundle\TagBundle\Entity\Tag;
use Sulu\Bundle\MediaBundle\Entity\File;
use Sulu\Bundle\MediaBundle\Entity\FileVersion;
use Sulu\Bundle\MediaBundle\Entity\CollectionRepository;
use Sulu\Bundle\MediaBundle\Media\Exception\CollectionNotFoundException;
use Sulu\Bundle\MediaBundle\Media\Exception\FileVersionNotFoundException;
use Sulu\Bundle\MediaBundle\Media\Exception\InvalidFileException;
use Sulu\Bundle\MediaBundle\Media\Storage\StorageInterface;
use Sulu\Bundle\MediaBundle\Media\FileValidator\FileValidatorInterface;
use Sulu\Component\Rest\ListBuilder\Doctrine\FieldDescriptor\DoctrineJoinDescriptor;
use Sulu\Component\Rest\ListBuilder\Doctrine\FieldDescriptor\DoctrineFieldDescriptor;
use Sulu\Component\Security\UserInterface;
use Sulu\Component\Security\UserRepositoryInterface;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\File\File as SymfonyFile;
use Sulu\Bundle\MediaBundle\Entity\Media as MediaEntity;
use Sulu\Bundle\MediaBundle\Api\Media;
use Sulu\Bundle\TagBundle\Tag\TagManagerInterface;
use Sulu\Bundle\MediaBundle\Entity\CollectionRepositoryInterface;

/**
 * @package Sulu\Bundle\MediaBundle\Media\Manager
 */
class DefaultMediaManager implements MediaManagerInterface
{
    const ENTITY_NAME_MEDIA = 'SuluMediaBundle:Media';
    const ENTITY_NAME_MEDIATYPE = 'SuluMediaBundle:MediaType';
    const ENTITY_NAME_FILE = 'SuluMediaBundle:File';
    const ENTITY_NAME_FILEVERSION = 'SuluMediaBundle:FileVersion';
    const ENTITY_NAME_FILEVERSIONMETA = 'SuluMediaBundle:FileVersionMeta';
    const ENTITY_NAME_TAG = 'SuluTagBundle:Tag';
    const ENTITY_NAME_FILEVERSIONCONTENTLANGUAGE = 'SuluMediaBundle:FileVersionContentLanguage';
    const ENTITY_NAME_FILEVERSIONPUBLISHLANGUAGE = 'SuluMediaBundle:FileVersionPublishLanguage';
    const ENTITY_NAME_CONTACT = 'SuluContactBundle:Contact';
    const ENTITY_NAME_USER = 'SuluSecurityBundle:User';

    /**
     * The repository for communication with the database
     * @var MediaRepositoryInterface
     */
    protected $mediaRepository;

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
     * @var array
     */
    private $blockedMimeTypes;

    /**
     * @var array
     */
    private $mediaTypes;

    /**
     * @var TagManagerInterface
     */
    private $tagManager;

    /**
     * @var DoctrineFieldDescriptor[]
     */
    private $fieldDescriptors = array();

    /**
     * @var string
     */
    private $downloadPath;

    /**
     * @var MediaType[]
     */
    private $mediaTypeEntities;

    /**
     * @var int
     */
    public $count;

    /**
     * @param MediaRepositoryInterface $mediaRepository
     * @param CollectionRepository $collectionRepository
     * @param UserRepositoryInterface $userRepository
     * @param ObjectManager $em
     * @param StorageInterface $storage
     * @param FileValidatorInterface $validator
     * @param FormatManagerInterface $formatManager
     * @param TagManagerInterface $tagManager
     * @param string $downloadPath
     * @param string $maxFileSize
     * @param array $blockedMimeTypes
     * @param array $mediaTypes
     */
    public function __construct(
        MediaRepositoryInterface $mediaRepository,
        CollectionRepositoryInterface $collectionRepository,
        UserRepositoryInterface $userRepository,
        ObjectManager $em,
        StorageInterface $storage,
        FileValidatorInterface $validator,
        FormatManagerInterface $formatManager,
        TagmanagerInterface $tagManager,
        $downloadPath,
        $maxFileSize,
        $blockedMimeTypes,
        $mediaTypes
    ) {
        $this->mediaRepository = $mediaRepository;
        $this->collectionRepository = $collectionRepository;
        $this->em = $em;
        $this->userRepository = $userRepository;
        $this->storage = $storage;
        $this->validator = $validator;
        $this->formatManager = $formatManager;
        $this->downloadPath = $downloadPath;
        $this->maxFileSize = $maxFileSize;
        $this->blockedMimeTypes = $blockedMimeTypes;
        $this->mediaTypes = $mediaTypes;
        $this->tagManager = $tagManager;

        $this->initializeFieldDescriptors();
    }

    /**
     * TODO
     * @return array
     */
    private function initializeFieldDescriptors()
    {
        $fieldDescriptors['id'] = new DoctrineFieldDescriptor(
            'id',
            'id',
            self::ENTITY_NAME_MEDIA,
            'public.id',
            array(),
            true,
            false,
            '',
            '50px',
            ''
        );

        $fieldDescriptors['thumbnails'] = new DoctrineFieldDescriptor(
            'id',
            'thumbnails',
            self::ENTITY_NAME_MEDIA,
            'media.media.thumbnails',
            array(),
            false,
            true,
            'thumbnails'
        );

        $fieldDescriptors['name'] = new DoctrineFieldDescriptor(
            'name',
            'name',
            self::ENTITY_NAME_FILEVERSION,
            'public.name',
            array(
                self::ENTITY_NAME_FILE => new DoctrineJoinDescriptor(
                    self::ENTITY_NAME_FILE,
                    self::ENTITY_NAME_MEDIA . '.file'
                ),
                self::ENTITY_NAME_FILEVERSION => new DoctrineJoinDescriptor(
                    self::ENTITY_NAME_FILEVERSION,
                    self::ENTITY_NAME_FILE . '.fileVersion',
                    self::ENTITY_NAME_FILEVERSION . '.version = ' . self::ENTITY_NAME_FILE . '.version'
                )
            )
        );
        $fieldDescriptors['size'] = new DoctrineFieldDescriptor(
            'size',
            'size',
            self::ENTITY_NAME_FILEVERSION,
            'media.media.size',
            array(
                self::ENTITY_NAME_FILE => new DoctrineJoinDescriptor(
                    self::ENTITY_NAME_FILE,
                    self::ENTITY_NAME_MEDIA . '.file'
                ),
                self::ENTITY_NAME_FILEVERSION => new DoctrineJoinDescriptor(
                    self::ENTITY_NAME_FILEVERSION,
                    self::ENTITY_NAME_FILE . '.fileVersion',
                    self::ENTITY_NAME_FILEVERSION . '.version = ' . self::ENTITY_NAME_FILE . '.version'
                )
            ),
            false,
            true,
            'bytes'
        );

        $fieldDescriptors['changed'] = new DoctrineFieldDescriptor(
            'changed',
            'changed',
            self::ENTITY_NAME_FILEVERSION,
            'public.changed',
            array(
                self::ENTITY_NAME_FILE => new DoctrineJoinDescriptor(
                    self::ENTITY_NAME_FILE,
                    self::ENTITY_NAME_MEDIA . '.file'
                ),
                self::ENTITY_NAME_FILEVERSION => new DoctrineJoinDescriptor(
                    self::ENTITY_NAME_FILEVERSION,
                    self::ENTITY_NAME_FILE . '.fileVersion',
                    self::ENTITY_NAME_FILEVERSION . '.version = ' . self::ENTITY_NAME_FILE . '.version'
                )
            ),
            true,
            false,
            'date'
        );

        $fieldDescriptors['created'] = new DoctrineFieldDescriptor(
            'created',
            'created',
            self::ENTITY_NAME_FILEVERSION,
            'public.created',
            array(
                self::ENTITY_NAME_FILE => new DoctrineJoinDescriptor(
                    self::ENTITY_NAME_FILE,
                    self::ENTITY_NAME_MEDIA . '.file'
                ),
                self::ENTITY_NAME_FILEVERSION => new DoctrineJoinDescriptor(
                    self::ENTITY_NAME_FILEVERSION,
                    self::ENTITY_NAME_FILE . '.fileVersion',
                    self::ENTITY_NAME_FILEVERSION . '.version = ' . self::ENTITY_NAME_FILE . '.version'
                )
            ),
            true,
            false,
            'date'
        );

        $fieldDescriptors['title'] = new DoctrineFieldDescriptor(
            'title',
            'title',
            self::ENTITY_NAME_FILEVERSIONMETA,
            'public.title',
            array(
                self::ENTITY_NAME_FILE => new DoctrineJoinDescriptor(
                    self::ENTITY_NAME_FILE,
                    self::ENTITY_NAME_MEDIA . '.file'
                ),
                self::ENTITY_NAME_FILEVERSION => new DoctrineJoinDescriptor(
                    self::ENTITY_NAME_FILEVERSION,
                    self::ENTITY_NAME_FILE . '.fileVersion',
                    self::ENTITY_NAME_FILEVERSION . '.version = ' . self::ENTITY_NAME_FILE . '.version'
                ),
                self::ENTITY_NAME_FILEVERSIONMETA => new DoctrineJoinDescriptor(
                    self::ENTITY_NAME_FILEVERSIONMETA,
                    self::ENTITY_NAME_FILEVERSION . '.meta'
                )
            ),
            false,
            true,
            'title'
        );

        $fieldDescriptors['description'] = new DoctrineFieldDescriptor(
            'description',
            'description',
            self::ENTITY_NAME_FILEVERSIONMETA,
            'media.media.description',
            array(
                self::ENTITY_NAME_FILE => new DoctrineJoinDescriptor(
                    self::ENTITY_NAME_FILE,
                    self::ENTITY_NAME_MEDIA . '.file'
                ),
                self::ENTITY_NAME_FILEVERSION => new DoctrineJoinDescriptor(
                    self::ENTITY_NAME_FILEVERSION,
                    self::ENTITY_NAME_FILE . '.fileVersion',
                    self::ENTITY_NAME_FILEVERSION . '.version = ' . self::ENTITY_NAME_FILE . '.version'
                ),
                self::ENTITY_NAME_FILEVERSIONMETA => new DoctrineJoinDescriptor(
                    self::ENTITY_NAME_FILEVERSIONMETA,
                    self::ENTITY_NAME_FILEVERSION . '.meta'
                )
            )
        );

        $this->fieldDescriptors = $fieldDescriptors;

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
    public function getFieldDescriptors()
    {
        return $this->fieldDescriptors;
    }

    /**
     * {@inheritdoc}
     */
    public function getById($id, $locale)
    {
        $mediaEntity = $this->mediaRepository->findMediaById($id);
        if (!$mediaEntity) {
            throw new MediaNotFoundException('Media with the ID ' . $id . ' was not found.');
        }

        return $this->addFormatsAndUrl(new Media($mediaEntity, $locale, null));
    }

    /**
     * {@inheritdoc}
     */
    public function get($locale, $filter = array(), $limit = null, $offset = null)
    {
        $media = array();
        $mediaEntities = $this->mediaRepository->findMedia($filter, $limit, $offset);
        $this->count = $mediaEntities instanceof Paginator ? $mediaEntities->count() : count($mediaEntities);
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
     * Modified an exists media
     * @param UploadedFile $uploadedFile
     * @param $data
     * @param UserInterface $user
     * @return Media
     * @throws MediaNotFoundException
     * @throws FileVersionNotFoundException
     * @throws FileNotFoundException
     */
    private function modifyMedia($uploadedFile, $data, $user)
    {
        $mediaEntity = $this->mediaRepository->findMediaById($data['id']);
        if (!$mediaEntity) {
            throw new MediaNotFoundException('Media with the ID ' . $data['id'] . ' not found');
        }

        $mediaEntity->setChanged(new \DateTime());
        $mediaEntity->setChanger($user);

        $files = $mediaEntity->getFiles();
        if (!isset($files[0])) {
            throw new FileNotFoundException('File was not found in media entity with the id . ' . $data['id']);
        }

        /**
         * @var File $file
         */
        $file = $files[0]; // currently a media can only have one file

        $file->setChanged(new \Datetime());
        $file->setChanger($user);

        $version = $file->getVersion();

        $currentFileVersion = null;

        /**
         * @var FileVersion $fileVersion
         */
        foreach ($file->getFileVersions() as $fileVersion) {
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
            $version++;
            $this->validator->validate($uploadedFile);

            $data['storageOptions'] = $this->storage->save(
                $uploadedFile->getPathname(),
                $uploadedFile->getClientOriginalName(),
                $version,
                $currentFileVersion->getStorageOptions()
            );
            $data['name'] = $uploadedFile->getClientOriginalName();
            $data['size'] = intval($uploadedFile->getSize());
            $data['mimeType'] = $uploadedFile->getMimeType();
            $data['type'] = array(
                'id' => $this->getMediaType($uploadedFile)
            );
            $data['version'] = $version;

            $fileVersion = clone($currentFileVersion);
            $fileVersion->setChanged(new \Datetime());
            $fileVersion->setCreated(new \Datetime());
            $fileVersion->setChanger($user);
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
        }

        $media = new Media($mediaEntity, $data['locale'], null);

        $media = $this->setDataToMedia(
            $media,
            $data,
            $user
        );

        $mediaEntity = $media->getEntity();
        $this->em->persist($mediaEntity);
        $this->em->flush();

        return $media;
    }

    /**
     * Prepares data
     *
     * @param UploadedFile $uploadedFile
     * @param Array $data
     * @param User $user
     */
    private function buildData($uploadedFile, $data, $user)
    {
        if (!($uploadedFile instanceof UploadedFile)) {
            throw new InvalidFileException('given uploadfile is not of instance UploadFile');
        }

        $this->validator->validate($uploadedFile);

        $data['storageOptions'] = $this->storage->save(
            $uploadedFile->getPathname(),
            $uploadedFile->getClientOriginalName(),
            1
        );
        $data['name'] = $uploadedFile->getClientOriginalName();
        $data['size'] = $uploadedFile->getSize();
        $data['mimeType'] = $uploadedFile->getMimeType();
        $data['type'] = array(
            'id' => $this->getMediaType($uploadedFile)
        );
        return $this->createMedia($data, $user);
    }

    /**
     * Create a new media
     * @param UploadedFile $uploadedFile
     * @param $data
     * @param UserInterface $user
     * @return MediaEntity
     * @throws InvalidFileException
     */
    protected function createMedia($data, $user)
    {
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
        $file->setVersion(1);
        $file->setMedia($mediaEntity);

        $fileVersion = new FileVersion();
        $fileVersion->setCreator($user);
        $fileVersion->setChanger($user);
        $fileVersion->setCreated(new \DateTime());
        $fileVersion->setChanged(new \DateTime());
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

        $mediaEntity = $media->getEntity();
        $this->em->persist($mediaEntity);
        $this->em->flush();

        return $media;
    }

    /**
     * @param SymfonyFile|null $uploadedFile
     * @return object
     */
    protected function getMediaType(SymfonyFile $file)
    {
        $mimeType = $file->getMimeType();
        foreach ($this->mediaTypes as $mediaType) {
            if (in_array($mimeType, $mediaType['mimeTypes']) || in_array('*', $mediaType['mimeTypes'])) {
                $name = $mediaType['type'];
            }
        }

        if (!isset($this->mediaTypeEntities[$name])) {
            $mediaType = $this->em->getRepository('Sulu\Bundle\MediaBundle\Entity\MediaType')->findOneByName($name);
            $this->mediaTypeEntities[$name] = $mediaType;
        }

        return $this->mediaTypeEntities[$name]->getId();
    }

    /**
     * Data can be set over by array
     * @param $media
     * @param $data
     * @param UserInterface $user
     * @return Media
     */
    protected function setDataToMedia(Media $media, $data, $user)
    {
        foreach ($data as $attribute => $value) {
            if ($value || ($attribute === 'tags' && $value !== null) || ($attribute === 'size' && $value !== null)) {
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
                        $media->setCreated($value);
                        break;
                    case 'changer':
                        $media->setChanger($value);
                        break;
                    case 'creator':
                        $media->setCreator($value);
                        break;
                    case 'mimeType':
                        $media->setMimeType($value);
                        break;
                    case 'collection':
                        $collectionEntity = $this->getCollectionById($value);
                        $media->setCollection($collectionEntity); // set parent
                        break;
                    case 'type':
                        if (!isset($value['id'])) {
                            break;
                        }
                        $type = $this->getTypeById($value['id']);
                        $media->setType($type);
                        break;
                }
            }
        }

        return $media;
    }

    /**
     * @param $collectionId
     * @return object
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
     * @param int $typeId
     * @return MediaType
     * @throws MediaTypeNotFoundException
     */
    protected function getTypeById($typeId)
    {
        /** @var MediaType $type */
        $type = $this->em->getRepository('SuluMediaBundle:MediaType')->find($typeId);
        if (!$type) {
            throw new MediaTypeNotFoundException('Collection Type with the ID ' . $typeId . ' not found');
        }

        return $type;
    }

    /**
     * {@inheritdoc}
     */
    public function delete($id)
    {
        $mediaEntity = $this->mediaRepository->findMediaById($id);

        if (!$mediaEntity) {
            throw new MediaNotFoundException('Media with the ID ' . $id . ' not found.');
        }

        /** @var File $file */
        foreach ($mediaEntity->getFiles() as $file) {
            /** @var FileVersion $fileVersion */
            foreach ($file->getFileVersions() as $fileVersion) {
                $this->formatManager->purge(
                    $mediaEntity->getId(),
                    $fileVersion->getName(),
                    $fileVersion->getStorageOptions()
                );
            }
        }

        $this->em->remove($mediaEntity);
        $this->em->flush();
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
     * @param Media $media
     * @return Media
     */
    protected function addFormatsAndUrl(Media $media)
    {
        $media->setFormats(
            $this->formatManager->getFormats(
                $media->getId(),
                $media->getName(),
                $media->getStorageOptions(),
                $media->getVersion()
            )
        );

        $media->setUrl(
            $this->getUrl($media->getId(), $media->getName(), $media->getVersion())
        );

        return $media;
    }

    /**
     * Returns a user for a given user-id
     * @param $userId
     * @return UserInterface
     */
    protected function getUser($userId)
    {
        return $this->userRepository->findUserById($userId);
    }

    /**
     * @param $id
     * @param $fileName
     * @param $version
     * @return string
     */
    protected function getUrl($id, $fileName, $version)
    {
        return str_replace(
            array(
                '{id}',
                '{slug}'
            ),
            array(
                $id,
                $fileName
            ),
            $this->downloadPath
        ) . '?v=' . $version;
    }
}
