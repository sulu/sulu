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

namespace Sulu\Content\Tests\Functional\Integration;

use Doctrine\Bundle\DoctrineBundle\DataCollector\DoctrineDataCollector;
use PHPUnit\Framework\Attributes\DataProvider;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Content\Tests\Application\ExampleTestBundle\Entity\Example;
use Sulu\Content\Tests\Traits\CreateExampleTrait;
use Symfony\Component\DomCrawler\Crawler;

class ExampleSmartContentExcludeDuplicateTest extends SuluTestCase
{
    use CreateExampleTrait;

    /**
     * @var array<string, int|string> example title => example id
     */
    private static array $exampleIds = [];

    private static int $pageCounter = 0;

    public static function setUpBeforeClass(): void
    {
        self::purgeDatabase();
        $examples = [];
        $examples['Example 1'] = self::createDefaultExample(title: 'Example 1', authored: '+1 min');
        $examples['Example 2'] = self::createDefaultExample(title: 'Example 2', authored: '+2 min');
        $examples['Example 3'] = self::createDefaultExample(title: 'Example 3', authored: '+3 min');
        $examples['Example 4'] = self::createDefaultExample(title: 'Example 4', authored: '+4 min');
        $examples['Example 5'] = self::createDefaultExample(title: 'Example 5', authored: '+5 min');
        $examples['Example 6'] = self::createDefaultExample(title: 'Example 6', authored: '+6 min');
        $examples['Example 7'] = self::createDefaultExample(title: 'Example 7', authored: '+7 min');

        static::getEntityManager()->flush();

        foreach ($examples as $title => $example) {
            self::$exampleIds[$title] = $example->getId();
        }

        self::ensureKernelShutdown();
    }

    /**
     * @param array<int, array<string, mixed>> $definitions
     * @param string[] $expectedFirstSmartContent
     * @param array<string, string[]> $expectedOthers
     */
    #[DataProvider('excludeDuplicatesDataProvider')]
    public function testExcludeDuplicates(
        array $definitions,
        array $expectedFirstSmartContent,
        array $expectedOthers,
        int $expectedExamplesQueryCount,
    ): void {
        $url = self::createSmartContentDuplicatesExample($definitions);

        $client = static::createWebsiteClient();
        $client->enableProfiler();
        $crawler = $client->request('GET', '/en' . $url);
        self::assertSame(200, $client->getResponse()->getStatusCode());

        $idToTitle = \array_flip(\array_map('strval', self::$exampleIds));
        $urlToTitle = [];
        foreach (\array_keys(self::$exampleIds) as $title) {
            $urlToTitle['/en/' . \strtolower(\str_replace(' ', '-', $title))] = $title;
        }

        /** @var array<int, array{type: string, titles: string[]}> $blocks */
        $blocks = $crawler->filter('[data-block-type]')->each(
            static function(Crawler $block) use ($idToTitle, $urlToTitle): array {
                return [
                    'type' => (string) $block->attr('data-block-type'),
                    'titles' => \array_merge(
                        $block->filter('[data-teaser-id]')->each(
                            static fn (Crawler $teaser): string => $idToTitle[(string) $teaser->attr('data-teaser-id')] ?? 'unknown',
                        ),
                        // link and text editor blocks render links instead of teasers
                        $block->filter('[data-link] a')->each(
                            static fn (Crawler $link): string => $urlToTitle[(string) $link->attr('href')] ?? 'unknown',
                        ),
                    ),
                ];
            },
        );

        // the first block is always the smart content with excluded duplicates
        $firstBlock = \array_shift($blocks);
        self::assertNotNull($firstBlock);
        self::assertSame('smart_content', $firstBlock['type']);
        self::assertSame($expectedFirstSmartContent, $firstBlock['titles']);

        $others = [];
        foreach ($blocks as $block) {
            $key = $block['type'];
            for ($i = 2; isset($others[$key]); ++$i) {
                $key = $block['type'] . '_' . $i;
            }
            $others[$key] = $block['titles'];
        }

        self::assertSame($expectedOthers, $others);

        $profile = $client->getProfile();
        self::assertNotFalse($profile);
        $dbCollector = $profile->getCollector('db');
        self::assertInstanceOf(DoctrineDataCollector::class, $dbCollector);

        /** @var list<array{sql: string}> $queries */
        $queries = $dbCollector->getQueries()['default'] ?? [];
        $examplesQueries = \array_filter(
            $queries,
            static fn (array $query): bool => 1 === \preg_match('/\b(FROM|JOIN)\s+test_examples\s/i', $query['sql']),
        );

        self::assertCount(
            $expectedExamplesQueryCount,
            $examplesQueries,
            "Unexpected examples queries:\n" . \implode("\n", \array_column($examplesQueries, 'sql')),
        );
    }

    public static function excludeDuplicatesDataProvider(): \Generator
    {
        yield 'teaser_selection' => [
            'definitions' => [
                [
                    'type' => 'smart_content',
                    'examples' => [
                        'limitResult' => 2,
                    ],
                ],
                [
                    'type' => 'teaser_selection',
                    'examples' => [
                        'Example 1',
                        'Example 2',
                    ],
                ],
            ],
            'expectedFirstSmartContent' => [
                'Example 3',
                'Example 4',
            ],
            'expectedOthers' => [
                'teaser_selection' => [
                    'Example 1',
                    'Example 2',
                ],
            ],
            'expectedExamplesQueryCount' => 3,
        ];

        yield 'example_selection' => [
            'definitions' => [
                [
                    'type' => 'smart_content',
                    'examples' => [
                        'limitResult' => 2,
                    ],
                ],
                [
                    'type' => 'example_selection',
                    'examples' => [
                        'Example 2',
                        'Example 3',
                    ],
                ],
            ],
            'expectedFirstSmartContent' => [
                'Example 1',
                'Example 4',
            ],
            'expectedOthers' => [
                'example_selection' => [
                    'Example 2',
                    'Example 3',
                ],
            ],
            'expectedExamplesQueryCount' => 3,
        ];

        yield 'single_example_selection' => [
            'definitions' => [
                [
                    'type' => 'smart_content',
                    'examples' => [
                        'limitResult' => 2,
                    ],
                ],
                [
                    'type' => 'single_example_selection',
                    'example' => 'Example 2',
                ],
            ],
            'expectedFirstSmartContent' => [
                'Example 1',
                'Example 3',
            ],
            'expectedOthers' => [
                'single_example_selection' => [
                    'Example 2',
                ],
            ],
            'expectedExamplesQueryCount' => 3,
        ];

        yield 'nested' => [
            'definitions' => [
                [
                    'type' => 'smart_content',
                    'examples' => [
                        'limitResult' => 2,
                    ],
                ],
                [
                    'type' => 'nested',
                    'nested_blocks' => [
                        [
                            'type' => 'example_selection',
                            'examples' => [
                                'Example 1',
                                'Example 2',
                            ],
                        ],
                    ],
                ],
            ],
            'expectedFirstSmartContent' => [
                'Example 3',
                'Example 4',
            ],
            'expectedOthers' => [
                'nested' => [
                    'Example 1',
                    'Example 2',
                ],
            ],
            'expectedExamplesQueryCount' => 3,
        ];

        yield 'smart_content' => [
            'definitions' => [
                [
                    'type' => 'smart_content',
                    'examples' => [
                        'limitResult' => 2,
                    ],
                ],
                [
                    'type' => 'smart_content',
                    'examples' => [
                        'limitResult' => 2,
                    ],
                ],
            ],
            'expectedFirstSmartContent' => [
                'Example 1',
                'Example 2',
            ],
            'expectedOthers' => [
                'smart_content' => [
                    'Example 3',
                    'Example 4',
                ],
            ],
            'expectedExamplesQueryCount' => 3,
        ];

        yield 'link' => [
            'definitions' => [
                [
                    'type' => 'smart_content',
                    'examples' => [
                        'limitResult' => 2,
                    ],
                ],
                [
                    'type' => 'link',
                    'example' => 'Example 1',
                ],
            ],
            'expectedFirstSmartContent' => [
                'Example 2',
                'Example 3',
            ],
            'expectedOthers' => [
                'link' => [
                    'Example 1',
                ],
            ],
            'expectedExamplesQueryCount' => 3,
        ];

        yield 'two_single_example_selections' => [
            'definitions' => [
                [
                    'type' => 'smart_content',
                    'examples' => [
                        'limitResult' => 2,
                    ],
                ],
                [
                    'type' => 'single_example_selection',
                    'example' => 'Example 2',
                ],
                [
                    'type' => 'single_example_selection',
                    'example' => 'Example 3',
                ],
            ],
            'expectedFirstSmartContent' => [
                'Example 1',
                'Example 4',
            ],
            'expectedOthers' => [
                'single_example_selection' => [
                    'Example 2',
                ],
                'single_example_selection_2' => [
                    'Example 3',
                ],
            ],
            'expectedExamplesQueryCount' => 3,
        ];

        // TODO the text editor case needs to be discussed: the sulu-link tags are resolved by the markup
        //      parser after the rendering and not via the resolvables, so it is not clear if these links
        //      should be excluded from the smart content and if their queries can be batched.
        // yield 'text_editor' => [
        //     'definitions' => [
        //         [
        //             'type' => 'smart_content',
        //             'examples' => [
        //                 'limitResult' => 2,
        //             ],
        //         ],
        //         [
        //             'type' => 'text_editor',
        //             'example' => 'Example 1',
        //         ],
        //     ],
        //     'expectedFirstSmartContent' => [
        //         'Example 2',
        //         'Example 3',
        //     ],
        //     'expectedOthers' => [
        //         'text_editor' => [
        //             'Example 1',
        //         ],
        //     ],
        //     'expectedExamplesQueryCount' => 3,
        // ];
    }

    private static function createDefaultExample(string $title, string $authored): Example
    {
        return static::createExample(
            [
                'en' => [
                    'live' => [
                        'template' => 'default',
                        'title' => $title,
                        'url' => '/' . \strtolower(\str_replace(' ', '-', $title)),
                        'authored' => (new \DateTimeImmutable($authored))->format('Y-m-d H:i:s'),
                    ],
                ],
            ],
            ['create_route' => true],
        );
    }

    /**
     * @param array<int, array<string, mixed>> $definitions
     *
     * @return string url of the created page
     */
    private static function createSmartContentDuplicatesExample(array $definitions): string
    {
        $url = '/duplicates-' . ++self::$pageCounter;

        $blocks = [];
        foreach ($definitions as $definition) {
            $blocks[] = match ($definition['type']) {
                'smart_content' => [
                    'type' => 'smart_content',
                    'examples' => \array_merge([
                        'types' => ['default'],
                        'sortBy' => 'authored',
                        'sortMethod' => 'asc',
                    ], $definition['examples']),
                ],
                'teaser_selection' => [
                    'type' => 'teaser_selection',
                    'teasers' => [
                        'items' => \array_map(
                            static fn ($id) => ['id' => (string) $id, 'type' => 'examples'],
                            self::mapTitlesToIds($definition['examples']),
                        ),
                    ],
                ],
                'example_selection' => [
                    'type' => 'example_selection',
                    'examples' => self::mapTitlesToIds($definition['examples']),
                ],
                'link' => [
                    'type' => 'link',
                    'link' => ['provider' => 'examples', 'href' => self::$exampleIds[$definition['example']]],
                ],
                // TODO text editor case needs to be discussed, see the data provider
                'text_editor' => [
                    'type' => 'text_editor',
                    'text' => \sprintf(
                        '<p><sulu-link provider="examples" href="%s">%s</sulu-link></p>',
                        self::$exampleIds[$definition['example']],
                        $definition['example'],
                    ),
                ],
                'single_example_selection' => [
                    'type' => 'single_example_selection',
                    'example' => self::$exampleIds[$definition['example']],
                ],
                'nested' => [
                    'type' => 'nested',
                    'nested_blocks' => \array_map(
                        static fn (array $nestedDefinition) => [
                            'type' => $nestedDefinition['type'],
                            'examples' => self::mapTitlesToIds($nestedDefinition['examples']),
                        ],
                        $definition['nested_blocks'],
                    ),
                ],
                default => throw new \InvalidArgumentException(\sprintf('Unknown block type "%s" in definition.', $definition['type'])),
            };
        }

        static::createExample(
            [
                'en' => [
                    'live' => [
                        'template' => 'smart-content-duplicate',
                        'title' => 'Duplicates ' . self::$pageCounter,
                        'url' => $url,
                        'blocks' => $blocks,
                    ],
                ],
            ],
            ['create_route' => true],
        );
        static::getEntityManager()->flush();
        self::ensureKernelShutdown();

        return $url;
    }

    /**
     * @param string[] $titles
     *
     * @return array<int|string>
     */
    private static function mapTitlesToIds(array $titles): array
    {
        return \array_map(static fn (string $title) => self::$exampleIds[$title], $titles);
    }
}
