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

namespace Sulu\Page\Tests\Functional\Infrastructure\Sulu\Search;

use CmsIg\Seal\Reindex\ReindexConfig;
use Doctrine\ORM\EntityManagerInterface;
use Sulu\Bundle\TestBundle\Testing\SetGetPrivatePropertyTrait;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Page\Domain\Model\PageInterface;
use Sulu\Page\Infrastructure\Sulu\Search\PageReindexProvider;
use Sulu\Page\Tests\Traits\CreatePageTrait;

class PageReindexProviderTest extends SuluTestCase
{
    use CreatePageTrait;
    use SetGetPrivatePropertyTrait;

    private EntityManagerInterface $entityManager;
    private PageReindexProvider $provider;

    protected function setUp(): void
    {
        $this->entityManager = $this->getEntityManager();
        $this->provider = new PageReindexProvider($this->entityManager);
        $this->purgeDatabase();
    }

    public function testGetIndex(): void
    {
        $this->assertSame('admin', PageReindexProvider::getIndex());
    }

    public function testTotal(): void
    {
        $this->assertNull($this->provider->total());
    }

    public function testProvideAll(): void
    {
        $page1 = $this->createPage([
            'en' => [
                'live' => [
                    'template' => 'default',
                    'title' => 'Test Page',
                    'url' => '/test-page',
                ],
            ],
        ]);
        $page2 = $this->createPage([
            'en' => [
                'live' => [
                    'template' => 'default',
                    'title' => 'Test Page 2',
                    'url' => '/test-page-2',
                ],
            ],
            'de' => [
                'draft' => [
                    'template' => 'default',
                    'title' => 'Test Schnipsel 2',
                    'url' => '/test-schnipsel-2',
                ],
            ],
        ]);

        $createdAt = '2020-06-01 15:30:00';
        $changedDateString1 = '2023-06-01 15:30:00';
        $changedDateString2 = '2024-11-29 15:30:00';

        $connection = self::getEntityManager()->getConnection();
        $sql = 'UPDATE pa_page_dimension_contents SET changed = :changed, created = :created WHERE pageUuid = :uuid';

        $connection->executeStatement($sql, [
            'changed' => $changedDateString1,
            'created' => $createdAt,
            'uuid' => $page1->getUuid(),
        ]);

        $connection->executeStatement($sql, [
            'changed' => $changedDateString2,
            'created' => $createdAt,
            'uuid' => $page2->getUuid(),
        ]);

        $config = ReindexConfig::create()->withIndex('admin');
        /** @var array<array{id: string}> $results */
        $results = \iterator_to_array($this->provider->provide($config));

        $this->assertCount(3, $results);

        $expectedResult = [
            [
                'id' => PageInterface::RESOURCE_KEY . '::' . $page2->getUuid() . '::de',
                'resourceKey' => PageInterface::RESOURCE_KEY,
                'resourceId' => $page2->getUuid(),
                'changedAt' => (new \DateTimeImmutable($changedDateString2))->format('c'),
                'createdAt' => (new \DateTimeImmutable($createdAt))->format('c'),
                'title' => 'Test Schnipsel 2',
                'locale' => 'de',
            ],
            [
                'id' => PageInterface::RESOURCE_KEY . '::' . $page1->getUuid() . '::en',
                'resourceKey' => PageInterface::RESOURCE_KEY,
                'resourceId' => $page1->getUuid(),
                'changedAt' => (new \DateTimeImmutable($changedDateString1))->format('c'),
                'createdAt' => (new \DateTimeImmutable($createdAt))->format('c'),
                'title' => 'Test Page',
                'locale' => 'en',
            ],
            [
                'id' => PageInterface::RESOURCE_KEY . '::' . $page2->getUuid() . '::en',
                'resourceKey' => PageInterface::RESOURCE_KEY,
                'resourceId' => $page2->getUuid(),
                'changedAt' => (new \DateTimeImmutable($changedDateString2))->format('c'),
                'createdAt' => (new \DateTimeImmutable($createdAt))->format('c'),
                'title' => 'Test Page 2',
                'locale' => 'en',
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
        $page1 = $this->createPage([
            'en' => [
                'live' => [
                    'template' => 'default',
                    'title' => 'Cool Page 1',
                    'url' => '/cool-page-1',
                ],
            ],
            'de' => [
                'live' => [
                    'template' => 'default',
                    'title' => 'Cool Page DE 1',
                    'url' => '/cool-seite-de-1',
                ],
            ],
        ]);
        $page2 = $this->createPage([
            'en' => [
                'live' => [
                    'template' => 'default',
                    'title' => 'Not so cool Page 2',
                    'url' => '/not-so-cool-page-2',
                ],
            ],
            'de' => [
                'draft' => [
                    'template' => 'default',
                    'title' => 'Not so cool Page DE 2',
                    'url' => '/not-so-cool-seite-de-2',
                ],
            ],
        ]);

        $createdAt = '2020-06-01 15:30:00';
        $changedDateString1 = '2023-06-01 15:30:00';
        $changedDateString2 = '2024-06-01 15:30:00';

        $connection = self::getEntityManager()->getConnection();
        $sql = 'UPDATE pa_page_dimension_contents SET changed = :changed, created = :created WHERE pageUuid = :uuid';

        $connection->executeStatement($sql, [
            'changed' => $changedDateString1,
            'created' => $createdAt,
            'uuid' => $page1->getUuid(),
        ]);

        $connection->executeStatement($sql, [
            'changed' => $changedDateString2,
            'created' => $createdAt,
            'uuid' => $page2->getUuid(),
        ]);

        $identifiers = [
            PageInterface::RESOURCE_KEY . '::' . $page1->getUuid() . '::de',
            PageInterface::RESOURCE_KEY . '::' . $page2->getUuid() . '::en',
        ];

        $config = ReindexConfig::create()
            ->withIndex('admin')
            ->withIdentifiers($identifiers);

        $results = \iterator_to_array($this->provider->provide($config));

        $this->assertCount(2, $results);

        $resultTitles = \array_column($results, 'title');
        $this->assertContains('Cool Page DE 1', $resultTitles);
        $this->assertContains('Not so cool Page 2', $resultTitles);
        $this->assertNotContains('Cool Page 1', $resultTitles);
        $this->assertNotContains('Not so cool Page DE 2', $resultTitles);
    }
}
