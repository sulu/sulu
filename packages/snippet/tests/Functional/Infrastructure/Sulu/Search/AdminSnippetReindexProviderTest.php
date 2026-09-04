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
use Sulu\Bundle\AdminBundle\Metadata\GroupProviderInterface;
use Sulu\Bundle\TestBundle\Testing\SetGetPrivatePropertyTrait;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Snippet\Domain\Model\SnippetInterface;
use Sulu\Snippet\Infrastructure\Sulu\Admin\SnippetAdmin;
use Sulu\Snippet\Infrastructure\Sulu\Search\AdminSnippetReindexProvider;
use Sulu\Snippet\Tests\Traits\CreateSnippetTrait;

class AdminSnippetReindexProviderTest extends SuluTestCase
{
    use CreateSnippetTrait;
    use SetGetPrivatePropertyTrait;

    private EntityManagerInterface $entityManager;
    private AdminSnippetReindexProvider $provider;

    protected function setUp(): void
    {
        $this->entityManager = $this->getEntityManager();
        /** @var GroupProviderInterface $groupProvider */
        $groupProvider = self::getContainer()->get('sulu_admin.metadata_group_provider');
        $this->provider = new AdminSnippetReindexProvider($this->entityManager, $groupProvider);
        $this->purgeDatabase();
    }

    public function testGetIndex(): void
    {
        $this->assertSame('admin', AdminSnippetReindexProvider::getIndex());
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
                'id' => SnippetInterface::RESOURCE_KEY . '__' . $snippet2->getUuid() . '__de',
                'resourceKey' => SnippetInterface::RESOURCE_KEY,
                'resourceId' => $snippet2->getUuid(),
                'changedAt' => (new \DateTimeImmutable($changedDateString2))->format('c'),
                'createdAt' => (new \DateTimeImmutable($createdAt))->format('c'),
                'title' => 'Test Schnipsel 2',
                'locale' => 'de',
                'metadata' => [
                    'group' => 'default',
                ],
                'securityContext' => SnippetAdmin::SECURITY_CONTEXT,
            ],
            [
                'id' => SnippetInterface::RESOURCE_KEY . '__' . $snippet1->getUuid() . '__en',
                'resourceKey' => SnippetInterface::RESOURCE_KEY,
                'resourceId' => $snippet1->getUuid(),
                'changedAt' => (new \DateTimeImmutable($changedDateString1))->format('c'),
                'createdAt' => (new \DateTimeImmutable($createdAt))->format('c'),
                'title' => 'Test Snippet',
                'locale' => 'en',
                'metadata' => [
                    'group' => 'default',
                ],
                'securityContext' => SnippetAdmin::SECURITY_CONTEXT,
            ],
            [
                'id' => SnippetInterface::RESOURCE_KEY . '__' . $snippet2->getUuid() . '__en',
                'resourceKey' => SnippetInterface::RESOURCE_KEY,
                'resourceId' => $snippet2->getUuid(),
                'changedAt' => (new \DateTimeImmutable($changedDateString2))->format('c'),
                'createdAt' => (new \DateTimeImmutable($createdAt))->format('c'),
                'title' => 'Test Snippet 2',
                'locale' => 'en',
                'metadata' => [
                    'group' => 'default',
                ],
                'securityContext' => SnippetAdmin::SECURITY_CONTEXT,
            ],
        ];

        \usort($expectedResult, fn ($a, $b) => \strcmp($a['id'], $b['id']));
        \usort($results, fn ($a, $b) => \strcmp($a['id'], $b['id']));

        $this->assertSame(
            $expectedResult,
            $results,
        );
    }

    public function testProvideUsesThePerGroupSecurityContextForGroupedTemplates(): void
    {
        $snippet = $this->createSnippet([
            'en' => [
                'draft' => [
                    'template' => 'snippet-alternate',
                    'title' => 'Alternate Snippet',
                ],
            ],
        ]);

        $config = ReindexConfig::create()->withIndex('admin');
        /** @var array<array{resourceId: string, metadata: array{group: string}, securityContext: string}> $results */
        $results = \iterator_to_array($this->provider->provide($config));

        $this->assertCount(1, $results);
        $this->assertSame($snippet->getUuid(), $results[0]['resourceId']);
        $this->assertSame(['group' => 'alternate-group'], $results[0]['metadata']);
        $this->assertSame(
            SnippetAdmin::getSnippetSecurityContext('alternate-group'),
            $results[0]['securityContext'],
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
            SnippetInterface::RESOURCE_KEY . '__' . $snippet1->getUuid() . '__de',
            SnippetInterface::RESOURCE_KEY . '__' . $snippet2->getUuid() . '__en',
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
