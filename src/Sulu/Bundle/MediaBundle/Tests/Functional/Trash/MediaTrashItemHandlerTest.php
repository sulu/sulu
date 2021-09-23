<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Tests\Functional\Trash;

use Doctrine\ORM\EntityManagerInterface;
use Sulu\Bundle\AudienceTargetingBundle\Entity\TargetGroup;
use Sulu\Bundle\AudienceTargetingBundle\Entity\TargetGroupInterface;
use Sulu\Bundle\CategoryBundle\Entity\Category;
use Sulu\Bundle\CategoryBundle\Entity\CategoryInterface;
use Sulu\Bundle\CategoryBundle\Trash\CategoryTrashItemHandler;
use Sulu\Bundle\MediaBundle\Entity\Collection;
use Sulu\Bundle\MediaBundle\Entity\CollectionInterface;
use Sulu\Bundle\MediaBundle\Entity\CollectionType;
use Sulu\Bundle\MediaBundle\Entity\File;
use Sulu\Bundle\MediaBundle\Entity\FileVersion;
use Sulu\Bundle\MediaBundle\Entity\FileVersionContentLanguage;
use Sulu\Bundle\MediaBundle\Entity\FileVersionMeta;
use Sulu\Bundle\MediaBundle\Entity\FileVersionPublishLanguage;
use Sulu\Bundle\MediaBundle\Entity\FormatOptions;
use Sulu\Bundle\MediaBundle\Entity\Media;
use Sulu\Bundle\MediaBundle\Entity\MediaInterface;
use Sulu\Bundle\MediaBundle\Entity\MediaType;
use Sulu\Bundle\MediaBundle\Media\Storage\StorageInterface;
use Sulu\Bundle\MediaBundle\Tests\Functional\Traits\CreateUploadedFileTrait;
use Sulu\Bundle\MediaBundle\Trash\MediaTrashItemHandler;
use Sulu\Bundle\TagBundle\Entity\Tag;
use Sulu\Bundle\TagBundle\Tag\TagInterface;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Symfony\Component\Filesystem\Filesystem;

class MediaTrashItemHandlerTest extends SuluTestCase
{
    use CreateUploadedFileTrait;

    /**
     * @var MediaTrashItemHandler
     */
    private $mediaTrashItemHandler;

    /**
     * @var StorageInterface
     */
    private $storage;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    public function setUp(): void
    {
        static::purgeDatabase();
        $this->bootKernelAndSetServices();
    }

    public function bootKernelAndSetServices(): void
    {
        static::bootKernel();

        $this->mediaTrashItemHandler = static::getContainer()->get('sulu_media.media_trash_item_handler');
        $this->storage = static::getContainer()->get('sulu_media.storage');
        $this->filesystem = new Filesystem();
        $this->entityManager = static::getEntityManager();
    }

    public function testStoreAndRestore(): void
    {
        $tag1 = $this->createTag('tag-1');
        $tag2 = $this->createTag('tag-2');

        $category1 = $this->createCategory('category-1');
        $category2 = $this->createCategory('category-2');

        $targetGroup1 = $this->createTargetGroup('target-group-1');
        $targetGroup2 = $this->createTargetGroup('target-group-2');

        $collection1 = $this->createCollection();
        $collection2 = $this->createCollection();

        $mediaType = new MediaType();
        $mediaType->setId(2);
        $mediaType->setName('image');

        $uploadedFile1 = $this->createUploadedFileImage();
        $file1StorageOptions = $this->storage->save($uploadedFile1->getPathname(), 'testStoreAndRestore-1');

        $uploadedFile2 = $this->createUploadedFileImage();
        $file2StorageOptions = $this->storage->save($uploadedFile2->getPathname(), 'testStoreAndRestore-2');

        $previewImageMedia = new Media();
        $previewImageMedia->setCollection($collection1);
        $previewImageMedia->setType($mediaType);

        $media1 = new Media();
        $media1->setCollection($collection1);
        $media1->setType($mediaType);
        $media1->setPreviewImage($previewImageMedia);

        $media1File1 = new File();
        $media1->addFile($media1File1);
        $media1File1->setMedia($media1);
        $media1File1->setVersion(2);

        $media1File1Version1 = new FileVersion();
        $media1File1->addFileVersion($media1File1Version1);
        $media1File1Version1->setFile($media1File1);
        $media1File1Version1->setName('file-version-1');
        $media1File1Version1->setVersion(1);
        $media1File1Version1->setSize(456);
        $media1File1Version1->setDownloadCounter(22);
        $media1File1Version1->setStorageOptions($file1StorageOptions);
        $media1File1Version1->setMimeType('mime-type-1');
        $media1File1Version1->setFocusPointX(101);
        $media1File1Version1->setFocusPointY(202);

        $media1File1Version1Meta1 = new FileVersionMeta();
        $media1File1Version1->addMeta($media1File1Version1Meta1);
        $media1File1Version1->setDefaultMeta($media1File1Version1Meta1);
        $media1File1Version1Meta1->setFileVersion($media1File1Version1);
        $media1File1Version1Meta1->setTitle('file-version-1-title-de');
        $media1File1Version1Meta1->setLocale('de');

        $media1File1Version2 = new FileVersion();
        $media1File1->addFileVersion($media1File1Version2);
        $media1File1Version2->setFile($media1File1);
        $media1File1Version2->setName('file-version-2');
        $media1File1Version2->setVersion(2);
        $media1File1Version2->setSize(123);
        $media1File1Version2->setDownloadCounter(33);
        $media1File1Version2->setStorageOptions($file2StorageOptions);
        $media1File1Version2->setMimeType('mime-type-2');
        $media1File1Version2->setProperties(['property-1' => 'value-1']);
        $media1File1Version2->setFocusPointX(35);
        $media1File1Version2->setFocusPointY(45);

        $media1File1Version2->addTag($tag1);
        $media1File1Version2->addTag($tag2);
        $media1File1Version2->addCategory($category1);
        $media1File1Version2->addCategory($category2);
        $media1File1Version2->addTargetGroup($targetGroup1);
        $media1File1Version2->addTargetGroup($targetGroup2);

        $media1File1Version2ContentLanguage1 = new FileVersionContentLanguage();
        $media1File1Version2->addContentLanguage($media1File1Version2ContentLanguage1);
        $media1File1Version2ContentLanguage1->setFileVersion($media1File1Version2);
        $media1File1Version2ContentLanguage1->setLocale('de');

        $media1File1Version2ContentLanguage2 = new FileVersionContentLanguage();
        $media1File1Version2->addContentLanguage($media1File1Version2ContentLanguage2);
        $media1File1Version2ContentLanguage2->setFileVersion($media1File1Version2);
        $media1File1Version2ContentLanguage2->setLocale('ru');

        $media1File1Version2PublishLanguage1 = new FileVersionPublishLanguage();
        $media1File1Version2->addPublishLanguage($media1File1Version2PublishLanguage1);
        $media1File1Version2PublishLanguage1->setFileVersion($media1File1Version2);
        $media1File1Version2PublishLanguage1->setLocale('de');

        $media1File1Version2Meta1 = new FileVersionMeta();
        $media1File1Version2->addMeta($media1File1Version2Meta1);
        $media1File1Version2->setDefaultMeta($media1File1Version2Meta1);
        $media1File1Version2Meta1->setFileVersion($media1File1Version2);
        $media1File1Version2Meta1->setTitle('file-version-2-title-de');
        $media1File1Version2Meta1->setDescription('file-version-2-description-de');
        $media1File1Version2Meta1->setCopyright('file-version-2-copyright-de');
        $media1File1Version2Meta1->setCredits('file-version-2-credits-de');
        $media1File1Version2Meta1->setLocale('de');

        $media1File1Version2Meta2 = new FileVersionMeta();
        $media1File1Version2->addMeta($media1File1Version2Meta2);
        $media1File1Version2Meta2->setFileVersion($media1File1Version2);
        $media1File1Version2Meta2->setTitle('file-version-2-title-en');
        $media1File1Version2Meta2->setDescription('file-version-2-description-en');
        $media1File1Version2Meta2->setCopyright('file-version-2-copyright-en');
        $media1File1Version2Meta2->setCredits('file-version-2-credits-en');
        $media1File1Version2Meta2->setLocale('en');

        $media1File1Version2FormatOptions1 = new FormatOptions();
        $media1File1Version2->addFormatOptions($media1File1Version2FormatOptions1);
        $media1File1Version2FormatOptions1->setFileVersion($media1File1Version2);
        $media1File1Version2FormatOptions1->setFormatKey('format-key-1');
        $media1File1Version2FormatOptions1->setCropHeight(200);
        $media1File1Version2FormatOptions1->setCropWidth(100);
        $media1File1Version2FormatOptions1->setCropX(50);
        $media1File1Version2FormatOptions1->setCropY(75);

        $media1File1Version2FormatOptions2 = new FormatOptions();
        $media1File1Version2->addFormatOptions($media1File1Version2FormatOptions2);
        $media1File1Version2FormatOptions2->setFileVersion($media1File1Version2);
        $media1File1Version2FormatOptions2->setFormatKey('format-key-2');
        $media1File1Version2FormatOptions2->setCropHeight(2000);
        $media1File1Version2FormatOptions2->setCropWidth(1000);
        $media1File1Version2FormatOptions2->setCropX(500);
        $media1File1Version2FormatOptions2->setCropY(750);

        $this->entityManager->persist($mediaType);
        $this->entityManager->persist($previewImageMedia);
        $this->entityManager->persist($media1);
        $this->entityManager->flush();

        $originalMediaId = $media1->getId();
        static::assertCount(2, $this->entityManager->getRepository(MediaInterface::class)->findAll());
        static::assertTrue($this->filesystem->exists($this->storage->getPath($file1StorageOptions)));
        static::assertTrue($this->filesystem->exists($this->storage->getPath($file2StorageOptions)));

        $trashItem = $this->mediaTrashItemHandler->store($media1);
        $this->entityManager->remove($media1);
        $this->entityManager->flush();
        $this->entityManager->clear();

        static::assertSame($originalMediaId, (int) $trashItem->getResourceId());
        static::assertSame('file-version-2-title-de', $trashItem->getResourceTitle());
        static::assertSame('file-version-2-title-de', $trashItem->getResourceTitle('de'));
        static::assertSame('file-version-2-title-en', $trashItem->getResourceTitle('en'));
        static::assertCount(1, $this->entityManager->getRepository(MediaInterface::class)->findAll());
        static::assertFalse($this->filesystem->exists($this->storage->getPath($file1StorageOptions)));
        static::assertFalse($this->filesystem->exists($this->storage->getPath($file2StorageOptions)));

        // the CategoryTrashItemHandler::restore method changes the id generator for the entity to restore the original id
        // this works only if no entity of the same type was persisted before, because doctrine caches the insert sql
        // to clear the cached insert statement, we need to reboot the kernel of the application
        // this problem does not occur during normal usage because restoring is a separate request with a fresh kernel
        $this->bootKernelAndSetServices();

        /** @var MediaInterface $restoredMedia */
        $restoredMedia = $this->mediaTrashItemHandler->restore($trashItem, ['collectionId' => $collection2->getId()]);
        static::assertCount(2, $this->entityManager->getRepository(MediaInterface::class)->findAll());
        static::assertSame($originalMediaId, $restoredMedia->getId());
        static::assertSame($collection2->getId(), $restoredMedia->getCollection()->getId());
        static::assertSame($mediaType->getId(), $restoredMedia->getType()->getId());
        static::assertTrue($this->filesystem->exists($this->storage->getPath($file1StorageOptions)));
        static::assertTrue($this->filesystem->exists($this->storage->getPath($file2StorageOptions)));

        /** @var File $restoredFile1 */
        $restoredFile1 = $restoredMedia->getFiles()[0];
        static::assertSame(2, $restoredFile1->getVersion());
        static::assertCount(2, $restoredFile1->getFileVersions());

        /** @var FileVersion $restoredFile1Version1 */
        $restoredFile1Version1 = $restoredFile1->getFileVersion(1);
        static::assertSame('file-version-1', $restoredFile1Version1->getName());
        static::assertSame(1, $restoredFile1Version1->getVersion());
        static::assertSame(456, $restoredFile1Version1->getSize());
        static::assertSame(22, $restoredFile1Version1->getDownloadCounter());
        static::assertSame($file1StorageOptions, $restoredFile1Version1->getStorageOptions());
        static::assertSame('mime-type-1', $restoredFile1Version1->getMimeType());
        static::assertSame([], $restoredFile1Version1->getProperties());
        static::assertSame(101, $restoredFile1Version1->getFocusPointX());
        static::assertSame(202, $restoredFile1Version1->getFocusPointY());
        static::assertSame('de', $restoredFile1Version1->getDefaultMeta()->getLocale());
        static::assertCount(0, $restoredFile1Version1->getTags());
        static::assertCount(0, $restoredFile1Version1->getCategories());
        static::assertCount(0, $restoredFile1Version1->getTargetGroups());
        static::assertCount(0, $restoredFile1Version1->getContentLanguages());
        static::assertCount(0, $restoredFile1Version1->getPublishLanguages());
        static::assertCount(1, $restoredFile1Version1->getMeta());
        static::assertCount(0, $restoredFile1Version1->getFormatOptions());

        /** @var FileVersionMeta $restoredFile1Version1Meta1 */
        $restoredFile1Version1Meta1 = $restoredFile1Version1->getMeta()[0];
        static::assertSame('de', $restoredFile1Version1Meta1->getLocale());
        static::assertSame('file-version-1-title-de', $restoredFile1Version1Meta1->getTitle());

        /** @var FileVersion $restoredFile1Version2 */
        $restoredFile1Version2 = $restoredFile1->getFileVersion(2);
        static::assertSame('file-version-2', $restoredFile1Version2->getName());
        static::assertSame(2, $restoredFile1Version2->getVersion());
        static::assertSame(123, $restoredFile1Version2->getSize());
        static::assertSame(33, $restoredFile1Version2->getDownloadCounter());
        static::assertSame($file2StorageOptions, $restoredFile1Version2->getStorageOptions());
        static::assertSame('mime-type-2', $restoredFile1Version2->getMimeType());
        static::assertSame(['property-1' => 'value-1'], $restoredFile1Version2->getProperties());
        static::assertSame(35, $restoredFile1Version2->getFocusPointX());
        static::assertSame(45, $restoredFile1Version2->getFocusPointY());
        static::assertCount(2, $restoredFile1Version2->getMeta());
        static::assertSame('de', $restoredFile1Version2->getDefaultMeta()->getLocale());
        static::assertCount(2, $restoredFile1Version2->getFormatOptions());

        static::assertCount(2, $restoredFile1Version2->getTags());
        static::assertNotNull($restoredFile1Version2->getTags()[0]);
        static::assertNotNull($restoredFile1Version2->getTags()[1]);
        static::assertSame($tag1->getId(), $restoredFile1Version2->getTags()[0]->getId());
        static::assertSame($tag2->getId(), $restoredFile1Version2->getTags()[1]->getId());

        static::assertCount(2, $restoredFile1Version2->getCategories());
        static::assertNotNull($restoredFile1Version2->getCategories()[0]);
        static::assertNotNull($restoredFile1Version2->getCategories()[1]);
        static::assertSame($category1->getId(), $restoredFile1Version2->getCategories()[0]->getId());
        static::assertSame($category2->getId(), $restoredFile1Version2->getCategories()[1]->getId());

        static::assertCount(2, $restoredFile1Version2->getTargetGroups());
        static::assertNotNull($restoredFile1Version2->getTargetGroups()[0]);
        static::assertNotNull($restoredFile1Version2->getTargetGroups()[1]);
        static::assertSame($targetGroup1->getId(), $restoredFile1Version2->getTargetGroups()[0]->getId());
        static::assertSame($targetGroup2->getId(), $restoredFile1Version2->getTargetGroups()[1]->getId());

        static::assertCount(2, $restoredFile1Version2->getContentLanguages());
        static::assertNotNull($restoredFile1Version2->getContentLanguages()[0]);
        static::assertNotNull($restoredFile1Version2->getContentLanguages()[1]);
        static::assertSame('de', $restoredFile1Version2->getContentLanguages()[0]->getLocale());
        static::assertSame('ru', $restoredFile1Version2->getContentLanguages()[1]->getLocale());

        static::assertCount(1, $restoredFile1Version2->getPublishLanguages());
        static::assertNotNull($restoredFile1Version2->getContentLanguages()[0]);
        static::assertSame('de', $restoredFile1Version2->getContentLanguages()[0]->getLocale());

        /** @var FileVersionMeta $restoredFile1Version2Meta1 */
        $restoredFile1Version2Meta1 = $restoredFile1Version2->getMeta()[0];
        static::assertSame('file-version-2-title-de', $restoredFile1Version2Meta1->getTitle());
        static::assertSame('file-version-2-description-de', $restoredFile1Version2Meta1->getDescription());
        static::assertSame('file-version-2-copyright-de', $restoredFile1Version2Meta1->getCopyright());
        static::assertSame('file-version-2-credits-de', $restoredFile1Version2Meta1->getCredits());
        static::assertSame('de', $restoredFile1Version2Meta1->getLocale());

        /** @var FileVersionMeta $restoredFile1Version2Meta2 */
        $restoredFile1Version2Meta2 = $restoredFile1Version2->getMeta()[1];
        static::assertSame('file-version-2-title-en', $restoredFile1Version2Meta2->getTitle());
        static::assertSame('file-version-2-description-en', $restoredFile1Version2Meta2->getDescription());
        static::assertSame('file-version-2-copyright-en', $restoredFile1Version2Meta2->getCopyright());
        static::assertSame('file-version-2-credits-en', $restoredFile1Version2Meta2->getCredits());
        static::assertSame('en', $restoredFile1Version2Meta2->getLocale());

        /** @var FormatOptions $restoredFile1Version2FormatOptions1 */
        $restoredFile1Version2FormatOptions1 = $restoredFile1Version2->getFormatOptions()['format-key-1'];
        static::assertSame('format-key-1', $restoredFile1Version2FormatOptions1->getFormatKey());
        static::assertSame(200, $restoredFile1Version2FormatOptions1->getCropHeight());
        static::assertSame(100, $restoredFile1Version2FormatOptions1->getCropWidth());
        static::assertSame(50, $restoredFile1Version2FormatOptions1->getCropX());
        static::assertSame(75, $restoredFile1Version2FormatOptions1->getCropY());

        /** @var FormatOptions $restoredFile1Version2FormatOptions2 */
        $restoredFile1Version2FormatOptions2 = $restoredFile1Version2->getFormatOptions()['format-key-2'];
        static::assertSame('format-key-2', $restoredFile1Version2FormatOptions2->getFormatKey());
        static::assertSame(2000, $restoredFile1Version2FormatOptions2->getCropHeight());
        static::assertSame(1000, $restoredFile1Version2FormatOptions2->getCropWidth());
        static::assertSame(500, $restoredFile1Version2FormatOptions2->getCropX());
        static::assertSame(750, $restoredFile1Version2FormatOptions2->getCropY());
    }

    public function testStoreAndRemove(): void
    {
        $collection1 = $this->createCollection();

        $mediaType = new MediaType();
        $mediaType->setId(2);
        $mediaType->setName('image');

        $uploadedFile1 = $this->createUploadedFileImage();
        $file1StorageOptions = $this->storage->save($uploadedFile1->getPathname(), 'testStoreAndRemove-1');
        $file1TrashStorageOptions = \array_merge($file1StorageOptions, ['directory' => 'trash']);

        $uploadedFile2 = $this->createUploadedFileImage();
        $file2StorageOptions = $this->storage->save($uploadedFile2->getPathname(), 'testStoreAndRemove-2');
        $file2TrashStorageOptions = \array_merge($file2StorageOptions, ['directory' => 'trash']);

        $media1 = new Media();
        $media1->setCollection($collection1);
        $media1->setType($mediaType);

        $media1File1 = new File();
        $media1->addFile($media1File1);
        $media1File1->setMedia($media1);
        $media1File1->setVersion(2);

        $media1File1Version1 = new FileVersion();
        $media1File1->addFileVersion($media1File1Version1);
        $media1File1Version1->setFile($media1File1);
        $media1File1Version1->setName('file-version-1');
        $media1File1Version1->setVersion(1);
        $media1File1Version1->setSize(100);
        $media1File1Version1->setStorageOptions($file1StorageOptions);

        $media1File1Version1Meta1 = new FileVersionMeta();
        $media1File1Version1->addMeta($media1File1Version1Meta1);
        $media1File1Version1->setDefaultMeta($media1File1Version1Meta1);
        $media1File1Version1Meta1->setFileVersion($media1File1Version1);
        $media1File1Version1Meta1->setTitle('file-version-1-title-de');
        $media1File1Version1Meta1->setLocale('de');

        $media1File1Version2 = new FileVersion();
        $media1File1->addFileVersion($media1File1Version2);
        $media1File1Version2->setFile($media1File1);
        $media1File1Version2->setName('file-version-2');
        $media1File1Version2->setVersion(1);
        $media1File1Version2->setSize(100);
        $media1File1Version2->setStorageOptions($file2StorageOptions);

        $media1File1Version2Meta1 = new FileVersionMeta();
        $media1File1Version2->addMeta($media1File1Version2Meta1);
        $media1File1Version2->setDefaultMeta($media1File1Version2Meta1);
        $media1File1Version2Meta1->setFileVersion($media1File1Version2);
        $media1File1Version2Meta1->setTitle('file-version-2-title-de');
        $media1File1Version2Meta1->setLocale('de');

        $this->entityManager->persist($mediaType);
        $this->entityManager->persist($media1);
        $this->entityManager->flush();

        static::assertTrue($this->filesystem->exists($this->storage->getPath($file1StorageOptions)));
        static::assertTrue($this->filesystem->exists($this->storage->getPath($file2StorageOptions)));
        static::assertFalse($this->filesystem->exists($this->storage->getPath($file1TrashStorageOptions)));
        static::assertFalse($this->filesystem->exists($this->storage->getPath($file2TrashStorageOptions)));

        $trashItem = $this->mediaTrashItemHandler->store($media1);

        static::assertFalse($this->filesystem->exists($this->storage->getPath($file1StorageOptions)));
        static::assertFalse($this->filesystem->exists($this->storage->getPath($file2StorageOptions)));
        static::assertTrue($this->filesystem->exists($this->storage->getPath($file1TrashStorageOptions)));
        static::assertTrue($this->filesystem->exists($this->storage->getPath($file2TrashStorageOptions)));

        $this->mediaTrashItemHandler->remove($trashItem);

        static::assertFalse($this->filesystem->exists($this->storage->getPath($file1StorageOptions)));
        static::assertFalse($this->filesystem->exists($this->storage->getPath($file2StorageOptions)));
        static::assertFalse($this->filesystem->exists($this->storage->getPath($file1TrashStorageOptions)));
        static::assertFalse($this->filesystem->exists($this->storage->getPath($file2TrashStorageOptions)));
    }

    protected function createCollection(): CollectionInterface
    {
        $collectionType = new CollectionType();
        $collectionType->setName('Default Collection Type');
        $collectionType->setDescription('Default Collection Type');

        $collection = new Collection();
        $collection->setType($collectionType);

        $this->entityManager->persist($collection);
        $this->entityManager->persist($collectionType);
        $this->entityManager->flush();

        return $collection;
    }

    protected function createTag(string $name): TagInterface
    {
        $tag = new Tag();
        $tag->setName($name);

        $this->entityManager->persist($tag);
        $this->entityManager->flush();

        return $tag;
    }

    protected function createCategory(string $key): CategoryInterface
    {
        $category = new Category();
        $category->setKey($key);
        $category->setDefaultLocale('en');

        $this->entityManager->persist($category);
        $this->entityManager->flush();

        return $category;
    }

    protected function createTargetGroup(string $name): TargetGroupInterface
    {
        $targetGroup = new TargetGroup();
        $targetGroup->setTitle($name);
        $targetGroup->setPriority(1);

        $this->entityManager->persist($targetGroup);
        $this->entityManager->flush();

        return $targetGroup;
    }
}
