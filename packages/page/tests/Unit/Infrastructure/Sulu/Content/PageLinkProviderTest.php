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
use Sulu\Content\Application\ContentManager\ContentManagerInterface;
use Sulu\Page\Domain\Model\PageInterface;
use Sulu\Page\Domain\Repository\PageRepositoryInterface;
use Sulu\Page\Infrastructure\Sulu\Content\PageLinkProvider;
use Symfony\Contracts\Translation\TranslatorInterface;

class PageLinkProviderTest extends TestCase
{
    use ProphecyTrait;
    use SetGetPrivatePropertyTrait;

    private PageLinkProvider $pageLinkProvider;
    private ObjectProphecy $contentManager;
    private ObjectProphecy $pageRepository;
    private ObjectProphecy $referenceStore;
    private ObjectProphecy $translator;

    protected function setUp(): void
    {
        $this->contentManager = $this->prophesize(ContentManagerInterface::class);
        $this->pageRepository = $this->prophesize(PageRepositoryInterface::class);
        $this->referenceStore = $this->prophesize(ReferenceStoreInterface::class);
        $this->translator = $this->prophesize(TranslatorInterface::class);
        $this->translator->trans(Argument::cetera())->willReturnArgument(0);

        $this->pageLinkProvider = new PageLinkProvider(
            $this->contentManager->reveal(),
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
