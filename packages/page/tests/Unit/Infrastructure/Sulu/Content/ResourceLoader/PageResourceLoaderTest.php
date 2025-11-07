<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\Page\Tests\Unit\Infrastructure\Sulu\Content\ResourceLoader;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\TestBundle\Testing\SetGetPrivatePropertyTrait;
use Sulu\Component\Security\Authentication\UserInterface;
use Sulu\Page\Domain\Model\Page;
use Sulu\Page\Domain\Repository\PageRepositoryInterface;
use Sulu\Page\Infrastructure\Sulu\Content\ResourceLoader\PageResourceLoader;

class PageResourceLoaderTest extends TestCase
{
    use ProphecyTrait;
    use SetGetPrivatePropertyTrait;

    /**
     * @var ObjectProphecy<PageRepositoryInterface>
     */
    private ObjectProphecy $pageRepository;

    private PageResourceLoader $loader;

    public function setUp(): void
    {
        $this->pageRepository = $this->prophesize(PageRepositoryInterface::class);
        $this->loader = new PageResourceLoader($this->pageRepository->reveal());
    }

    public function testGetKey(): void
    {
        $this->assertSame('page', $this->loader::getKey());
    }

    public function testLoad(): void
    {
        $page1 = $this->createPage('123-123-123');
        $page2 = $this->createPage('321-321-321');

        $this->pageRepository->findBy(['uuids' => ['123-123-123', '321-321-321']])->willReturn([
            $page1,
            $page2,
        ])
            ->shouldBeCalled();

        $result = $this->loader->load(['123-123-123', '321-321-321'], 'en', []);

        $this->assertSame([
            '123-123-123' => $page1,
            '321-321-321' => $page2,
        ], $result);
    }

    public function testLoadWithFiltersFromParams(): void
    {
        $page = $this->createPage('123-123-123');

        $this->pageRepository->findBy([
            'uuids' => ['123-123-123'],
            'locale' => 'en',
            'stage' => 'live',
        ])->willReturn([$page])
            ->shouldBeCalled();

        $result = $this->loader->load(['123-123-123'], 'en', [
            'filters' => [
                'locale' => 'en',
                'stage' => 'live',
            ],
        ]);

        $this->assertSame(['123-123-123' => $page], $result);
    }

    public function testLoadWithPermissionConfigInFilters(): void
    {
        $page = $this->createPage('123-123-123');
        $user = $this->prophesize(UserInterface::class);

        $this->pageRepository->findBy([
            'uuids' => ['123-123-123'],
            'locale' => 'en',
            'stage' => 'live',
            'permissionConfig' => [
                'user' => $user->reveal(),
                'permission' => 64,
            ],
        ])->willReturn([$page])
            ->shouldBeCalled();

        $result = $this->loader->load(['123-123-123'], 'en', [
            'filters' => [
                'locale' => 'en',
                'stage' => 'live',
                'permissionConfig' => [
                    'user' => $user->reveal(),
                    'permission' => 64,
                ],
            ],
        ]);

        $this->assertSame(['123-123-123' => $page], $result);
    }

    public function testLoadWithEmptyParams(): void
    {
        $page = $this->createPage('123-123-123');

        $this->pageRepository->findBy([
            'uuids' => ['123-123-123'],
        ])->willReturn([$page])
            ->shouldBeCalled();

        $result = $this->loader->load(['123-123-123'], 'en', []);

        $this->assertSame(['123-123-123' => $page], $result);
    }

    public function testLoadPreservesUuidsWhenMergingFilters(): void
    {
        $page = $this->createPage('123-123-123');

        $this->pageRepository->findBy(Argument::that(function(array $filters) {
            return isset($filters['uuids'])
                && $filters['uuids'] === ['123-123-123']
                && isset($filters['locale'])
                && 'de' === $filters['locale'];
        }))->willReturn([$page])
            ->shouldBeCalled();

        $result = $this->loader->load(['123-123-123'], 'en', [
            'filters' => [
                'locale' => 'de',
            ],
        ]);

        $this->assertSame(['123-123-123' => $page], $result);
    }

    public function testLoadWithNonArrayFilters(): void
    {
        $page = $this->createPage('123-123-123');

        $this->pageRepository->findBy([
            'uuids' => ['123-123-123'],
        ])->willReturn([$page])
            ->shouldBeCalled();

        $result = $this->loader->load(['123-123-123'], 'en', [
            'filters' => 'invalid',
        ]);

        $this->assertSame(['123-123-123' => $page], $result);
    }

    private static function createPage(string $id): Page
    {
        $page = new Page();
        static::setPrivateProperty($page, 'uuid', $id);

        return $page;
    }
}
