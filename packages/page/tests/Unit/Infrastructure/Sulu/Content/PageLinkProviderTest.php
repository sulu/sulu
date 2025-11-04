<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Page\Tests\Unit\Infrastructure\Sulu\Content;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\HttpCacheBundle\ReferenceStore\ReferenceStoreInterface;
use Sulu\Bundle\TestBundle\Testing\SetGetPrivatePropertyTrait;
use Sulu\Content\Application\ContentAggregator\ContentAggregatorInterface;
use Sulu\Content\Application\ContentEnhancer\ContentEnhancerInterface;
use Sulu\Page\Domain\Model\PageInterface;
use Sulu\Page\Domain\Repository\PageRepositoryInterface;
use Sulu\Page\Infrastructure\Sulu\Content\PageLinkProvider;
use Symfony\Contracts\Translation\TranslatorInterface;

class PageLinkProviderTest extends TestCase
{
    use ProphecyTrait;
    use SetGetPrivatePropertyTrait;

    /**
     * @var ObjectProphecy<ContentAggregatorInterface>
     */
    private ObjectProphecy $contentAggregator;

    /**
     * @var ObjectProphecy<ContentEnhancerInterface>
     */
    private ObjectProphecy $contentEnhancer;

    /**
     * @var ObjectProphecy<PageRepositoryInterface>
     */
    private ObjectProphecy $pageRepository;

    /**
     * @var ObjectProphecy<ReferenceStoreInterface>
     */
    private ObjectProphecy $referenceStore;

    /**
     * @var ObjectProphecy<TranslatorInterface>
     */
    private ObjectProphecy $translator;

    private PageLinkProvider $pageLinkProvider;

    protected function setUp(): void
    {
        $this->contentAggregator = $this->prophesize(ContentAggregatorInterface::class);
        $this->contentEnhancer = $this->prophesize(ContentEnhancerInterface::class);
        $this->pageRepository = $this->prophesize(PageRepositoryInterface::class);
        $this->referenceStore = $this->prophesize(ReferenceStoreInterface::class);
        $this->translator = $this->prophesize(TranslatorInterface::class);
        $this->translator->trans(Argument::cetera())->willReturnArgument(0);

        $this->pageLinkProvider = new PageLinkProvider(
            $this->contentAggregator->reveal(),
            $this->contentEnhancer->reveal(),
            $this->pageRepository->reveal(),
            $this->referenceStore->reveal(),
            $this->translator->reveal()
        );
    }

    public function testGetConfigurationBuilder(): void
    {
        $linkConfigurationBuilder = $this->pageLinkProvider->getConfigurationBuilder();
        $linkConfiguration = $linkConfigurationBuilder->getLinkConfiguration();

        $this->assertSame(
            PageInterface::RESOURCE_KEY,
            self::getPrivateProperty($linkConfiguration, 'resourceKey'),
        );

        $this->assertSame(
            'column_list',
            self::getPrivateProperty($linkConfiguration, 'listAdapter'),
        );

        $this->assertSame([
            'title',
        ], self::getPrivateProperty($linkConfiguration, 'displayProperties'));
    }
}
