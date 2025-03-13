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
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\TestBundle\Testing\SetGetPrivatePropertyTrait;
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

        $this->pageRepository->findBy(['id' => ['123-123-123', '321-321-321']])->willReturn([
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

    private static function createPage(string $id): Page
    {
        $page = new Page();
        static::setPrivateProperty($page, 'uuid', $id);

        return $page;
    }
}
