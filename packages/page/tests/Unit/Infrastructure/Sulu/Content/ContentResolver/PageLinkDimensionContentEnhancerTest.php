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
use Sulu\Route\Application\Routing\Generator\RouteGeneratorInterface;
use Sulu\Route\Domain\Model\Route;

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
     * @var ObjectProphecy<RouteGeneratorInterface>
     */
    private ObjectProphecy $routeGenerator;

    protected function setUp(): void
    {
        $this->pageRepository = $this->prophesize(PageRepositoryInterface::class);
        $this->contentAggregator = $this->prophesize(ContentAggregatorInterface::class);
        $this->linkProviderPool = $this->prophesize(LinkProviderPoolInterface::class);
        $this->routeGenerator = $this->prophesize(RouteGeneratorInterface::class);

        $this->enhancer = new PageLinkDimensionContentEnhancer(
            $this->pageRepository->reveal(),
            $this->contentAggregator->reveal(),
            $this->linkProviderPool->reveal(),
            $this->routeGenerator->reveal(),
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

    public function testEnhancePreservesSourceLinkDataWhenResolvingPageLink(): void
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

        $targetDimensionContent = $this->prophesize(PageDimensionContentInterface::class);
        $targetDimensionContent->getRoute()->willReturn(new Route('pages', 'uuid-target', 'en', '/target-page'));
        $targetDimensionContent->getLocale()->willReturn('en');
        $targetDimensionContent->getResource()->willReturn($targetPage->reveal());
        $targetDimensionContent->getTemplateData()->willReturn(['existing' => 'value']);
        $targetDimensionContent->setTemplateData([
            'existing' => 'value',
            'title' => 'Link Page',
            'url' => '/en/target-page',
        ])->shouldBeCalled();
        $targetDimensionContent->setLinkData([
            'provider' => 'page',
            'href' => 'uuid-target',
        ])->shouldBeCalled();

        $this->pageRepository->findOneBy(
            ['uuid' => 'uuid-target'],
            Argument::type('array'),
        )->willReturn($targetPage->reveal())->shouldBeCalled();

        $this->contentAggregator->aggregate($targetPage->reveal(), [
            'locale' => 'en',
            'stage' => DimensionContentInterface::STAGE_LIVE,
            'version' => DimensionContentInterface::CURRENT_VERSION,
        ])->willReturn($targetDimensionContent->reveal())->shouldBeCalled();

        $this->routeGenerator->generate('/target-page', 'en', 'sulu-io')->willReturn('/en/target-page')->shouldBeCalled();

        $result = $this->enhancer->enhance($pageDimensionContent);

        $this->assertSame($targetDimensionContent->reveal(), $result);
    }
}
