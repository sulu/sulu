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

namespace Sulu\Page\Tests\Functional\Integration;

use Doctrine\Bundle\DoctrineBundle\DataCollector\DoctrineDataCollector;
use PHPUnit\Framework\Attributes\CoversNothing;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Page\Tests\Traits\CreatePageTrait;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

#[CoversNothing]
class NavigationPerformanceTest extends SuluTestCase
{
    use CreatePageTrait;

    private KernelBrowser $client;

    private string $parentUuid;
    private string $child1Uuid;

    public function setUp(): void
    {
        parent::setUp();
        self::purgeDatabase();

        self::createPage([
            'en' => [
                'live' => [
                    'template' => 'navigation-test',
                    'title' => 'Test Root Flat',
                    'url' => '/test-root-flat',
                    'navigationContexts' => ['main'],
                    'showNavigationRootFlat' => true,
                ],
            ],
        ]);

        self::createPage([
            'en' => [
                'live' => [
                    'template' => 'navigation-test',
                    'title' => 'Test Root Tree',
                    'url' => '/test-root-tree',
                    'navigationContexts' => ['main'],
                    'showNavigationRootTree' => true,
                ],
            ],
        ]);

        $parent = self::createPage([
            'en' => [
                'live' => [
                    'template' => 'navigation-test',
                    'title' => 'Parent Page',
                    'url' => '/parent',
                    'navigationContexts' => ['main'],
                    'showNavigationFlat' => true,
                ],
            ],
        ]);
        $this->parentUuid = $parent->getUuid();

        $child1 = self::createPage([
            'en' => [
                'live' => [
                    'parentId' => $parent->getUuid(),
                    'template' => 'navigation-test',
                    'title' => 'Child 1',
                    'url' => '/parent/child1',
                    'navigationContexts' => ['main'],
                    'showNavigationTree' => true,
                ],
            ],
        ]);
        $this->child1Uuid = $child1->getUuid();

        self::createPage([
            'en' => [
                'live' => [
                    'parentId' => $parent->getUuid(),
                    'template' => 'default',
                    'title' => 'Child 2',
                    'url' => '/parent/child2',
                    'navigationContexts' => ['footer'],
                ],
            ],
        ]);

        self::createPage([
            'en' => [
                'live' => [
                    'parentId' => $child1->getUuid(),
                    'template' => 'navigation-test',
                    'title' => 'Grandchild 1',
                    'url' => '/parent/child1/grandchild1',
                    'navigationContexts' => ['main'],
                    'showBreadcrumb' => true,
                ],
            ],
        ]);

        self::getEntityManager()->flush();
        self::getEntityManager()->clear();
    }

    public function testNavigationRootFlatQueryCount(): void
    {
        $result = $this->requestPageAndGetQueries('http://sulu.io/en/test-root-flat');
        $content = (string) $result['response']->getContent();

        $this->assertSame(200, $result['response']->getStatusCode());
        $this->assertStringContainsString('navigation-root-flat', $content);

        $navigationCount = $this->extractNavigationCount($content, 'navigation-root-flat');
        $this->assertSame(5, $navigationCount);

        $initialQueryCount = \count($result['queries']);
        $this->assertCount(8, $result['queries']);

        self::createPage([
            'en' => [
                'live' => [
                    'template' => 'default',
                    'title' => 'Additional Root Page Flat Test',
                    'url' => '/additional-root-flat-test',
                    'navigationContexts' => ['main'],
                ],
            ],
        ]);
        self::getEntityManager()->flush();
        self::getEntityManager()->clear();

        $resultAfter = $this->requestPageAndGetQueries('http://sulu.io/en/test-root-flat');
        $contentAfter = (string) $resultAfter['response']->getContent();

        $navigationCountAfter = $this->extractNavigationCount($contentAfter, 'navigation-root-flat');
        $this->assertSame($navigationCount + 1, $navigationCountAfter);

        // Query count should stay the same despite more pages
        $this->assertCount($initialQueryCount, $resultAfter['queries']);
    }

    public function testNavigationRootTreeQueryCount(): void
    {
        $result = $this->requestPageAndGetQueries('http://sulu.io/en/test-root-tree');
        $content = (string) $result['response']->getContent();

        $this->assertSame(200, $result['response']->getStatusCode());
        $this->assertStringContainsString('navigation-root-tree', $content);

        $navigationCount = $this->extractNavigationCount($content, 'navigation-root-tree');
        $this->assertSame(3, $navigationCount);

        $initialQueryCount = \count($result['queries']);
        $this->assertCount(8, $result['queries']);

        self::createPage([
            'en' => [
                'live' => [
                    'template' => 'default',
                    'title' => 'Additional Root Page Tree Test',
                    'url' => '/additional-root-tree-test',
                    'navigationContexts' => ['main'],
                ],
            ],
        ]);
        self::getEntityManager()->flush();
        self::getEntityManager()->clear();

        $resultAfter = $this->requestPageAndGetQueries('http://sulu.io/en/test-root-tree');
        $contentAfter = (string) $resultAfter['response']->getContent();

        $navigationCountAfter = $this->extractNavigationCount($contentAfter, 'navigation-root-tree');
        $this->assertSame($navigationCount + 1, $navigationCountAfter);

        $this->assertCount($initialQueryCount, $resultAfter['queries']);
    }

    public function testNavigationFlatByUuidQueryCount(): void
    {
        $result = $this->requestPageAndGetQueries('http://sulu.io/en/parent');
        $content = (string) $result['response']->getContent();

        $this->assertSame(200, $result['response']->getStatusCode());
        $this->assertStringContainsString('navigation-flat', $content);

        $navigationCount = $this->extractNavigationCount($content, 'navigation-flat');
        $this->assertSame(2, $navigationCount);

        $initialQueryCount = \count($result['queries']);
        $this->assertCount(9, $result['queries']);

        self::createPage([
            'en' => [
                'live' => [
                    'parentId' => $this->parentUuid,
                    'template' => 'default',
                    'title' => 'Child Flat Test',
                    'url' => '/parent/child-flat-test',
                    'navigationContexts' => ['main'],
                ],
            ],
        ]);
        self::getEntityManager()->flush();
        self::getEntityManager()->clear();

        $resultAfter = $this->requestPageAndGetQueries('http://sulu.io/en/parent');
        $contentAfter = (string) $resultAfter['response']->getContent();

        $navigationCountAfter = $this->extractNavigationCount($contentAfter, 'navigation-flat');
        $this->assertSame($navigationCount + 1, $navigationCountAfter);

        $this->assertCount($initialQueryCount, $resultAfter['queries']);
    }

    public function testNavigationTreeByUuidQueryCount(): void
    {
        $result = $this->requestPageAndGetQueries('http://sulu.io/en/parent/child1');
        $content = (string) $result['response']->getContent();

        $this->assertSame(200, $result['response']->getStatusCode());
        $this->assertStringContainsString('navigation-tree', $content);

        $navigationCount = $this->extractNavigationCount($content, 'navigation-tree');
        $this->assertSame(1, $navigationCount);

        $initialQueryCount = \count($result['queries']);
        $this->assertCount(9, $result['queries']);

        self::createPage([
            'en' => [
                'live' => [
                    'parentId' => $this->child1Uuid,
                    'template' => 'default',
                    'title' => 'Grandchild Tree Test',
                    'url' => '/parent/child1/grandchild-tree-test',
                    'navigationContexts' => ['main'],
                ],
            ],
        ]);
        self::getEntityManager()->flush();
        self::getEntityManager()->clear();

        $resultAfter = $this->requestPageAndGetQueries('http://sulu.io/en/parent/child1');
        $contentAfter = (string) $resultAfter['response']->getContent();

        $navigationCountAfter = $this->extractNavigationCount($contentAfter, 'navigation-tree');
        $this->assertSame($navigationCount + 1, $navigationCountAfter);

        $this->assertCount($initialQueryCount, $resultAfter['queries']);
    }

    public function testBreadcrumbQueryCount(): void
    {
        $result = $this->requestPageAndGetQueries('http://sulu.io/en/parent/child1/grandchild1');
        $content = (string) $result['response']->getContent();

        $this->assertSame(200, $result['response']->getStatusCode());
        $this->assertStringContainsString('breadcrumb', $content);

        $navigationCount = $this->extractNavigationCount($content, 'breadcrumb');
        $this->assertSame(3, $navigationCount);

        $initialQueryCount = \count($result['queries']);
        $this->assertCount(9, $result['queries']);

        self::createPage([
            'en' => [
                'live' => [
                    'parentId' => $this->child1Uuid,
                    'template' => 'default',
                    'title' => 'Grandchild Breadcrumb Test',
                    'url' => '/parent/child1/grandchild-breadcrumb-test',
                    'navigationContexts' => ['main'],
                ],
            ],
        ]);
        self::getEntityManager()->flush();
        self::getEntityManager()->clear();

        $resultAfter = $this->requestPageAndGetQueries('http://sulu.io/en/parent/child1/grandchild1');
        $contentAfter = (string) $resultAfter['response']->getContent();

        $navigationCountAfter = $this->extractNavigationCount($contentAfter, 'breadcrumb');
        $this->assertSame($navigationCount, $navigationCountAfter);

        $this->assertCount($initialQueryCount, $resultAfter['queries']);
    }

    /**
     * @return array{queries: list<array{sql: string}>, response: \Symfony\Component\HttpFoundation\Response}
     */
    private function requestPageAndGetQueries(string $url): array
    {
        self::ensureKernelShutdown();
        $this->client = static::createWebsiteClient();
        $this->client->enableProfiler();
        $this->client->request('GET', $url);
        $response = $this->client->getResponse();

        $profiler = $this->client->getProfile();
        $this->assertNotFalse($profiler, 'Profiler must be enabled');
        $this->assertNotNull($profiler);

        $dbCollector = $profiler->getCollector('db');
        $this->assertInstanceOf(DoctrineDataCollector::class, $dbCollector);

        $queriesData = $dbCollector->getQueries();
        $this->assertArrayHasKey('default', $queriesData);

        /** @var list<array{sql: string}> $queries */
        $queries = $queriesData['default'];

        return [
            'queries' => $queries,
            'response' => $response,
        ];
    }

    private function extractNavigationCount(string $content, string $navClass): int
    {
        \preg_match('/class="' . \preg_quote($navClass, '/') . '"[^>]*data-count="(\d+)"/', $content, $matches);

        return isset($matches[1]) ? (int) $matches[1] : -1;
    }
}
