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

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\ActivityBundle\Application\Collector\DomainEventCollectorInterface;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Page\Application\Message\RemovePageTranslationMessage;
use Sulu\Page\Application\MessageHandler\RemovePageTranslationMessageHandler;
use Sulu\Page\Domain\Event\PageTranslationRemovedEvent;
use Sulu\Page\Domain\Model\Page;
use Sulu\Page\Domain\Model\PageDimensionContent;
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

    public function testRemoveDimensionContentWhenDirectLocaleMatches(): void
    {
        $identifier = ['uuid' => 'page-123'];
        $locale = 'en';
        $message = new RemovePageTranslationMessage($identifier, $locale);

        $page = new Page('page-123');
        $dimensionContent = new PageDimensionContent($page);
        $dimensionContent->setLocale($locale);
        $dimensionContent->setStage(DimensionContentInterface::STAGE_DRAFT);

        $page->addDimensionContent($dimensionContent);

        $this->pageRepository->getOneBy($identifier)->willReturn($page);
        $this->pageRepository->removeDimensionContent($dimensionContent)->shouldBeCalled();
        $this->domainEventCollector->collect(Argument::type(PageTranslationRemovedEvent::class))->shouldBeCalled();

        ($this->handler)($message);

        $this->assertCount(0, $page->getDimensionContents());
    }

    public function testRemoveDimensionContentWhenGhostLocaleMatchesAndNoRemainingLocales(): void
    {
        $identifier = ['uuid' => 'page-123'];
        $locale = 'en';
        $message = new RemovePageTranslationMessage($identifier, $locale);

        $page = new Page('page-123');
        $dimensionContent = new PageDimensionContent($page);
        $dimensionContent->setLocale(null);
        $dimensionContent->setGhostLocale($locale);
        $dimensionContent->addAvailableLocale('en');
        $dimensionContent->setStage(DimensionContentInterface::STAGE_DRAFT);

        $page->addDimensionContent($dimensionContent);

        $this->pageRepository->getOneBy($identifier)->willReturn($page);
        $this->pageRepository->removeDimensionContent($dimensionContent)->shouldBeCalled();
        $this->domainEventCollector->collect(Argument::type(PageTranslationRemovedEvent::class))->shouldBeCalled();

        ($this->handler)($message);

        $this->assertCount(0, $page->getDimensionContents());
    }

    public function testUpdateGhostLocaleWhenGhostLocaleMatchesWithRemainingLocales(): void
    {
        $identifier = ['uuid' => 'page-123'];
        $locale = 'en';
        $message = new RemovePageTranslationMessage($identifier, $locale);

        $page = new Page('page-123');
        $dimensionContent = new PageDimensionContent($page);
        $dimensionContent->setLocale(null);
        $dimensionContent->setGhostLocale($locale);
        $dimensionContent->addAvailableLocale('en');
        $dimensionContent->addAvailableLocale('de');
        $dimensionContent->addAvailableLocale('fr');
        $dimensionContent->setStage(DimensionContentInterface::STAGE_DRAFT);

        $page->addDimensionContent($dimensionContent);

        $this->pageRepository->getOneBy($identifier)->willReturn($page);
        $this->pageRepository->removeDimensionContent(Argument::any())->shouldNotBeCalled();
        $this->domainEventCollector->collect(Argument::type(PageTranslationRemovedEvent::class))->shouldBeCalled();

        ($this->handler)($message);

        $this->assertCount(1, $page->getDimensionContents());
        $this->assertSame('de', $dimensionContent->getGhostLocale());
        $this->assertSame(['de', 'fr'], $dimensionContent->getAvailableLocales());
    }

    public function testHandleNullAvailableLocales(): void
    {
        $identifier = ['uuid' => 'page-123'];
        $locale = 'en';
        $message = new RemovePageTranslationMessage($identifier, $locale);

        $page = new Page('page-123');
        $dimensionContent = new PageDimensionContent($page);
        $dimensionContent->setLocale(null);
        $dimensionContent->setGhostLocale($locale);
        $dimensionContent->setStage(DimensionContentInterface::STAGE_DRAFT);

        $page->addDimensionContent($dimensionContent);

        $this->pageRepository->getOneBy($identifier)->willReturn($page);
        $this->pageRepository->removeDimensionContent($dimensionContent)->shouldBeCalled();
        $this->domainEventCollector->collect(Argument::type(PageTranslationRemovedEvent::class))->shouldBeCalled();

        ($this->handler)($message);

        $this->assertCount(0, $page->getDimensionContents());
    }

    public function testProcessMultipleDimensionContents(): void
    {
        $identifier = ['uuid' => 'page-123'];
        $locale = 'en';
        $message = new RemovePageTranslationMessage($identifier, $locale);

        $page = new Page('page-123');

        $dimensionContent1 = new PageDimensionContent($page);
        $dimensionContent1->setLocale($locale);
        $dimensionContent1->setStage(DimensionContentInterface::STAGE_DRAFT);
        $page->addDimensionContent($dimensionContent1);

        $dimensionContent2 = new PageDimensionContent($page);
        $dimensionContent2->setLocale('de');
        $dimensionContent2->setStage(DimensionContentInterface::STAGE_DRAFT);
        $page->addDimensionContent($dimensionContent2);

        $dimensionContent3 = new PageDimensionContent($page);
        $dimensionContent3->setLocale(null);
        $dimensionContent3->setGhostLocale($locale);
        $dimensionContent3->addAvailableLocale('en');
        $dimensionContent3->addAvailableLocale('de');
        $dimensionContent3->setStage(DimensionContentInterface::STAGE_LIVE);
        $page->addDimensionContent($dimensionContent3);

        $this->pageRepository->getOneBy($identifier)->willReturn($page);
        $this->pageRepository->removeDimensionContent($dimensionContent1)->shouldBeCalled();
        $this->pageRepository->removeDimensionContent($dimensionContent2)->shouldNotBeCalled();
        $this->pageRepository->removeDimensionContent($dimensionContent3)->shouldNotBeCalled();
        $this->domainEventCollector->collect(Argument::type(PageTranslationRemovedEvent::class))->shouldBeCalled();

        ($this->handler)($message);

        $this->assertCount(2, $page->getDimensionContents());
        $this->assertSame('de', $dimensionContent3->getGhostLocale());
        $this->assertSame(['de'], $dimensionContent3->getAvailableLocales());
    }

    public function testCollectsDomainEvent(): void
    {
        $identifier = ['uuid' => 'page-123'];
        $locale = 'en';
        $message = new RemovePageTranslationMessage($identifier, $locale);

        $page = new Page('page-123');
        $dimensionContent = new PageDimensionContent($page);
        $dimensionContent->setLocale($locale);
        $dimensionContent->setStage(DimensionContentInterface::STAGE_DRAFT);
        $page->addDimensionContent($dimensionContent);

        $this->pageRepository->getOneBy($identifier)->willReturn($page);
        $this->pageRepository->removeDimensionContent($dimensionContent)->shouldBeCalled();

        $this->domainEventCollector->collect(Argument::that(function(PageTranslationRemovedEvent $event) use ($page, $locale) {
            return $event->getPage() === $page && $event->getResourceLocale() === $locale;
        }))->shouldBeCalled();

        ($this->handler)($message);
    }
}
