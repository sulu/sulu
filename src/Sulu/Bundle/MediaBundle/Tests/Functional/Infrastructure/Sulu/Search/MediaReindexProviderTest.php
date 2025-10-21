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

namespace Sulu\Bundle\MediaBundle\Tests\Functional\Infrastructure\Sulu\Search;

use CmsIg\Seal\Reindex\ReindexConfig;
use Doctrine\ORM\EntityManagerInterface;
use Sulu\Bundle\MediaBundle\Entity\Collection;
use Sulu\Bundle\MediaBundle\Entity\CollectionMeta;
use Sulu\Bundle\MediaBundle\Entity\CollectionType;
use Sulu\Bundle\MediaBundle\Entity\File;
use Sulu\Bundle\MediaBundle\Entity\FileVersion;
use Sulu\Bundle\MediaBundle\Entity\FileVersionMeta;
use Sulu\Bundle\MediaBundle\Entity\Media;
use Sulu\Bundle\MediaBundle\Entity\MediaInterface;
use Sulu\Bundle\MediaBundle\Entity\MediaType;
use Sulu\Bundle\MediaBundle\Infrastructure\Sulu\Search\MediaReindexProvider;
use Sulu\Bundle\TestBundle\Testing\SetGetPrivatePropertyTrait;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;

class MediaReindexProviderTest extends SuluTestCase
{
    use SetGetPrivatePropertyTrait;

    private EntityManagerInterface $entityManager;
    private MediaReindexProvider $provider;

    private MediaType $imageType;

    private Collection $collection;

    private CollectionType $collectionType;

    private CollectionMeta $collectionMeta;

    protected function setUp(): void
    {
        $this->entityManager = $this->getEntityManager();
        $this->provider = new MediaReindexProvider($this->entityManager);
        $this->purgeDatabase();
        $this->setUpMedia();
        $this->setUpCollection();
    }

    public function testGetIndex(): void
    {
        $this->assertSame('admin', MediaReindexProvider::getIndex());
    }

    public function testTotal(): void
    {
        $this->createMedia('test123');

        $this->entityManager->flush();

        $this->assertSame(1, $this->provider->total());
    }

    public function testProvideAll(): void
    {
        $media1 = $this->createMedia('media EN');
        $media2 = $this->createMedia('media DE', 'de');

        $changedDateString1 = '2023-06-01 15:30:00';
        $createdDateString1 = '2022-06-01 15:30:00';
        $changedDateString2 = '2024-06-01 15:30:00';
        $createdDateString2 = '2022-07-01 15:30:00';

        $connection = self::getEntityManager()->getConnection();
        $sql = 'UPDATE me_media SET changed = :changed, created = :created WHERE id = :id';

        $connection->executeStatement($sql, [
            'changed' => $changedDateString1,
            'created' => $createdDateString1,
            'id' => $media1->getId(),
        ]);

        $connection->executeStatement($sql, [
            'changed' => $changedDateString2,
            'created' => $createdDateString2,
            'id' => $media2->getId(),
        ]);

        $config = ReindexConfig::create()->withIndex('admin');
        /** @var array<array{id: string}> $results */
        $results = \iterator_to_array($this->provider->provide($config));

        $this->assertCount(2, $results);

        $expectedResult = [
            [
                'id' => MediaInterface::RESOURCE_KEY . '::' . $media1->getId() . '::en',
                'resourceKey' => MediaInterface::RESOURCE_KEY,
                'resourceId' => (string) $media1->getId(),
                'mediaId' => (string) $media1->getId(),
                'changedAt' => (new \DateTimeImmutable($changedDateString1))->format('c'),
                'createdAt' => (new \DateTimeImmutable($createdDateString1))->format('c'),
                'title' => 'media EN',
                'locale' => 'en',
            ],
            [
                'id' => MediaInterface::RESOURCE_KEY . '::' . $media2->getId() . '::de',
                'resourceKey' => MediaInterface::RESOURCE_KEY,
                'resourceId' => (string) $media2->getId(),
                'mediaId' => (string) $media2->getId(),
                'changedAt' => (new \DateTimeImmutable($changedDateString2))->format('c'),
                'createdAt' => (new \DateTimeImmutable($createdDateString2))->format('c'),
                'title' => 'media DE',
                'locale' => 'de',
            ],
        ];

        \usort($expectedResult, fn ($a, $b) => \strcmp($a['id'], $b['id']));
        \usort($results, fn ($a, $b) => \strcmp($a['id'], $b['id']));

        $this->assertSame(
            $expectedResult,
            $results,
        );
    }

    public function testProvideWithSpecificIdentifiers(): void
    {
        $media1 = $this->createMedia('Cool Media EN 1');
        $media2 = $this->createMedia('Cool Media DE', 'de');
        $media3 = $this->createMedia('Cool Media EN 2');

        $identifiers = [
            MediaInterface::RESOURCE_KEY . '::' . $media1->getId() . '::en',
            MediaInterface::RESOURCE_KEY . '::' . $media2->getId() . '::de',
        ];

        $config = ReindexConfig::create()
            ->withIndex('admin')
            ->withIdentifiers($identifiers);

        $results = \iterator_to_array($this->provider->provide($config));

        $this->assertCount(2, $results);

        $resultTitles = \array_column($results, 'title');
        $this->assertContains('Cool Media EN 1', $resultTitles);
        $this->assertContains('Cool Media DE', $resultTitles);
        $this->assertNotContains('Cool Media EN 2', $resultTitles);
    }

    protected function setUpMedia(): void
    {
        $this->imageType = new MediaType();
        $this->imageType->setName('image');
        $this->imageType->setDescription('This is an image');

        $this->entityManager->persist($this->imageType);

        $this->entityManager->flush();
    }

    protected function createMedia(string $name, string $locale = 'en'): Media
    {
        $media = new Media();

        $media->setType($this->imageType);
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
        $media->setCollection($this->collection);

        $this->entityManager->persist($media);
        $this->entityManager->persist($file);
        $this->entityManager->persist($fileVersionMeta);
        $this->entityManager->persist($fileVersion);

        $this->entityManager->flush();

        return $media;
    }

    protected function setUpCollection(): void
    {
        $this->collection = new Collection();
        $style = [
            'type' => 'circle', 'color' => '#ffcc00',
        ];

        $this->collection->setStyle(\json_encode($style) ?: null);

        // Create Collection Type
        $this->collectionType = new CollectionType();
        $this->collectionType->setName('Default Collection Type');
        $this->collectionType->setDescription('Default Collection Type');

        $this->collection->setType($this->collectionType);

        // Collection Meta 1
        $this->collectionMeta = new CollectionMeta();
        $this->collectionMeta->setTitle('Test Collection');
        $this->collectionMeta->setDescription('This Description is only for testing');
        $this->collectionMeta->setLocale('en-gb');
        $this->collectionMeta->setCollection($this->collection);

        $this->collection->addMeta($this->collectionMeta);

        $this->entityManager->persist($this->collection);
        $this->entityManager->persist($this->collectionType);
        $this->entityManager->persist($this->collectionMeta);
    }
}
