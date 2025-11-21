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
use Sulu\Bundle\MediaBundle\Admin\MediaAdmin;
use Sulu\Bundle\MediaBundle\Entity\Collection;
use Sulu\Bundle\MediaBundle\Entity\CollectionInterface;
use Sulu\Bundle\MediaBundle\Entity\CollectionMeta;
use Sulu\Bundle\MediaBundle\Infrastructure\Sulu\Search\AdminCollectionReindexProvider;
use Sulu\Bundle\TestBundle\Testing\SetGetPrivatePropertyTrait;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Content\Tests\Functional\Traits\CreateMediaTrait;

class AdminCollectionReindexProviderTest extends SuluTestCase
{
    use SetGetPrivatePropertyTrait;
    use CreateMediaTrait;

    private EntityManagerInterface $entityManager;
    private AdminCollectionReindexProvider $provider;

    protected function setUp(): void
    {
        $this->entityManager = $this->getEntityManager();
        $this->provider = new AdminCollectionReindexProvider($this->entityManager);
        $this->purgeDatabase();
    }

    public function testGetIndex(): void
    {
        $this->assertSame('admin', AdminCollectionReindexProvider::getIndex());
    }

    public function testTotal(): void
    {
        self::createCollection([
            'title' => 'Test Collection 2',
            'locale' => 'en',
        ]);

        $this->entityManager->flush();

        $this->assertSame(1, $this->provider->total());
    }

    public function testProvideAll(): void
    {
        $collection1 = self::createCollection([
            'title' => 'Test Collection 1',
            'locale' => 'en',
            'key' => 'collection-1',
        ]);

        /** @var Collection $collection2 */
        $collection2 = self::createCollection([
            'title' => 'Test Collection 2',
            'locale' => 'de',
            'key' => 'collection-2',
        ]);

        $this->addCollectionMeta($collection2, [
            'title' => 'Test Collection 2 EN',
            'locale' => 'en',
        ]);

        $this->entityManager->flush();

        $changedDateString1 = '2023-06-01 15:30:00';
        $createdDateString1 = '2022-06-01 15:30:00';
        $changedDateString2 = '2024-06-01 15:30:00';
        $createdDateString2 = '2022-07-01 15:30:00';

        $connection = self::getEntityManager()->getConnection();
        $sql = 'UPDATE me_collections SET changed = :changed, created = :created WHERE id = :id';

        $connection->executeStatement($sql, [
            'changed' => $changedDateString1,
            'created' => $createdDateString1,
            'id' => $collection1->getId(),
        ]);

        $connection->executeStatement($sql, [
            'changed' => $changedDateString2,
            'created' => $createdDateString2,
            'id' => $collection2->getId(),
        ]);

        $config = ReindexConfig::create()->withIndex('admin');
        /** @var array<array{id: string}> $results */
        $results = \iterator_to_array($this->provider->provide($config));

        $this->assertCount(3, $results);

        $expectedResult = [
            [
                'id' => CollectionInterface::RESOURCE_KEY . '__' . $collection1->getId() . '__en',
                'resourceKey' => CollectionInterface::RESOURCE_KEY,
                'resourceId' => (string) $collection1->getId(),
                'changedAt' => (new \DateTimeImmutable($changedDateString1))->format('c'),
                'createdAt' => (new \DateTimeImmutable($createdDateString1))->format('c'),
                'title' => 'Test Collection 1',
                'locale' => 'en',
                'securityContext' => MediaAdmin::SECURITY_CONTEXT,
            ],
            [
                'id' => CollectionInterface::RESOURCE_KEY . '__' . $collection2->getId() . '__de',
                'resourceKey' => CollectionInterface::RESOURCE_KEY,
                'resourceId' => (string) $collection2->getId(),
                'changedAt' => (new \DateTimeImmutable($changedDateString2))->format('c'),
                'createdAt' => (new \DateTimeImmutable($createdDateString2))->format('c'),
                'title' => 'Test Collection 2',
                'locale' => 'de',
                'securityContext' => MediaAdmin::SECURITY_CONTEXT,
            ],

            [
                'id' => CollectionInterface::RESOURCE_KEY . '__' . $collection2->getId() . '__en',
                'resourceKey' => CollectionInterface::RESOURCE_KEY,
                'resourceId' => (string) $collection2->getId(),
                'changedAt' => (new \DateTimeImmutable($changedDateString2))->format('c'),
                'createdAt' => (new \DateTimeImmutable($createdDateString2))->format('c'),
                'title' => 'Test Collection 2 EN',
                'locale' => 'en',
                'securityContext' => MediaAdmin::SECURITY_CONTEXT,
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
        $collection1 = self::createCollection([
            'title' => 'Test Collection 1',
            'locale' => 'en',
            'key' => 'collection-1',
        ]);

        /** @var Collection $collection2 */
        $collection2 = self::createCollection([
            'title' => 'Test Collection 2',
            'locale' => 'en',
            'key' => 'collection-2',
        ]);

        $this->addCollectionMeta($collection2, [
            'title' => 'Test Collection 2 DE',
            'locale' => 'de',
        ]);

        $collection3 = self::createCollection([
            'title' => 'Test Collection 3',
            'locale' => 'en',
            'key' => 'collection-3',
        ]);

        $this->entityManager->flush();

        $identifiers = [
            CollectionInterface::RESOURCE_KEY . '__' . $collection1->getId() . '__en',
            CollectionInterface::RESOURCE_KEY . '__' . $collection2->getId() . '__de',
        ];

        $config = ReindexConfig::create()
            ->withIndex('admin')
            ->withIdentifiers($identifiers);

        $results = \iterator_to_array($this->provider->provide($config));

        $this->assertCount(2, $results);

        $resultTitles = \array_column($results, 'title');
        $this->assertContains('Test Collection 1', $resultTitles);
        $this->assertContains('Test Collection 2 DE', $resultTitles);
        $this->assertNotContains('Test Collection 2', $resultTitles);
        $this->assertNotContains('Test Collection 3', $resultTitles);
    }

    /**
     * @param array{
     *     title?: string,
     *     description?: string,
     *     locale?: string,
     * } $data
     */
    protected function addCollectionMeta(Collection $collection, array $data): CollectionInterface
    {
        $data = \array_merge([
            'title' => 'Collection',
            'description' => 'description',
            'locale' => 'en',
        ], $data);

        $collectionMeta = new CollectionMeta();
        $collectionMeta->setTitle($data['title']);
        $collectionMeta->setDescription($data['description']);
        $collectionMeta->setLocale($data['locale']);
        $collectionMeta->setCollection($collection);

        $collection->addMeta($collectionMeta);

        $this->entityManager->persist($collection);
        $this->entityManager->persist($collectionMeta);

        return $collection;
    }
}
