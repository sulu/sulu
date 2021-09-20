<?php

declare(strict_types=1);

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Trash;

use Doctrine\ORM\EntityManagerInterface;
use Sulu\Bundle\ActivityBundle\Application\Collector\DomainEventCollectorInterface;
use Sulu\Bundle\CategoryBundle\Entity\CategoryInterface;
use Sulu\Bundle\MediaBundle\Admin\MediaAdmin;
use Sulu\Bundle\MediaBundle\Domain\Event\MediaRestoredEvent;
use Sulu\Bundle\MediaBundle\Entity\Collection;
use Sulu\Bundle\MediaBundle\Entity\CollectionInterface;
use Sulu\Bundle\MediaBundle\Entity\File;
use Sulu\Bundle\MediaBundle\Entity\FileVersion;
use Sulu\Bundle\MediaBundle\Entity\FileVersionContentLanguage;
use Sulu\Bundle\MediaBundle\Entity\FileVersionMeta;
use Sulu\Bundle\MediaBundle\Entity\FileVersionPublishLanguage;
use Sulu\Bundle\MediaBundle\Entity\FormatOptions;
use Sulu\Bundle\MediaBundle\Entity\MediaInterface;
use Sulu\Bundle\MediaBundle\Entity\MediaRepositoryInterface;
use Sulu\Bundle\MediaBundle\Entity\MediaType;
use Sulu\Bundle\TagBundle\Tag\TagInterface;
use Sulu\Bundle\TrashBundle\Application\DoctrineRestoreHelper\DoctrineRestoreHelperInterface;
use Sulu\Bundle\TrashBundle\Application\TrashItemHandler\RestoreTrashItemHandlerInterface;
use Sulu\Bundle\TrashBundle\Application\TrashItemHandler\StoreTrashItemHandlerInterface;
use Sulu\Bundle\TrashBundle\Domain\Model\TrashItemInterface;
use Sulu\Bundle\TrashBundle\Domain\Repository\TrashItemRepositoryInterface;
use Sulu\Component\Security\Authentication\UserInterface;
use Webmozart\Assert\Assert;

final class MediaTrashItemHandler implements StoreTrashItemHandlerInterface, RestoreTrashItemHandlerInterface
{
    /**
     * @var TrashItemRepositoryInterface
     */
    private $trashItemRepository;

    /**
     * @var MediaRepositoryInterface
     */
    private $mediaRepository;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var DoctrineRestoreHelperInterface
     */
    private $doctrineRestoreHelper;

    /**
     * @var DomainEventCollectorInterface
     */
    private $domainEventCollector;

    public function __construct(
        TrashItemRepositoryInterface $trashItemRepository,
        MediaRepositoryInterface $mediaRepository,
        EntityManagerInterface $entityManager,
        DoctrineRestoreHelperInterface $doctrineRestoreHelper,
        DomainEventCollectorInterface $domainEventCollector
    ) {
        $this->trashItemRepository = $trashItemRepository;
        $this->mediaRepository = $mediaRepository;
        $this->entityManager = $entityManager;
        $this->doctrineRestoreHelper = $doctrineRestoreHelper;
        $this->domainEventCollector = $domainEventCollector;
    }

    /**
     * @param MediaInterface $media
     */
    public function store(object $media): TrashItemInterface
    {
        Assert::isInstanceOf($media, MediaInterface::class);

        // TODO: move original image file

        $creator = $media->getCreator();
        $previewImage = $media->getPreviewImage();

        $data = [
            'collectionId' => $media->getCollection()->getId(),
            'typeId' => $media->getType()->getId(),
            'previewImageId' => $previewImage ? $previewImage->getId() : null,
            'created' => $media->getCreated()->format('c'),
            'creatorId' => $creator ? $creator->getId() : null,
            'files' => [],
        ];
        $mediaTitles = [];

        /** @var File $file */
        foreach ($media->getFiles() as $file) {
            $creator = $file->getCreator();

            $fileData = [
                'version' => $file->getVersion(),
                'created' => $file->getCreated()->format('c'),
                'creatorId' => $creator ? $creator->getId() : null,
                'fileVersions' => [],
            ];

            /** @var FileVersion $fileVersion */
            foreach ($file->getFileVersions() as $fileVersion) {
                $creator = $fileVersion->getCreator();

                $fileVersionData = [
                    'name' => $fileVersion->getName(),
                    'version' => $fileVersion->getVersion(),
                    'size' => $fileVersion->getSize(),
                    'downloadCounter' => $fileVersion->getDownloadCounter(),
                    'storageOptions' => $fileVersion->getStorageOptions(),
                    'mimeType' => $fileVersion->getMimeType(),
                    'properties' => $fileVersion->getProperties(),
                    'focusPointX' => $fileVersion->getFocusPointX(),
                    'focusPointY' => $fileVersion->getFocusPointY(),
                    'created' => $file->getCreated()->format('c'),
                    'creatorId' => $creator ? $creator->getId() : null,
                    'contentLanguageLocales' => [],
                    'publishLanguageLocales' => [],
                    'meta' => [],
                    'defaultMetaLocale' => $fileVersion->getDefaultMeta()->getLocale(),
                    'formatOptions' => [],
                    'tagIds' => [],
                    'categoryIds' => [],
                ];

                /** @var FileVersionContentLanguage $contentLanguage */
                foreach ($fileVersion->getContentLanguages() as $contentLanguage) {
                    $fileVersionData['contentLanguageLocales'][] = $contentLanguage->getLocale();
                }

                /** @var FileVersionPublishLanguage $publishLanguage */
                foreach ($fileVersion->getPublishLanguages() as $publishLanguage) {
                    $fileVersionData['publishLanguageLocales'][] = $publishLanguage->getLocale();
                }

                /** @var FileVersionMeta $meta */
                foreach ($fileVersion->getMeta() as $meta) {
                    $mediaTitles[$meta->getLocale()] = $meta->getTitle();

                    $fileVersionData['meta'][] = [
                        'title' => $meta->getTitle(),
                        'description' => $meta->getDescription(),
                        'copyright' => $meta->getCopyright(),
                        'credits' => $meta->getCredits(),
                        'locale' => $meta->getLocale(),
                    ];
                }

                /** @var FormatOptions $formatOption */
                foreach ($fileVersion->getFormatOptions() as $formatOption) {
                    $fileVersionData['formatOptions'][] = [
                        'formatKey' => $formatOption->getFormatKey(),
                        'cropHeight' => $formatOption->getCropHeight(),
                        'cropWidth' => $formatOption->getCropWidth(),
                        'cropX' => $formatOption->getCropX(),
                        'cropY' => $formatOption->getCropY(),
                    ];
                }

                /** @var TagInterface $tag */
                foreach ($fileVersion->getTags() as $tag) {
                    $fileVersionData['tagIds'][] = $tag->getId();
                }

                /** @var CategoryInterface $category */
                foreach ($fileVersion->getCategories() as $category) {
                    $fileVersionData['categoryIds'][] = $category->getId();
                }

                $fileData['fileVersions'][] = $fileVersionData;
            }

            $data['files'][] = $fileData;
        }

        return $this->trashItemRepository->create(
            MediaInterface::RESOURCE_KEY,
            (string) $media->getId(),
            $data,
            $mediaTitles,
            MediaAdmin::SECURITY_CONTEXT,
            Collection::class,
            (string) $media->getCollection()->getId()
        );
    }

    public function restore(TrashItemInterface $trashItem, array $restoreFormData): object
    {
        $id = (int) $trashItem->getResourceId();
        $data = $trashItem->getRestoreData();

        $collection = $this->findEntity(CollectionInterface::class, $restoreFormData['collectionId']);
        Assert::isInstanceOf($collection, CollectionInterface::class);

        $type = $this->findEntity(MediaType::class, $data['typeId']);
        Assert::isInstanceOf($type, MediaType::class);

        $media = $this->mediaRepository->createNew();
        $media->setCollection($collection);
        $media->setType($type);
        $media->setPreviewImage($this->findEntity(MediaInterface::class, $data['previewImageId']));
        $media->setCreated(new \DateTime($data['created']));
        $media->setCreator($this->findEntity(UserInterface::class, $data['creatorId']));

        foreach ($data['files'] as $fileData) {
            $file = new File();
            $file->setMedia($media);
            $media->addFile($file);
            $this->entityManager->persist($file);

            $file->setVersion($fileData['version']);
            $file->setCreated(new \DateTime($fileData['created']));
            $file->setCreator($this->findEntity(UserInterface::class, $fileData['creatorId']));

            foreach ($fileData['fileVersions'] as $fileVersionData) {
                $fileVersion = new FileVersion();
                $fileVersion->setFile($file);
                $file->addFileVersion($fileVersion);
                $this->entityManager->persist($fileVersion);

                $fileVersion->setName($fileVersionData['name']);
                $fileVersion->setVersion($fileVersionData['version']);
                $fileVersion->setSize($fileVersionData['size']);
                $fileVersion->setDownloadCounter($fileVersionData['downloadCounter']);
                $fileVersion->setStorageOptions($fileVersionData['storageOptions']);
                $fileVersion->setMimeType($fileVersionData['mimeType']);
                $fileVersion->setProperties($fileVersionData['properties']);
                $fileVersion->setFocusPointX($fileVersionData['focusPointX']);
                $fileVersion->setFocusPointY($fileVersionData['focusPointY']);
                $fileVersion->setCreated(new \DateTime($fileVersionData['created']));
                $fileVersion->setCreator($this->findEntity(UserInterface::class, $fileVersionData['creatorId']));

                foreach ($fileVersionData['contentLanguageLocales'] as $contentLanguageLocale) {
                    $contentLanguage = new FileVersionContentLanguage();
                    $contentLanguage->setFileVersion($fileVersion);
                    $fileVersion->addContentLanguage($contentLanguage);
                    $this->entityManager->persist($contentLanguage);

                    $contentLanguage->setLocale($contentLanguageLocale);
                }

                foreach ($fileVersionData['publishLanguageLocales'] as $publishLanguageLocale) {
                    $publishLanguage = new FileVersionPublishLanguage();
                    $publishLanguage->setFileVersion($fileVersion);
                    $fileVersion->addPublishLanguage($publishLanguage);
                    $this->entityManager->persist($publishLanguage);

                    $publishLanguage->setLocale($publishLanguageLocale);
                }

                foreach ($fileVersionData['meta'] as $metaData) {
                    $meta = new FileVersionMeta();
                    $meta->setFileVersion($fileVersion);
                    $fileVersion->addMeta($meta);
                    $this->entityManager->persist($meta);

                    if ($metaData['locale'] === $fileVersionData['defaultMetaLocale']) {
                        $fileVersion->setDefaultMeta($meta);
                    }

                    $meta->setTitle($metaData['title']);
                    $meta->setDescription($metaData['description']);
                    $meta->setCopyright($metaData['copyright']);
                    $meta->setCredits($metaData['credits']);
                    $meta->setLocale($metaData['locale']);
                }

                foreach ($fileVersionData['formatOptions'] as $formatOptionData) {
                    $formatOption = new FormatOptions();
                    $formatOption->setFileVersion($fileVersion);
                    $fileVersion->addFormatOptions($formatOption);
                    $this->entityManager->persist($formatOption);

                    $formatOption->setFormatKey($formatOptionData['formatKey']);
                    $formatOption->setCropHeight($formatOptionData['cropHeight']);
                    $formatOption->setCropWidth($formatOptionData['cropWidth']);
                    $formatOption->setCropX($formatOptionData['cropX']);
                    $formatOption->setCropY($formatOptionData['cropY']);
                }

                foreach ($fileVersionData['tagIds'] as $tagId) {
                    if ($tag = $this->findEntity(TagInterface::class, $tagId)) {
                        $fileVersion->addTag($tag);
                    }
                }

                foreach ($fileVersionData['categoryIds'] as $categoryId) {
                    if ($category = $this->findEntity(CategoryInterface::class, $categoryId)) {
                        $fileVersion->addCategory($category);
                    }
                }
            }
        }

        $this->domainEventCollector->collect(
            new MediaRestoredEvent($media, $data)
        );

        if (null === $this->mediaRepository->findMediaById($id)) {
            $this->doctrineRestoreHelper->persistAndFlushWithId($media, $id);
        } else {
            $this->entityManager->persist($media);
            $this->entityManager->flush();
        }

        return $media;
    }

    public static function getResourceKey(): string
    {
        return MediaInterface::RESOURCE_KEY;
    }

    /**
     * @template T of object
     *
     * @param class-string<T> $className
     * @param mixed|null $id
     *
     * @return T|null
     */
    private function findEntity(string $className, $id)
    {
        if ($id) {
            return $this->entityManager->find($className, $id);
        }

        return null;
    }
}
