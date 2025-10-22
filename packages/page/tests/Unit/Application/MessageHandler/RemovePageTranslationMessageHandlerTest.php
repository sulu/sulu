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

namespace Sulu\Page\Tests\Unit\Application\MessageHandler;

use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\ActivityBundle\Application\Collector\DomainEventCollectorInterface;
use Sulu\Page\Application\Message\RemovePageTranslationMessage;
use Sulu\Page\Application\MessageHandler\RemovePageTranslationMessageHandler;
use Sulu\Page\Domain\Event\PageTranslationRemovedEvent;
use Sulu\Page\Domain\Model\PageDimensionContent;
use Sulu\Page\Domain\Model\PageInterface;
use Sulu\Page\Domain\Repository\PageRepositoryInterface;

class RemovePageTranslationMessageHandlerTest extends TestCase
{
    use ProphecyTrait;

    /** @var ObjectProphecy<PageRepositoryInterface> */
    private ObjectProphecy $pageRepository;
    /** @var ObjectProphecy<DomainEventCollectorInterface> */
    private ObjectProphecy $domainEventCollector;
    private RemovePageTranslationMessageHandler $handler;

    protected function setUp(): void
    {
        $this->pageRepository = $this->prophesize(PageRepositoryInterface::class);
        $this->domainEventCollector = $this->prophesize(DomainEventCollectorInterface::class);

        $this->handler = new RemovePageTranslationMessageHandler(
            $this->pageRepository->reveal(),
            $this->domainEventCollector->reveal()
        );
    }

    public function testInvokeRemovesMatchingLocale(): void
    {
        $identifier = ['uuid' => 'page-123'];
        $locale = 'en';
        $message = new RemovePageTranslationMessage($identifier, $locale);

        $page = $this->prophesize(PageInterface::class);
        $dimensionContent1 = $this->prophesize(PageDimensionContent::class);
        $dimensionContent1->getLocale()->willReturn('en');
        $dimensionContent1->getGhostLocale()->willReturn(null);

        $dimensionContent2 = $this->prophesize(PageDimensionContent::class);
        $dimensionContent2->getLocale()->willReturn('de');
        $dimensionContent2->getGhostLocale()->willReturn(null);

        $dimensionContents = new ArrayCollection([
            $dimensionContent1->reveal(),
            $dimensionContent2->reveal(),
        ]);

        $this->pageRepository->getOneBy($identifier)->willReturn($page->reveal());
        $page->getDimensionContents()->willReturn($dimensionContents);

        $page->removeDimensionContent($dimensionContent1->reveal())->shouldBeCalled();
        $this->pageRepository->removeDimensionContent($dimensionContent1->reveal())->shouldBeCalled();

        $page->removeDimensionContent($dimensionContent2->reveal())->shouldNotBeCalled();
        $this->pageRepository->removeDimensionContent($dimensionContent2->reveal())->shouldNotBeCalled();

        $this->domainEventCollector->collect(
            Argument::type(PageTranslationRemovedEvent::class)
        )->shouldBeCalled();

        ($this->handler)($message);
    }

    public function testInvokeRemovesMatchingGhostLocale(): void
    {
        $identifier = ['uuid' => 'page-123'];
        $locale = 'en';
        $message = new RemovePageTranslationMessage($identifier, $locale);

        $page = $this->prophesize(PageInterface::class);
        $dimensionContent1 = $this->prophesize(PageDimensionContent::class);
        $dimensionContent1->getLocale()->willReturn('de');
        $dimensionContent1->getGhostLocale()->willReturn('en');

        $dimensionContents = new ArrayCollection([$dimensionContent1->reveal()]);

        $this->pageRepository->getOneBy($identifier)->willReturn($page->reveal());
        $page->getDimensionContents()->willReturn($dimensionContents);

        $page->removeDimensionContent($dimensionContent1->reveal())->shouldBeCalled();
        $this->pageRepository->removeDimensionContent($dimensionContent1->reveal())->shouldBeCalled();

        $this->domainEventCollector->collect(
            Argument::type(PageTranslationRemovedEvent::class)
        )->shouldBeCalled();

        ($this->handler)($message);
    }

    public function testInvokeUpdatesGhostLocaleInElseIfBranch(): void
    {
        $identifier = ['uuid' => 'page-123'];
        $locale = 'en';
        $message = new RemovePageTranslationMessage($identifier, $locale);

        $page = $this->prophesize(PageInterface::class);
        $dimensionContent1 = $this->prophesize(PageDimensionContent::class);
        $dimensionContent1->getLocale()->willReturn('de');
        $dimensionContent1->getGhostLocale()->willReturn('en');

        $dimensionContents = new ArrayCollection([$dimensionContent1->reveal()]);

        $this->pageRepository->getOneBy($identifier)->willReturn($page->reveal());
        $page->getDimensionContents()->willReturn($dimensionContents);

        $page->removeDimensionContent($dimensionContent1->reveal())->shouldBeCalled();
        $this->pageRepository->removeDimensionContent($dimensionContent1->reveal())->shouldBeCalled();

        $dimensionContent1->getAvailableLocales()->shouldNotBeCalled();
        $dimensionContent1->setGhostLocale(Argument::any())->shouldNotBeCalled();
        $dimensionContent1->removeAvailableLocale(Argument::any())->shouldNotBeCalled();

        $this->domainEventCollector->collect(
            Argument::type(PageTranslationRemovedEvent::class)
        )->shouldBeCalled();

        ($this->handler)($message);
    }

    public function testInvokeHandlesMultipleDimensionContents(): void
    {
        $identifier = ['uuid' => 'page-123'];
        $locale = 'en';
        $message = new RemovePageTranslationMessage($identifier, $locale);

        $page = $this->prophesize(PageInterface::class);

        // Dimension content with matching locale
        $dimensionContent1 = $this->prophesize(PageDimensionContent::class);
        $dimensionContent1->getLocale()->willReturn('en');
        $dimensionContent1->getGhostLocale()->willReturn(null);

        // Dimension content with matching ghost locale
        $dimensionContent2 = $this->prophesize(PageDimensionContent::class);
        $dimensionContent2->getLocale()->willReturn('de');
        $dimensionContent2->getGhostLocale()->willReturn('en');

        // Dimension content that should not be affected
        $dimensionContent3 = $this->prophesize(PageDimensionContent::class);
        $dimensionContent3->getLocale()->willReturn('de');
        $dimensionContent3->getGhostLocale()->willReturn(null);

        $dimensionContents = new ArrayCollection([
            $dimensionContent1->reveal(),
            $dimensionContent2->reveal(),
            $dimensionContent3->reveal(),
        ]);

        $this->pageRepository->getOneBy($identifier)->willReturn($page->reveal());
        $page->getDimensionContents()->willReturn($dimensionContents);

        // First dimension content should be removed
        $page->removeDimensionContent($dimensionContent1->reveal())->shouldBeCalled();
        $this->pageRepository->removeDimensionContent($dimensionContent1->reveal())->shouldBeCalled();

        // Second dimension content should be removed
        $page->removeDimensionContent($dimensionContent2->reveal())->shouldBeCalled();
        $this->pageRepository->removeDimensionContent($dimensionContent2->reveal())->shouldBeCalled();

        // Third dimension content should not be affected
        $page->removeDimensionContent($dimensionContent3->reveal())->shouldNotBeCalled();
        $this->pageRepository->removeDimensionContent($dimensionContent3->reveal())->shouldNotBeCalled();

        $this->domainEventCollector->collect(
            Argument::type(PageTranslationRemovedEvent::class)
        )->shouldBeCalled();

        ($this->handler)($message);
    }

    public function testInvokeCollectsEvent(): void
    {
        $identifier = ['uuid' => 'page-123'];
        $locale = 'en';
        $message = new RemovePageTranslationMessage($identifier, $locale);

        $page = $this->prophesize(PageInterface::class);
        $this->pageRepository->getOneBy($identifier)->willReturn($page->reveal());
        $page->getDimensionContents()->willReturn(new ArrayCollection([]));

        $this->domainEventCollector->collect(Argument::that(function($event) use ($page, $locale) {
            return $event instanceof PageTranslationRemovedEvent
                && $event->getPage() === $page->reveal()
                && $event->getResourceLocale() === $locale;
        }))->shouldBeCalled();

        ($this->handler)($message);
    }
}
