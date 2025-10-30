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

namespace Sulu\Snippet\Tests\Functional\Infrastructure\Sulu\Search;

use CmsIg\Seal\Reindex\ReindexConfig;
use Doctrine\ORM\EntityManagerInterface;
use Sulu\Bundle\TestBundle\Testing\SetGetPrivatePropertyTrait;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Snippet\Domain\Model\SnippetInterface;
use Sulu\Snippet\Infrastructure\Sulu\Search\SnippetReindexProvider;
use Sulu\Snippet\Tests\Traits\CreateSnippetTrait;

class SnippetReindexProviderTest extends SuluTestCase
{
    use CreateSnippetTrait;
    use SetGetPrivatePropertyTrait;

    private EntityManagerInterface $entityManager;
    private SnippetReindexProvider $provider;

    protected function setUp(): void
    {
        $this->entityManager = $this->getEntityManager();
        $this->provider = new SnippetReindexProvider($this->entityManager);
        $this->purgeDatabase();
    }

    public function testGetIndex(): void
    {
        $this->assertSame('admin', SnippetReindexProvider::getIndex());
    }

    public function testTotal(): void
    {
        $this->assertNull($this->provider->total());
    }

    public function testProvideAll(): void
    {
        $snippet1 = $this->createSnippet([
            'en' => [
                'live' => [
                    'template' => 'snippet',
                    'title' => 'Test Snippet',
                ],
            ],
        ]);
        $snippet2 = $this->createSnippet([
            'en' => [
                'live' => [
                    'template' => 'snippet',
                    'title' => 'Test Snippet 2',
                ],
            ],
            'de' => [
                'draft' => [
                    'template' => 'snippet',
                    'title' => 'Test Schnipsel 2',
                ],
            ],
        ]);

        $createdAt = '2020-06-01 15:30:00';
        $changedDateString1 = '2023-06-01 15:30:00';
        $changedDateString2 = '2024-11-29 15:30:00';

        $connection = self::getEntityManager()->getConnection();
        $sql = 'UPDATE sn_snippet_dimension_contents SET changed = :changed, created = :created WHERE snippetUuid = :uuid';

        $connection->executeStatement($sql, [
            'changed' => $changedDateString1,
            'created' => $createdAt,
            'uuid' => $snippet1->getUuid(),
        ]);

        $connection->executeStatement($sql, [
            'changed' => $changedDateString2,
            'created' => $createdAt,
            'uuid' => $snippet2->getUuid(),
        ]);

        $config = ReindexConfig::create()->withIndex('admin');
        /** @var array<array{id: string}> $results */
        $results = \iterator_to_array($this->provider->provide($config));

        $this->assertCount(3, $results);

        $expectedResult = [
            [
                'id' => SnippetInterface::RESOURCE_KEY . '::' . $snippet2->getUuid() . '::de',
                'resourceKey' => SnippetInterface::RESOURCE_KEY,
                'resourceId' => $snippet2->getUuid(),
                'changedAt' => (new \DateTimeImmutable($changedDateString2))->format('c'),
                'createdAt' => (new \DateTimeImmutable($createdAt))->format('c'),
                'title' => 'Test Schnipsel 2',
                'locale' => 'de',
            ],
            [
                'id' => SnippetInterface::RESOURCE_KEY . '::' . $snippet1->getUuid() . '::en',
                'resourceKey' => SnippetInterface::RESOURCE_KEY,
                'resourceId' => $snippet1->getUuid(),
                'changedAt' => (new \DateTimeImmutable($changedDateString1))->format('c'),
                'createdAt' => (new \DateTimeImmutable($createdAt))->format('c'),
                'title' => 'Test Snippet',
                'locale' => 'en',
            ],
            [
                'id' => SnippetInterface::RESOURCE_KEY . '::' . $snippet2->getUuid() . '::en',
                'resourceKey' => SnippetInterface::RESOURCE_KEY,
                'resourceId' => $snippet2->getUuid(),
                'changedAt' => (new \DateTimeImmutable($changedDateString2))->format('c'),
                'createdAt' => (new \DateTimeImmutable($createdAt))->format('c'),
                'title' => 'Test Snippet 2',
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
        $snippet1 = $this->createSnippet([
            'en' => [
                'live' => [
                    'template' => 'snippet',
                    'title' => 'Cool Snippet 1',
                ],
            ],
            'de' => [
                'live' => [
                    'template' => 'snippet',
                    'title' => 'Cool Snippet DE 1',
                ],
            ],
        ]);
        $snippet2 = $this->createSnippet([
            'en' => [
                'live' => [
                    'template' => 'snippet',
                    'title' => 'Not so cool Snippet 2',
                ],
            ],
            'de' => [
                'draft' => [
                    'template' => 'snippet',
                    'title' => 'Not so cool Snippet DE 2',
                ],
            ],
        ]);

        $createdAt = '2020-06-01 15:30:00';
        $changedDateString1 = '2023-06-01 15:30:00';
        $changedDateString2 = '2024-06-01 15:30:00';

        $connection = self::getEntityManager()->getConnection();
        $sql = 'UPDATE sn_snippet_dimension_contents SET changed = :changed, created = :created WHERE snippetUuid = :uuid';

        $connection->executeStatement($sql, [
            'changed' => $changedDateString1,
            'created' => $createdAt,
            'uuid' => $snippet1->getUuid(),
        ]);

        $connection->executeStatement($sql, [
            'changed' => $changedDateString2,
            'created' => $createdAt,
            'uuid' => $snippet2->getUuid(),
        ]);

        $identifiers = [
            SnippetInterface::RESOURCE_KEY . '::' . $snippet1->getUuid() . '::de',
            SnippetInterface::RESOURCE_KEY . '::' . $snippet2->getUuid() . '::en',
        ];

        $config = ReindexConfig::create()
            ->withIndex('admin')
            ->withIdentifiers($identifiers);

        $results = \iterator_to_array($this->provider->provide($config));

        $this->assertCount(2, $results);

        $resultTitles = \array_column($results, 'title');
        $this->assertContains('Cool Snippet DE 1', $resultTitles);
        $this->assertContains('Not so cool Snippet 2', $resultTitles);
        $this->assertNotContains('Cool Snippet 1', $resultTitles);
        $this->assertNotContains('Not so cool Snippet DE 2', $resultTitles);
    }
}
