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

namespace Sulu\Content\Tests\Functional\Infrastructure\Sulu\Teaser;

use Sulu\Bundle\AdminBundle\Teaser\Teaser;
use Sulu\Bundle\TestBundle\Testing\AssertSnapshotTrait;
use Sulu\Bundle\TestBundle\Testing\WebsiteTestCase;
use Sulu\Content\Tests\Application\ExampleTestBundle\Teaser\ExampleTeaserProvider;
use Sulu\Content\Tests\Traits\CreateExampleTrait;
use Symfony\Component\Routing\RequestContext;

class ContentTeaserProviderTest extends WebsiteTestCase
{
    use AssertSnapshotTrait;
    use CreateExampleTrait;

    /**
     * @var ExampleTeaserProvider
     */
    private $exampleTeaserProvider;

    /**
     * @var array<string>
     */
    private static array $exampleIds = [];

    public static function setUpBeforeClass(): void
    {
        static::purgeDatabase();
        parent::setUpBeforeClass();

        // Example 1 (both locales, both published)
        $example1 = static::createExample([
            'en' => [
                'live' => [
                    'title' => 'example-1',
                    'article' => 'example-1-article',
                    'excerpt' => [
                        'title' => 'example-1-excerpt-title',
                        'description' => 'example-1-excerpt-description',
                        'more' => 'example-1-more',
                    ],
                ],
            ],
            'de' => [
                'live' => [
                    'title' => 'beispiel-1',
                    'article' => null,
                    'excerpt' => [
                        'description' => 'example-1-excerpt-auszug',
                    ],
                ],
            ],
        ], ['create_route' => true]);

        // Example 2 (only en, published)
        $example2 = static::createExample([
            'en' => [
                'live' => [
                    'title' => 'example-2',
                ],
            ],
        ], ['create_route' => true]);

        // Example 3 (both locales, only en published)
        $example3 = static::createExample([
            'en' => [
                'live' => [
                    'title' => 'example-3',
                    'article' => '<p>Test article</p>',
                ],
            ],
            'de' => [
                'draft' => [
                    'title' => 'beispiel-3',
                ],
            ],
        ], ['create_route' => true]);

        // Example 4 (only de, published)
        $example4 = static::createExample([
            'de' => [
                'live' => [
                    'title' => 'beispiel-4',
                    'article' => '<p>Test article</p>',
                ],
            ],
        ], ['create_route' => true]);

        // Example 5 (only en, not published)
        $example5 = static::createExample([
            'en' => [
                'draft' => [
                    'title' => 'example-5',
                ],
            ],
        ]);

        static::getEntityManager()->flush();

        self::$exampleIds[] = (string) $example1->getId();
        self::$exampleIds[] = (string) $example2->getId();
        self::$exampleIds[] = (string) $example3->getId();
        self::$exampleIds[] = (string) $example4->getId();
        self::$exampleIds[] = (string) $example5->getId();
    }

    protected function setUp(): void
    {
        $this->exampleTeaserProvider = $this->getContainer()->get('example_test.example_teaser_provider');

        /** @var RequestContext $requestContext */
        $requestContext = self::getContainer()->get('router')->getContext();
        $requestContext->setParameter('webspace', 'sulu-io');
    }

    public function testEmpty(): void
    {
        $teasers = $this->exampleTeaserProvider->find([], 'de');

        $this->assertCount(0, $teasers);
    }

    public function testFindDE(): void
    {
        $teasers = $this->exampleTeaserProvider->find(self::$exampleIds, 'de');

        $teasers = $this->mapTeasers($teasers);

        $this->assertArraySnapshot('teasers_de.json', $teasers);
    }

    public function testFindEN(): void
    {
        $teasers = $this->exampleTeaserProvider->find(self::$exampleIds, 'en');

        $teasers = $this->mapTeasers($teasers);

        $this->assertArraySnapshot('teasers_en.json', $teasers);
    }

    public function testFindENNoRoute(): void
    {
        $example6 = static::createExample([
            'en' => [
                'live' => [
                    'title' => 'example-6',
                    'template' => 'no-route',
                ],
            ],
        ]);

        static::getEntityManager()->flush();

        $teasers = $this->exampleTeaserProvider->find([(string) $example6->getId()], 'en');

        $teasers = $this->mapTeasers($teasers);

        $this->assertArraySnapshot('teasers_en_no_route.json', $teasers);
    }

    /**
     * @param array<Teaser> $teasers
     *
     * @return array<array{
     *     id: int|string,
     *     type: string,
     *     locale: string,
     *     url: string,
     *     title: string,
     *     description: string,
     *     moreText: string,
     *     mediaId: int|null,
     *     attributes: array<string, mixed>,
     * }>
     */
    private function mapTeasers(array $teasers): array
    {
        return \array_map(function(Teaser $teaser) {
            return [
                'id' => $teaser->getId(),
                'type' => $teaser->getType(),
                'locale' => $teaser->getLocale(),
                'url' => $teaser->getUrl(),
                'title' => $teaser->getTitle(),
                'description' => $teaser->getDescription(),
                'moreText' => $teaser->getMoreText(),
                'mediaId' => $teaser->getMediaId(),
                'attributes' => $teaser->getAttributes(),
            ];
        }, $teasers);
    }
}
