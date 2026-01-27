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
use Sulu\Bundle\SecurityBundle\Entity\Role;
use Sulu\Bundle\TestBundle\Testing\SetGetPrivatePropertyTrait;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Page\Domain\Model\PageInterface;
use Sulu\Page\Infrastructure\Sulu\Search\Visitor\WebsitePageReindexExcerptEnhancer;
use Sulu\Page\Infrastructure\Sulu\Search\WebsitePageReindexProvider;
use Sulu\Page\Tests\Traits\CreatePageTrait;
use Sulu\Page\Tests\Traits\CreatePageWithPermissionsTrait;

class WebsitePageReindexProviderTest extends SuluTestCase
{
    use CreatePageTrait;
    use CreatePageWithPermissionsTrait;
    use SetGetPrivatePropertyTrait;

    private EntityManagerInterface $entityManager;
    private WebsitePageReindexProvider $provider;
    private Role $anonymousRole;

    protected function setUp(): void
    {
        $this->entityManager = $this->getEntityManager();
        $this->provider = new WebsitePageReindexProvider($this->entityManager, []);
        $this->purgeDatabase();
        $this->anonymousRole = $this->createAnonymousRoleWithWebspacePermissions('sulu-test-secure');
    }

    public function testGetIndex(): void
    {
        $this->assertSame('website', WebsitePageReindexProvider::getIndex());
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
                    'authored' => '1995-11-29T12:00:00+00:00',
                ],
            ],
        ], 'sulu-io');
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
                    'title' => 'Test Page 2 De',
                    'url' => '/test-page-2',
                ],
            ],
        ], 'blog');
        $page3 = $this->createPage([
            'en' => [
                'live' => [
                    'template' => 'default',
                    'title' => 'No Access',
                    'url' => '/no-access',
                    'authored' => '1995-11-29T12:00:00+00:00',
                ],
            ],
        ], 'sulu-io');
        $this->denyAccessToPage($page3, $this->anonymousRole);
        $this->entityManager->clear();

        $changedDateString1 = '2023-06-01 15:30:00';
        $changedDateString2 = '2024-11-29 15:30:00';

        $connection = self::getEntityManager()->getConnection();
        $sql = 'UPDATE pa_page_dimension_contents SET changed = :changed WHERE pageUuid = :uuid';

        $connection->executeStatement($sql, [
            'changed' => $changedDateString1,
            'uuid' => $page1->getUuid(),
        ]);

        $sql = 'UPDATE pa_page_dimension_contents SET changed = :changed, authored = :authored WHERE pageUuid = :uuid';
        $connection->executeStatement($sql, [
            'changed' => $changedDateString2,
            'authored' => null,
            'uuid' => $page2->getUuid(),
        ]);

        $config = ReindexConfig::create()->withIndex('website');
        /** @var array<array{id: string}> $results */
        $results = \iterator_to_array($this->provider->provide($config));

        $this->assertCount(2, $results);

        $expectedResult = [
            [
                'id' => PageInterface::RESOURCE_KEY . '__' . $page1->getUuid() . '__en',
                'resourceKey' => PageInterface::RESOURCE_KEY,
                'resourceId' => $page1->getUuid(),
                'locale' => 'en',
                'webspaces' => ['sulu-io'],
                'title' => 'Test Page',
                'url' => '/test-page',
                'content' => [],
                'mediaId' => '',
                'authoredAt' => (new \DateTimeImmutable('1995-11-29 12:00:00'))->format('c'),
                'properties' => [],
            ],
            [
                'id' => PageInterface::RESOURCE_KEY . '__' . $page2->getUuid() . '__en',
                'resourceKey' => PageInterface::RESOURCE_KEY,
                'resourceId' => $page2->getUuid(),
                'locale' => 'en',
                'webspaces' => ['blog'],
                'title' => 'Test Page 2',
                'url' => '/test-page-2',
                'content' => [],
                'mediaId' => '',
                'authoredAt' => (new \DateTimeImmutable($changedDateString2))->format('c'),
                'properties' => [],
            ],
        ];

        \usort($expectedResult, fn ($a, $b) => \strcmp($a['id'], $b['id']));
        \usort($results, fn ($a, $b) => \strcmp($a['id'], $b['id']));

        $this->assertSame(
            $expectedResult,
            $results,
        );
    }

    public function testExcerptEnhancerPopulatesExcerptProperties(): void
    {
        $provider = new WebsitePageReindexProvider(
            $this->entityManager,
            [new WebsitePageReindexExcerptEnhancer()],
        );

        $page = $this->createPage([
            'en' => [
                'live' => [
                    'template' => 'default',
                    'title' => 'Page With Excerpt',
                    'url' => '/page-excerpt',
                    'excerpt' => [
                        'title' => 'Excerpt Title',
                        'description' => '<p>Excerpt <strong>description</strong></p>',
                        'more' => 'Read more',
                        'image' => ['id' => 1],
                        'icon' => ['id' => 2],
                    ],
                ],
            ],
        ], 'sulu-io');
        $this->entityManager->clear();

        $config = ReindexConfig::create()->withIndex('website');
        /** @var array<array<string, mixed>> $results */
        $results = \iterator_to_array($provider->provide($config));

        $this->assertCount(1, $results);

        $result = $results[0];
        $this->assertSame('Excerpt Title', $result['title']);

        $this->assertIsArray($result['properties']);
        $excerpt = $result['properties']['excerpt'];
        $this->assertIsArray($excerpt);

        $this->assertSame('Excerpt Title', $excerpt['title']);
        $this->assertSame('Excerpt description', $excerpt['description']);
        $this->assertSame('Read more', $excerpt['more']);
        $this->assertSame(1, $excerpt['imageId']);
        $this->assertSame(2, $excerpt['iconId']);
    }

    public function testExcerptTitleFallbackWhenNoContentTitle(): void
    {
        $provider = new WebsitePageReindexProvider(
            $this->entityManager,
            [new WebsitePageReindexExcerptEnhancer()],
        );

        $page = $this->createPage([
            'en' => [
                'live' => [
                    'template' => 'default',
                    'title' => 'Original Title',
                    'url' => '/page-title-fallback',
                    'excerpt' => [
                        'title' => 'Excerpt Title Override',
                    ],
                ],
            ],
        ], 'sulu-io');
        $this->entityManager->clear();

        $config = ReindexConfig::create()->withIndex('website');
        /** @var array<array<string, mixed>> $results */
        $results = \iterator_to_array($provider->provide($config));

        $this->assertCount(1, $results);

        $result = $results[0];
        $this->assertSame('Excerpt Title Override', $result['title']);

        $this->assertIsArray($result['properties']);
        $excerpt = $result['properties']['excerpt'];
        $this->assertIsArray($excerpt);
        $this->assertSame('Excerpt Title Override', $excerpt['title']);
    }

    public function testExcerptImageFallbackToMediaId(): void
    {
        $provider = new WebsitePageReindexProvider(
            $this->entityManager,
            [new WebsitePageReindexExcerptEnhancer()],
        );

        $page = $this->createPage([
            'en' => [
                'live' => [
                    'template' => 'default',
                    'title' => 'Page With Excerpt Image',
                    'url' => '/page-excerpt-image',
                    'excerpt' => [
                        'image' => ['id' => 55],
                    ],
                ],
            ],
        ], 'sulu-io');
        $this->entityManager->clear();

        $config = ReindexConfig::create()->withIndex('website');
        /** @var array<array<string, mixed>> $results */
        $results = \iterator_to_array($provider->provide($config));

        $this->assertCount(1, $results);

        $result = $results[0];
        $this->assertSame('55', $result['mediaId']);

        $this->assertIsArray($result['properties']);
        $excerpt = $result['properties']['excerpt'];
        $this->assertIsArray($excerpt);
        $this->assertSame(55, $excerpt['imageId']);
    }

    public function testNoExcerptDataProducesEmptyProperties(): void
    {
        $provider = new WebsitePageReindexProvider(
            $this->entityManager,
            [new WebsitePageReindexExcerptEnhancer()],
        );

        $page = $this->createPage([
            'en' => [
                'live' => [
                    'template' => 'default',
                    'title' => 'No Excerpt Page',
                    'url' => '/no-excerpt',
                ],
            ],
        ], 'sulu-io');
        $this->entityManager->clear();

        $config = ReindexConfig::create()->withIndex('website');
        /** @var array<array<string, mixed>> $results */
        $results = \iterator_to_array($provider->provide($config));

        $this->assertCount(1, $results);

        $result = $results[0];
        $this->assertSame('No Excerpt Page', $result['title']);
        $this->assertSame([], $result['properties']);
    }
}
