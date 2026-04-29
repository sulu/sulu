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

namespace Sulu\Page\Tests\Unit\Infrastructure\Sulu\Content\ContentResolver;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\MarkupBundle\Markup\Link\LinkItem;
use Sulu\Bundle\MarkupBundle\Markup\Link\LinkProviderInterface;
use Sulu\Bundle\MarkupBundle\Markup\Link\LinkProviderPoolInterface;
use Sulu\Content\Application\ContentAggregator\ContentAggregatorInterface;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Page\Domain\Model\PageDimensionContent;
use Sulu\Page\Domain\Model\PageDimensionContentInterface;
use Sulu\Page\Domain\Model\PageInterface;
use Sulu\Page\Domain\Repository\PageRepositoryInterface;
use Sulu\Page\Infrastructure\Sulu\Content\ContentResolver\PageLinkDimensionContentEnhancer;
use Sulu\Route\Domain\Model\Route;
use Sulu\Route\Domain\Repository\RouteRepositoryInterface;

class PageLinkDimensionContentEnhancerTest extends TestCase
{
    use ProphecyTrait;

    private PageLinkDimensionContentEnhancer $enhancer;

    /**
     * @var ObjectProphecy<PageRepositoryInterface>
     */
    private ObjectProphecy $pageRepository;

    /**
     * @var ObjectProphecy<ContentAggregatorInterface>
     */
    private ObjectProphecy $contentAggregator;

    /**
     * @var ObjectProphecy<LinkProviderPoolInterface>
     */
    private ObjectProphecy $linkProviderPool;

    /**
     * @var ObjectProphecy<RouteRepositoryInterface>
     */
    private ObjectProphecy $routeRepository;

    protected function setUp(): void
    {
        $this->pageRepository = $this->prophesize(PageRepositoryInterface::class);
        $this->contentAggregator = $this->prophesize(ContentAggregatorInterface::class);
        $this->linkProviderPool = $this->prophesize(LinkProviderPoolInterface::class);
        $this->routeRepository = $this->prophesize(RouteRepositoryInterface::class);

        $this->enhancer = new PageLinkDimensionContentEnhancer(
            $this->pageRepository->reveal(),
            $this->contentAggregator->reveal(),
            $this->linkProviderPool->reveal(),
            $this->routeRepository->reveal(),
        );
    }

    public function testEnhanceResolvesMediaLinkWithIntegerHref(): void
    {
        $linkProvider = $this->prophesize(LinkProviderInterface::class);
        $pageDimensionContent = $this->prophesize(PageDimensionContentInterface::class);

        $pageDimensionContent->getLocale()->willReturn('en');
        $pageDimensionContent->getLinkData()->willReturn([
            'provider' => 'media',
            'href' => 42,
        ]);
        $pageDimensionContent->getTemplateData()->willReturn([
            'title' => 'Link Page',
        ]);
        $pageDimensionContent->setTemplateData([
            'title' => 'Link Page',
            'url' => '/media/example.jpg?v=1',
        ])->shouldBeCalled();

        $this->linkProviderPool->getProvider('media')->willReturn($linkProvider->reveal())->shouldBeCalled();
        $linkProvider->preload(['42'], 'en')->willReturn([
            new LinkItem('42', 'Example media', '/media/example.jpg?v=1', true),
        ])->shouldBeCalled();

        $result = $this->enhancer->enhance($pageDimensionContent->reveal());

        $this->assertSame($pageDimensionContent->reveal(), $result);
    }

    public function testEnhanceResolvesArticleLinkViaRouteRepository(): void
    {
        $pageDimensionContent = $this->prophesize(PageDimensionContentInterface::class);

        $pageDimensionContent->getLocale()->willReturn('en');
        $pageDimensionContent->getLinkData()->willReturn([
            'provider' => 'article',
            'href' => 'article-uuid-1',
        ]);
        $pageDimensionContent->getTemplateData()->willReturn([
            'title' => 'Article Link Page',
        ]);
        $pageDimensionContent->setTemplateData([
            'title' => 'Article Link Page',
            'url' => '/my-article',
        ])->shouldBeCalled();

        $route = new Route('articles', 'article-uuid-1', 'en', '/my-article');
        $this->routeRepository->findOneBy([
            'resourceId' => 'article-uuid-1',
            'locale' => 'en',
        ])->willReturn($route)->shouldBeCalled();

        $this->linkProviderPool->getProvider(Argument::any())->shouldNotBeCalled();

        $result = $this->enhancer->enhance($pageDimensionContent->reveal());

        $this->assertSame($pageDimensionContent->reveal(), $result);
    }

    public function testEnhanceInternalPageLinkReportsSourceIdentityWithTargetContent(): void
    {
        $sourcePage = $this->prophesize(PageInterface::class);
        $sourcePage->getId()->willReturn('uuid-link');

        $pageDimensionContent = new PageDimensionContent($sourcePage->reveal());
        $pageDimensionContent->setLocale('en');
        $pageDimensionContent->setStage(DimensionContentInterface::STAGE_LIVE);
        $pageDimensionContent->setVersion(DimensionContentInterface::CURRENT_VERSION);
        $pageDimensionContent->setTemplateData(['title' => 'Link Page']);
        $pageDimensionContent->setLinkData([
            'provider' => 'page',
            'href' => 'uuid-target',
        ]);

        $targetPage = $this->prophesize(PageInterface::class);
        $targetPage->getWebspaceKey()->willReturn('sulu-io');

        $targetDimensionContent = new PageDimensionContent($targetPage->reveal());
        $targetDimensionContent->setLocale('en');
        $targetDimensionContent->setStage(DimensionContentInterface::STAGE_LIVE);
        $targetDimensionContent->setVersion(DimensionContentInterface::CURRENT_VERSION);
        $targetDimensionContent->setTemplateData(['existing' => 'value', 'title' => 'Target Page']);
        $targetDimensionContent->setRoute(new Route('pages', 'uuid-target', 'en', '/target-page'));

        $this->pageRepository->findOneBy(
            ['uuid' => 'uuid-target'],
            Argument::type('array'),
        )->willReturn($targetPage->reveal())->shouldBeCalled();

        $this->contentAggregator->aggregate($targetPage->reveal(), [
            'locale' => 'en',
            'stage' => DimensionContentInterface::STAGE_LIVE,
            'version' => DimensionContentInterface::CURRENT_VERSION,
        ])->willReturn($targetDimensionContent)->shouldBeCalled();

        $result = $this->enhancer->enhance($pageDimensionContent);

        $this->assertNotSame($targetDimensionContent, $result);
        $this->assertSame($sourcePage->reveal(), $result->getResource());
        $this->assertSame([
            'existing' => 'value',
            'title' => 'Link Page',
            'url' => '/target-page',
        ], $result->getTemplateData());
        $this->assertSame('Link Page', $result->getTitle());
        $this->assertSame([
            'provider' => 'page',
            'href' => 'uuid-target',
        ], $result->getLinkData());

        // the clone took all writes so the aggregator's instance stays unmodified
        $this->assertSame($targetPage->reveal(), $targetDimensionContent->getResource());
        $this->assertSame(
            ['existing' => 'value', 'title' => 'Target Page'],
            $targetDimensionContent->getTemplateData(),
        );
        $this->assertNull($targetDimensionContent->getLinkData());
    }
}
