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

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
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
        $this->assertSame(
            5,
            $navigationCount,
            \sprintf('Expected 5 navigation items from sulu_page_navigation_root_flat, got %d', $navigationCount)
        );

        // 8 queries: Optimized eager-loading with all excerpt relations in single query
        $this->assertCount(
            8,
            $result['queries'],
            \sprintf(
                'Query count regression for sulu_page_navigation_root_flat: Expected exactly 8 queries, but got %d. Query patterns: %s',
                \count($result['queries']),
                \implode(' | ', $this->extractQueryPatterns($result['queries']))
            )
        );
    }

    public function testNavigationRootTreeQueryCount(): void
    {
        $result = $this->requestPageAndGetQueries('http://sulu.io/en/test-root-tree');
        $content = (string) $result['response']->getContent();

        $this->assertSame(200, $result['response']->getStatusCode());
        $this->assertStringContainsString('navigation-root-tree', $content);

        $navigationCount = $this->extractNavigationCount($content, 'navigation-root-tree');
        $this->assertSame(
            3,
            $navigationCount,
            \sprintf('Expected 3 root navigation items from sulu_page_navigation_root_tree, got %d', $navigationCount)
        );

        // 8 queries: Optimized eager-loading with all excerpt relations in single query
        $this->assertCount(
            8,
            $result['queries'],
            \sprintf(
                'Query count regression for sulu_page_navigation_root_tree: Expected exactly 8 queries, but got %d. Query patterns: %s',
                \count($result['queries']),
                \implode(' | ', $this->extractQueryPatterns($result['queries']))
            )
        );
    }

    public function testNavigationFlatByUuidQueryCount(): void
    {
        $result = $this->requestPageAndGetQueries('http://sulu.io/en/parent');
        $content = (string) $result['response']->getContent();

        $this->assertSame(200, $result['response']->getStatusCode());
        $this->assertStringContainsString('navigation-flat', $content);

        $navigationCount = $this->extractNavigationCount($content, 'navigation-flat');
        $this->assertSame(
            2,
            $navigationCount,
            \sprintf('Expected 2 navigation items from sulu_page_navigation_flat (child1 + grandchild1 with depth 2), got %d', $navigationCount)
        );

        // 9 queries: Optimized eager-loading with all excerpt relations in single query
        $this->assertCount(
            9,
            $result['queries'],
            \sprintf(
                'Query count regression for sulu_page_navigation_flat: Expected exactly 9 queries, but got %d. Query patterns: %s',
                \count($result['queries']),
                \implode(' | ', $this->extractQueryPatterns($result['queries']))
            )
        );
    }

    public function testNavigationTreeByUuidQueryCount(): void
    {
        $result = $this->requestPageAndGetQueries('http://sulu.io/en/parent/child1');
        $content = (string) $result['response']->getContent();

        $this->assertSame(200, $result['response']->getStatusCode());
        $this->assertStringContainsString('navigation-tree', $content);

        $navigationCount = $this->extractNavigationCount($content, 'navigation-tree');
        $this->assertSame(
            1,
            $navigationCount,
            \sprintf('Expected 1 navigation item from sulu_page_navigation_tree (grandchild1), got %d', $navigationCount)
        );

        // 9 queries: Optimized eager-loading with all excerpt relations in single query
        $this->assertCount(
            9,
            $result['queries'],
            \sprintf(
                'Query count regression for sulu_page_navigation_tree: Expected exactly 9 queries, but got %d. Query patterns: %s',
                \count($result['queries']),
                \implode(' | ', $this->extractQueryPatterns($result['queries']))
            )
        );
    }

    public function testBreadcrumbQueryCount(): void
    {
        $result = $this->requestPageAndGetQueries('http://sulu.io/en/parent/child1/grandchild1');
        $content = (string) $result['response']->getContent();

        $this->assertSame(200, $result['response']->getStatusCode());
        $this->assertStringContainsString('breadcrumb', $content);

        $navigationCount = $this->extractNavigationCount($content, 'breadcrumb');
        $this->assertSame(
            3,
            $navigationCount,
            \sprintf('Expected 3 breadcrumb items (parent -> child1 -> grandchild1), got %d', $navigationCount)
        );

        // 9 queries: Optimized eager-loading with all excerpt relations in single query
        $this->assertCount(
            9,
            $result['queries'],
            \sprintf(
                'Query count regression for sulu_page_breadcrumb: Expected exactly 9 queries, but got %d. Query patterns: %s',
                \count($result['queries']),
                \implode(' | ', $this->extractQueryPatterns($result['queries']))
            )
        );
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

    /**
     * @param list<array{sql: string}> $queries
     *
     * @return list<string>
     */
    private function extractQueryPatterns(array $queries): array
    {
        $patterns = [];
        foreach ($queries as $query) {
            \preg_match_all('/(?:FROM|JOIN)\s+([a-z_]+)\s+[a-z0-9_]/i', $query['sql'], $matches);
            $patterns[] = \implode('.', $matches[1]);
        }

        return $patterns;
    }

    private function extractNavigationCount(string $content, string $navClass): int
    {
        \preg_match('/class="' . \preg_quote($navClass, '/') . '"[^>]*data-count="(\d+)"/', $content, $matches);

        return isset($matches[1]) ? (int) $matches[1] : -1;
    }
}
