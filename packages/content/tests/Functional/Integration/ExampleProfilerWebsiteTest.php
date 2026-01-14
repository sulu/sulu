<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Content\Tests\Functional\Integration;

use Doctrine\Bundle\DoctrineBundle\DataCollector\DoctrineDataCollector;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Content\Tests\Functional\Traits\CreateCategoryTrait;
use Sulu\Content\Tests\Functional\Traits\CreateMediaTrait;
use Sulu\Content\Tests\Functional\Traits\CreateTagTrait;
use Sulu\Content\Tests\Traits\CreateExampleTrait;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

class ExampleProfilerWebsiteTest extends SuluTestCase
{
    use CreateCategoryTrait;
    use CreateExampleTrait;
    use CreateMediaTrait;
    use CreateTagTrait;

    private KernelBrowser $client;

    protected function setUp(): void
    {
        self::purgeDatabase();
    }

    public function testResolveNestedResolvableResources(): void
    {
        $collection1 = self::createCollection(['title' => 'collection-1', 'locale' => 'en']);
        $media1 = self::createMedia($collection1, ['title' => 'media-1', 'locale' => 'en']);
        $media2 = self::createMedia($collection1, ['title' => 'media-2', 'locale' => 'en']);
        $media3 = self::createMedia($collection1, ['title' => 'media-3', 'locale' => 'en']);
        self::getEntityManager()->flush();

        $innerInnerExample = static::createExample(
            [
                'en' => [
                    'live' => [
                        'template' => 'default',
                        'title' => 'Inner Inner Example',
                        'url' => '/inner-inner-example',
                        'image' => ['id' => $media3->getId()],
                        'blocks' => [
                            [
                                'type' => 'media',
                                'media' => [
                                    'ids' => [$media2->getId(), $media3->getId()],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            ['create_route' => true]
        );
        self::getEntityManager()->flush();
        $innerExample = static::createExample(
            [
                'en' => [
                    'live' => [
                        'template' => 'default',
                        'title' => 'Inner Example',
                        'url' => '/inner-example',
                        'examples' => [$innerInnerExample->getId()],
                        'image' => ['id' => $media1->getId()],
                    ],
                ],
            ],
            ['create_route' => true]
        );
        self::getEntityManager()->flush();
        $startExample = static::createExample(
            [
                'en' => [
                    'live' => [
                        'template' => 'default',
                        'title' => 'Start Example',
                        'url' => '/start-example',
                        'examples' => [$innerExample->getId()],
                        'image' => ['id' => $media2->getId()],
                    ],
                ],
            ],
            ['create_route' => true]
        );
        self::getEntityManager()->flush();
        self::getEntityManager()->clear();

        // shutdown kernel to ensure that no other queries are tracked in the profiler
        self::ensureKernelShutdown();
        $this->client = static::createWebsiteClient();

        $this->client->enableProfiler();
        $this->client->request('GET', '/en/start-example');
        $response = $this->client->getResponse();
        $this->assertSame(200, $response->getStatusCode());

        $profiler = $this->client->getProfile();
        $this->assertNotFalse($profiler);
        $this->assertNotNull($profiler);

        $dbCollector = $profiler->getCollector('db');
        $this->assertInstanceOf(DoctrineDataCollector::class, $dbCollector);

        $queriesData = $dbCollector->getQueries();
        $this->assertArrayHasKey('default', $queriesData);

        /** @var list<array{sql: string}> $queries */
        $queries = $queriesData['default'];

        $queryPatterns = $this->extractQueryPatterns($queries);

        self::assertSame(
            [
                // Route lookup for the requested page
                'ro_routes',
                // Load start example entity with dimension content, tags, and categories (eager loaded)
                'test_examples.test_example_dimension_contents.test_example_dimension_content_excerpt_tags.ta_tags.test_example_dimension_content_excerpt_categories.ca_categories.ca_category_translations',
                // Load inner example entity with dimension content, tags, categories, and routes (eager loaded)
                'test_examples.test_example_dimension_contents.test_example_dimension_contents.test_example_dimension_content_excerpt_tags.ta_tags.test_example_dimension_content_excerpt_categories.ca_categories.ca_category_translations.ro_routes',
                // Load inner-inner example entity with dimension content, tags, categories, and routes (eager loaded)
                'test_examples.test_example_dimension_contents.test_example_dimension_contents.test_example_dimension_content_excerpt_tags.ta_tags.test_example_dimension_content_excerpt_categories.ca_categories.ca_category_translations.ro_routes',
                // Batch load all media references (once for all 3 examples)
                'me_media.me_collections.me_files.me_file_versions.me_file_version_tags.ta_tags.me_file_version_meta.me_file_version_meta.se_users.co_contacts.se_users.co_contacts',
                // Route resolution for locale switcher
                'ro_routes',
                // Analytics for the webspace
                'we_analytics.we_analytics_domains.we_domains',
            ],
            $queryPatterns
        );
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
}
