<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\CategoryBundle\Tests\Functional\Trash;

use Doctrine\ORM\EntityManagerInterface;
use Sulu\Bundle\CategoryBundle\Entity\Category;
use Sulu\Bundle\CategoryBundle\Entity\CategoryInterface;
use Sulu\Bundle\CategoryBundle\Entity\CategoryMeta;
use Sulu\Bundle\CategoryBundle\Entity\CategoryTranslation;
use Sulu\Bundle\CategoryBundle\Entity\CategoryTranslationInterface;
use Sulu\Bundle\CategoryBundle\Entity\Keyword;
use Sulu\Bundle\CategoryBundle\Trash\CategoryTrashItemHandler;
use Sulu\Bundle\MediaBundle\Entity\Collection;
use Sulu\Bundle\MediaBundle\Entity\CollectionInterface;
use Sulu\Bundle\MediaBundle\Entity\CollectionType;
use Sulu\Bundle\MediaBundle\Entity\File;
use Sulu\Bundle\MediaBundle\Entity\FileVersion;
use Sulu\Bundle\MediaBundle\Entity\FileVersionMeta;
use Sulu\Bundle\MediaBundle\Entity\Media;
use Sulu\Bundle\MediaBundle\Entity\MediaInterface;
use Sulu\Bundle\MediaBundle\Entity\MediaType;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;

class CategoryTrashItemHandlerTest extends SuluTestCase
{
    /**
     * @var CategoryTrashItemHandler
     */
    private $categoryTrashItemHandler;

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

        $this->categoryTrashItemHandler = static::getContainer()->get('sulu_category.category_trash_item_handler');
        $this->entityManager = static::getEntityManager();
    }

    public function testStoreAndRestore(): void
    {
        $collection = $this->createCollection();
        $media1 = $this->createMedia('first media', $collection);
        $media2 = $this->createMedia('second media', $collection);

        $category1 = new Category();
        $category1->setKey('test-key');
        $category1->setDefaultLocale('en');

        $category1Meta1 = new CategoryMeta();
        $category1Meta1->setLocale('en');
        $category1Meta1->setKey('meta-key');
        $category1Meta1->setValue('meta-value');
        $category1Meta1->setCategory($category1);

        $category1TranslationEn = new CategoryTranslation();
        $category1TranslationEn->setTranslation('first category');
        $category1TranslationEn->setDescription('');
        $category1TranslationEn->setLocale('en');
        $category1TranslationEn->setCategory($category1);
        $category1TranslationEn->setMedias([$media1, $media2]);
        $category1->addTranslation($category1TranslationEn);

        $category1TranslationDe = new CategoryTranslation();
        $category1TranslationDe->setTranslation('erste kategorie');
        $category1TranslationDe->setDescription('deutsche beschreibung');
        $category1TranslationDe->setLocale('de');
        $category1TranslationDe->setCategory($category1);
        $category1->addTranslation($category1TranslationDe);

        $keyword1 = new Keyword();
        $keyword1->setKeyword('test keyword');
        $keyword1->setLocale('de');
        $keyword1->addCategoryTranslation($category1TranslationDe);
        $category1TranslationDe->addKeyword($keyword1);

        $category2 = new Category();
        $category2->setDefaultLocale('en');

        $category2TranslationEn = new CategoryTranslation();
        $category2TranslationEn->setTranslation('second category');
        $category2TranslationEn->setLocale('en');
        $category2TranslationEn->setCategory($category2);
        $category2->addTranslation($category2TranslationEn);

        $this->entityManager->persist($category1);
        $this->entityManager->persist($category2);
        $this->entityManager->persist($keyword1);
        $this->entityManager->flush();

        $originalCategoryId = $category1->getId();
        static::assertCount(2, $this->entityManager->getRepository(CategoryInterface::class)->findAll());

        $trashItem = $this->categoryTrashItemHandler->store($category1);
        $this->entityManager->remove($category1);
        $this->entityManager->flush();

        static::assertSame($originalCategoryId, (int) $trashItem->getResourceId());
        static::assertSame('first category', $trashItem->getResourceTitle());
        static::assertSame('first category', $trashItem->getResourceTitle('en'));
        static::assertSame('erste kategorie', $trashItem->getResourceTitle('de'));
        static::assertCount(1, $this->entityManager->getRepository(CategoryInterface::class)->findAll());

        // the CategoryTrashItemHandler::restore method changes the id generator for the entity to restore the original id
        // this works only if no entity of the same type was persisted before, because doctrine caches the insert sql
        // to clear the cached insert statement, we need to reboot the kernel of the application
        // this problem does not occur during normal usage because restoring is a separate request with a fresh kernel
        $this->bootKernelAndSetServices();

        /** @var CategoryInterface $restoredCategory */
        $restoredCategory = $this->categoryTrashItemHandler->restore($trashItem, ['parentId' => $category2->getId()]);
        static::assertCount(2, $this->entityManager->getRepository(CategoryInterface::class)->findAll());
        static::assertSame($originalCategoryId, $restoredCategory->getId());
        static::assertSame('test-key', $restoredCategory->getKey());
        static::assertNotNull($restoredCategory->getParent());
        static::assertSame($category2->getId(), $restoredCategory->getParent()->getId());

        /** @var CategoryTranslationInterface $restoredTranslationEn */
        $restoredTranslationEn = $restoredCategory->findTranslationByLocale('en');
        static::assertSame('first category', $restoredTranslationEn->getTranslation());
        static::assertSame('', $restoredTranslationEn->getDescription());
        static::assertCount(2, $restoredTranslationEn->getMedias());
        static::assertCount(0, $restoredTranslationEn->getKeywords());

        /** @var CategoryTranslationInterface $restoredTranslationDe */
        $restoredTranslationDe = $restoredCategory->findTranslationByLocale('de');
        static::assertSame('erste kategorie', $restoredTranslationDe->getTranslation());
        static::assertSame('deutsche beschreibung', $restoredTranslationDe->getDescription());
        static::assertCount(0, $restoredTranslationDe->getMedias());
        static::assertCount(1, $restoredTranslationDe->getKeywords());
        static::assertSame('test keyword', $restoredTranslationDe->getKeywords()[0]->getKeyword());
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

    protected function createMedia(string $name, CollectionInterface $collection, string $locale = 'en'): MediaInterface
    {
        $mediaType = new MediaType();
        $mediaType->setId(2);
        $mediaType->setName('image');

        $media = new Media();
        $media->setType($mediaType);
        $extension = 'jpeg';
        $mimeType = 'image/jpg';

        // create file
        $file = new File();
        $file->setVersion(1);
        $file->setMedia($media);

        // create file version
        $fileVersion = new FileVersion();
        $fileVersion->setVersion(1);
        $fileVersion->setName($name . '.' . $extension);
        $fileVersion->setMimeType($mimeType);
        $fileVersion->setFile($file);
        $fileVersion->setSize(1124214);
        $fileVersion->setDownloadCounter(2);
        $fileVersion->setChanged(new \DateTimeImmutable('1937-04-20'));
        $fileVersion->setCreated(new \DateTimeImmutable('1937-04-20'));

        // create meta
        $fileVersionMeta = new FileVersionMeta();
        $fileVersionMeta->setLocale($locale);
        $fileVersionMeta->setTitle($name);
        $fileVersionMeta->setFileVersion($fileVersion);

        $fileVersion->addMeta($fileVersionMeta);
        $fileVersion->setDefaultMeta($fileVersionMeta);

        $file->addFileVersion($fileVersion);

        $media->addFile($file);
        $media->setCollection($collection);

        $this->entityManager->persist($mediaType);
        $this->entityManager->persist($media);
        $this->entityManager->persist($file);
        $this->entityManager->persist($fileVersionMeta);
        $this->entityManager->persist($fileVersion);

        return $media;
    }
}
