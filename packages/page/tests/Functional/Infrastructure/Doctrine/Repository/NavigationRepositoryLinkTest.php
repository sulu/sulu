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

namespace Sulu\Page\Tests\Functional\Infrastructure\Doctrine\Repository;

use PHPUnit\Framework\Attributes\CoversNothing;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Page\Domain\Repository\NavigationRepositoryInterface;
use Sulu\Page\Tests\Traits\CreatePageTrait;

#[CoversNothing]
class NavigationRepositoryLinkTest extends SuluTestCase
{
    use CreatePageTrait;

    private NavigationRepositoryInterface $navigationRepository;

    /**
     * @return array<string, string>
     */
    private function getDefaultProperties(bool $withExcerpt = false): array
    {
        $properties = [
            'uuid' => 'object.resource.id',
            'title' => 'title',
            'url' => 'url',
            'webspaceKey' => 'object.resource.webspaceKey',
            'template' => 'object.templateKey',
            'changed' => 'object.changed',
            'changer' => 'object.changer',
            'created' => 'object.created',
            'creator' => 'object.creator',
            'linkProvider' => 'object.linkData.linkProvider',
        ];

        if ($withExcerpt) {
            $properties['excerpt.title'] = 'excerpt.title';
            $properties['excerpt.more'] = 'excerpt.more';
            $properties['excerpt.description'] = 'excerpt.description';
            $properties['excerpt.segment'] = 'excerpt.segment';
            $properties['excerpt.categories'] = 'excerpt.categories';
            $properties['excerpt.tags'] = 'excerpt.tags';
            $properties['excerpt.audienceTargetGroups'] = 'excerpt.audienceTargetGroups';
            $properties['excerpt.icon'] = 'excerpt.icon';
            $properties['excerpt.image'] = 'excerpt.image';
        }

        return $properties;
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->navigationRepository = $this->getContainer()->get('sulu_page.navigation_repository');
    }

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        self::purgeDatabase();
        self::bootKernel();

        $homepage = self::createPage([
            'en' => [
                'live' => [
                    'title' => 'Homepage',
                    'url' => '/',
                    'template' => 'default',
                    'navigationContexts' => ['main'],
                    'excerpt' => [
                        'title' => 'Homepage Excerpt',
                    ],
                ],
            ],
        ]);

        $contentPage = self::createPage([
            'en' => [
                'live' => [
                    'title' => 'Content Page',
                    'url' => '/content-page',
                    'template' => 'default',
                    'navigationContexts' => ['main'],
                    'excerpt' => [
                        'title' => 'Content Page Excerpt',
                    ],
                    'parentId' => $homepage->getId(),
                ],
            ],
        ]);

        $targetPage = self::createPage([
            'en' => [
                'live' => [
                    'title' => 'Target Page',
                    'url' => '/target-page',
                    'template' => 'default',
                    'navigationContexts' => [],
                    'excerpt' => [
                        'title' => 'Target Page Excerpt',
                    ],
                    'parentId' => $homepage->getId(),
                ],
            ],
        ]);

        $internalLinkPage = self::createPage([
            'en' => [
                'live' => [
                    'title' => 'Internal Link Page',
                    'url' => '/internal-link',
                    'template' => 'default',
                    'navigationContexts' => ['main'],
                    'linkOn' => true,
                    'linkData' => [
                        'href' => $targetPage->getUuid(),
                        'provider' => 'page',
                    ],
                    'excerpt' => [
                        'title' => 'Internal Link Excerpt',
                    ],
                    'parentId' => $homepage->getId(),
                ],
            ],
        ]);

        $externalLinkPage = self::createPage([
            'en' => [
                'live' => [
                    'title' => 'External Link Page',
                    'url' => '/external-link',
                    'template' => 'default',
                    'navigationContexts' => ['main'],
                    'linkOn' => true,
                    'linkData' => [
                        'href' => 'https://example.com',
                        'provider' => 'external',
                    ],
                    'excerpt' => [
                        'title' => 'External Link Excerpt',
                    ],
                    'parentId' => $homepage->getId(),
                ],
            ],
        ]);
    }

    public function testGetNavigationFlat(): void
    {
        $navigation = $this->navigationRepository->getNavigationFlat(
            'main',
            'en',
            'sulu-io',
            null,
            1,
            $this->getDefaultProperties()
        );

        /** @var array<string, mixed> $contentPageNav */
        $contentPageNav = $navigation[1];
        $this->assertSame('Content Page', $contentPageNav['title']);
        $this->assertSame('/content-page', $contentPageNav['url']);

        // Test internal link resolves to target page
        /** @var array<string, mixed> $internalLinkNav */
        $internalLinkNav = $navigation[2];
        $this->assertSame('Internal Link Page', $internalLinkNav['title']);
        $this->assertSame('/target-page', $internalLinkNav['url']);
        $this->assertSame('sulu-io', $internalLinkNav['webspaceKey']);

        // Test external link resolve url
        /** @var array<string, mixed> $externalLinkNav */
        $externalLinkNav = $navigation[3];
        $this->assertSame('External Link Page', $externalLinkNav['title']);
        $this->assertSame('https://example.com', $externalLinkNav['url']);
    }

    public function testGetNavigationFlatWithExcerpt(): void
    {
        $navigation = $this->navigationRepository->getNavigationFlat(
            'main',
            'en',
            'sulu-io',
            null,
            1,
            $this->getDefaultProperties(true)
        );

        // Test internal link with excerpt
        /** @var array<string, mixed> $internalLinkNav */
        $internalLinkNav = $navigation[2];
        $this->assertArrayHasKey('excerpt', $internalLinkNav);
        /** @var array<string, mixed> $excerpt */
        $excerpt = $internalLinkNav['excerpt'];
        $this->assertSame('Target Page Excerpt', $excerpt['title']);

        // Test external link with excerpt
        /** @var array<string, mixed> $externalLinkNav */
        $externalLinkNav = $navigation[3];
        $this->assertArrayHasKey('excerpt', $externalLinkNav);
        /** @var array<string, mixed> $externalExcerpt */
        $externalExcerpt = $externalLinkNav['excerpt'];
        $this->assertSame('External Link Excerpt', $externalExcerpt['title']);
    }

    public function testGetNavigationTree(): void
    {
        $navigation = $this->navigationRepository->getNavigationTree(
            'main',
            'en',
            'sulu-io',
            null,
            2,
            $this->getDefaultProperties()
        );

        // Test content page
        /** @var array<string, mixed> $homepageNav */
        $homepageNav = $navigation[0];
        /** @var array<int, array<string, mixed>> $homepageChildren */
        $homepageChildren = $homepageNav['children'];
        /** @var array<string, mixed> $contentPageNav */
        $contentPageNav = $homepageChildren[0];
        $this->assertSame('Content Page', $contentPageNav['title']);
        $this->assertSame('/content-page', $contentPageNav['url']);
        $this->assertArrayHasKey('children', $contentPageNav);

        // Test internal link resolves to target page
        /** @var array<string, mixed> $internalLinkNav */
        $internalLinkNav = $homepageChildren[1];
        $this->assertSame('Internal Link Page', $internalLinkNav['title']);
        $this->assertSame('/target-page', $internalLinkNav['url']);
        $this->assertArrayHasKey('children', $internalLinkNav);

        // Test external link resolve url
        /** @var array<string, mixed> $externalLinkNav */
        $externalLinkNav = $homepageChildren[2];
        $this->assertSame('External Link Page', $externalLinkNav['title']);
        $this->assertSame('https://example.com', $externalLinkNav['url']);
        $this->assertArrayHasKey('children', $externalLinkNav);
    }
}
