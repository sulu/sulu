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

namespace Sulu\Snippet\Tests\Unit\Application\MessageHandler;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\ActivityBundle\Application\Collector\DomainEventCollectorInterface;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Snippet\Application\Message\RemoveSnippetTranslationMessage;
use Sulu\Snippet\Application\MessageHandler\RemoveSnippetTranslationMessageHandler;
use Sulu\Snippet\Domain\Event\SnippetTranslationRemovedEvent;
use Sulu\Snippet\Domain\Model\Snippet;
use Sulu\Snippet\Domain\Model\SnippetDimensionContent;
use Sulu\Snippet\Domain\Repository\SnippetRepositoryInterface;

class RemoveSnippetTranslationMessageHandlerTest extends TestCase
{
    use ProphecyTrait;

    /** @var ObjectProphecy<SnippetRepositoryInterface> */
    private ObjectProphecy $snippetRepository;
    /** @var ObjectProphecy<DomainEventCollectorInterface> */
    private ObjectProphecy $domainEventCollector;
    private RemoveSnippetTranslationMessageHandler $handler;

    protected function setUp(): void
    {
        $this->snippetRepository = $this->prophesize(SnippetRepositoryInterface::class);
        $this->domainEventCollector = $this->prophesize(DomainEventCollectorInterface::class);

        $this->handler = new RemoveSnippetTranslationMessageHandler(
            $this->snippetRepository->reveal(),
            $this->domainEventCollector->reveal()
        );
    }

    public function testRemoveDimensionContentWhenDirectLocaleMatches(): void
    {
        $identifier = ['uuid' => 'snippet-123'];
        $locale = 'en';
        $message = new RemoveSnippetTranslationMessage($identifier, $locale);

        $snippet = new Snippet('snippet-123');
        $dimensionContent = new SnippetDimensionContent($snippet);
        $dimensionContent->setLocale($locale);
        $dimensionContent->setStage(DimensionContentInterface::STAGE_DRAFT);

        $snippet->addDimensionContent($dimensionContent);

        $this->snippetRepository->getOneBy($identifier)->willReturn($snippet);
        $this->snippetRepository->removeDimensionContent($dimensionContent)->shouldBeCalled();
        $this->domainEventCollector->collect(Argument::type(SnippetTranslationRemovedEvent::class))->shouldBeCalled();

        ($this->handler)($message);

        $this->assertCount(0, $snippet->getDimensionContents());
    }

    public function testRemoveDimensionContentWhenGhostLocaleMatchesAndNoRemainingLocales(): void
    {
        $identifier = ['uuid' => 'snippet-123'];
        $locale = 'en';
        $message = new RemoveSnippetTranslationMessage($identifier, $locale);

        $snippet = new Snippet('snippet-123');
        $dimensionContent = new SnippetDimensionContent($snippet);
        $dimensionContent->setLocale(null);
        $dimensionContent->setGhostLocale($locale);
        $dimensionContent->addAvailableLocale('en');
        $dimensionContent->setStage(DimensionContentInterface::STAGE_DRAFT);

        $snippet->addDimensionContent($dimensionContent);

        $this->snippetRepository->getOneBy($identifier)->willReturn($snippet);
        $this->snippetRepository->removeDimensionContent($dimensionContent)->shouldBeCalled();
        $this->domainEventCollector->collect(Argument::type(SnippetTranslationRemovedEvent::class))->shouldBeCalled();

        ($this->handler)($message);

        $this->assertCount(0, $snippet->getDimensionContents());
    }

    public function testUpdateGhostLocaleWhenGhostLocaleMatchesWithRemainingLocales(): void
    {
        $identifier = ['uuid' => 'snippet-123'];
        $locale = 'en';
        $message = new RemoveSnippetTranslationMessage($identifier, $locale);

        $snippet = new Snippet('snippet-123');
        $dimensionContent = new SnippetDimensionContent($snippet);
        $dimensionContent->setLocale(null);
        $dimensionContent->setGhostLocale($locale);
        $dimensionContent->addAvailableLocale('en');
        $dimensionContent->addAvailableLocale('de');
        $dimensionContent->addAvailableLocale('fr');
        $dimensionContent->setStage(DimensionContentInterface::STAGE_DRAFT);

        $snippet->addDimensionContent($dimensionContent);

        $this->snippetRepository->getOneBy($identifier)->willReturn($snippet);
        $this->snippetRepository->removeDimensionContent(Argument::any())->shouldNotBeCalled();
        $this->domainEventCollector->collect(Argument::type(SnippetTranslationRemovedEvent::class))->shouldBeCalled();

        ($this->handler)($message);

        $this->assertCount(1, $snippet->getDimensionContents());
        $this->assertSame('de', $dimensionContent->getGhostLocale());
        $this->assertSame(['de', 'fr'], $dimensionContent->getAvailableLocales());
    }

    public function testHandleNullAvailableLocales(): void
    {
        $identifier = ['uuid' => 'snippet-123'];
        $locale = 'en';
        $message = new RemoveSnippetTranslationMessage($identifier, $locale);

        $snippet = new Snippet('snippet-123');
        $dimensionContent = new SnippetDimensionContent($snippet);
        $dimensionContent->setLocale(null);
        $dimensionContent->setGhostLocale($locale);
        $dimensionContent->setStage(DimensionContentInterface::STAGE_DRAFT);

        $snippet->addDimensionContent($dimensionContent);

        $this->snippetRepository->getOneBy($identifier)->willReturn($snippet);
        $this->snippetRepository->removeDimensionContent($dimensionContent)->shouldBeCalled();
        $this->domainEventCollector->collect(Argument::type(SnippetTranslationRemovedEvent::class))->shouldBeCalled();

        ($this->handler)($message);

        $this->assertCount(0, $snippet->getDimensionContents());
    }

    public function testProcessMultipleDimensionContents(): void
    {
        $identifier = ['uuid' => 'snippet-123'];
        $locale = 'en';
        $message = new RemoveSnippetTranslationMessage($identifier, $locale);

        $snippet = new Snippet('snippet-123');

        $dimensionContent1 = new SnippetDimensionContent($snippet);
        $dimensionContent1->setLocale($locale);
        $dimensionContent1->setStage(DimensionContentInterface::STAGE_DRAFT);
        $snippet->addDimensionContent($dimensionContent1);

        $dimensionContent2 = new SnippetDimensionContent($snippet);
        $dimensionContent2->setLocale('de');
        $dimensionContent2->setStage(DimensionContentInterface::STAGE_DRAFT);
        $snippet->addDimensionContent($dimensionContent2);

        $dimensionContent3 = new SnippetDimensionContent($snippet);
        $dimensionContent3->setLocale(null);
        $dimensionContent3->setGhostLocale($locale);
        $dimensionContent3->addAvailableLocale('en');
        $dimensionContent3->addAvailableLocale('de');
        $dimensionContent3->setStage(DimensionContentInterface::STAGE_LIVE);
        $snippet->addDimensionContent($dimensionContent3);

        $this->snippetRepository->getOneBy($identifier)->willReturn($snippet);
        $this->snippetRepository->removeDimensionContent($dimensionContent1)->shouldBeCalled();
        $this->snippetRepository->removeDimensionContent($dimensionContent2)->shouldNotBeCalled();
        $this->snippetRepository->removeDimensionContent($dimensionContent3)->shouldNotBeCalled();
        $this->domainEventCollector->collect(Argument::type(SnippetTranslationRemovedEvent::class))->shouldBeCalled();

        ($this->handler)($message);

        $this->assertCount(2, $snippet->getDimensionContents());
        $this->assertSame('de', $dimensionContent3->getGhostLocale());
        $this->assertSame(['de'], $dimensionContent3->getAvailableLocales());
    }

    public function testCollectsDomainEvent(): void
    {
        $identifier = ['uuid' => 'snippet-123'];
        $locale = 'en';
        $message = new RemoveSnippetTranslationMessage($identifier, $locale);

        $snippet = new Snippet('snippet-123');
        $dimensionContent = new SnippetDimensionContent($snippet);
        $dimensionContent->setLocale($locale);
        $dimensionContent->setStage(DimensionContentInterface::STAGE_DRAFT);
        $snippet->addDimensionContent($dimensionContent);

        $this->snippetRepository->getOneBy($identifier)->willReturn($snippet);
        $this->snippetRepository->removeDimensionContent($dimensionContent)->shouldBeCalled();

        $this->domainEventCollector->collect(Argument::that(function(SnippetTranslationRemovedEvent $event) use ($snippet, $locale) {
            return $event->getSnippet() === $snippet && $event->getResourceLocale() === $locale;
        }))->shouldBeCalled();

        ($this->handler)($message);
    }
}
