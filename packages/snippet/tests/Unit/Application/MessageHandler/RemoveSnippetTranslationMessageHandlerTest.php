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

use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Sulu\Bundle\ActivityBundle\Application\Collector\DomainEventCollectorInterface;
use Sulu\Snippet\Application\Message\RemoveSnippetTranslationMessage;
use Sulu\Snippet\Application\MessageHandler\RemoveSnippetTranslationMessageHandler;
use Sulu\Snippet\Domain\Event\SnippetTranslationRemovedEvent;
use Sulu\Snippet\Domain\Model\SnippetDimensionContent;
use Sulu\Snippet\Domain\Model\SnippetInterface;
use Sulu\Snippet\Domain\Repository\SnippetRepositoryInterface;

class RemoveSnippetTranslationMessageHandlerTest extends TestCase
{
    use ProphecyTrait;

    private $snippetRepository;
    private $domainEventCollector;
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

    public function testInvokeRemovesMatchingLocale(): void
    {
        $identifier = ['uuid' => 'snippet-123'];
        $locale = 'en';
        $message = new RemoveSnippetTranslationMessage($identifier, $locale);

        $snippet = $this->prophesize(SnippetInterface::class);
        $dimensionContent1 = $this->prophesize(SnippetDimensionContent::class);
        $dimensionContent1->getLocale()->willReturn('en');
        $dimensionContent1->getGhostLocale()->willReturn(null);

        $dimensionContent2 = $this->prophesize(SnippetDimensionContent::class);
        $dimensionContent2->getLocale()->willReturn('de');
        $dimensionContent2->getGhostLocale()->willReturn(null);

        $dimensionContents = new ArrayCollection([
            $dimensionContent1->reveal(),
            $dimensionContent2->reveal(),
        ]);

        $this->snippetRepository->getOneBy($identifier)->willReturn($snippet->reveal());
        $snippet->getDimensionContents()->willReturn($dimensionContents);

        $snippet->removeDimensionContent($dimensionContent1->reveal())->shouldBeCalled();
        $this->snippetRepository->removeDimensionContent($dimensionContent1->reveal())->shouldBeCalled();

        $snippet->removeDimensionContent($dimensionContent2->reveal())->shouldNotBeCalled();
        $this->snippetRepository->removeDimensionContent($dimensionContent2->reveal())->shouldNotBeCalled();

        $this->domainEventCollector->collect(
            Argument::type(SnippetTranslationRemovedEvent::class)
        )->shouldBeCalled();

        ($this->handler)($message);
    }

    public function testInvokeRemovesMatchingGhostLocale(): void
    {
        $identifier = ['uuid' => 'snippet-123'];
        $locale = 'en';
        $message = new RemoveSnippetTranslationMessage($identifier, $locale);

        $snippet = $this->prophesize(SnippetInterface::class);
        $dimensionContent1 = $this->prophesize(SnippetDimensionContent::class);
        $dimensionContent1->getLocale()->willReturn('de');
        $dimensionContent1->getGhostLocale()->willReturn('en');

        $dimensionContents = new ArrayCollection([$dimensionContent1->reveal()]);

        $this->snippetRepository->getOneBy($identifier)->willReturn($snippet->reveal());
        $snippet->getDimensionContents()->willReturn($dimensionContents);

        $snippet->removeDimensionContent($dimensionContent1->reveal())->shouldBeCalled();
        $this->snippetRepository->removeDimensionContent($dimensionContent1->reveal())->shouldBeCalled();

        $this->domainEventCollector->collect(
            Argument::type(SnippetTranslationRemovedEvent::class)
        )->shouldBeCalled();

        ($this->handler)($message);
    }

    public function testInvokeUpdatesGhostLocaleInElseIfBranch(): void
    {
        $identifier = ['uuid' => 'snippet-123'];
        $locale = 'en';
        $message = new RemoveSnippetTranslationMessage($identifier, $locale);

        $snippet = $this->prophesize(SnippetInterface::class);
        $dimensionContent1 = $this->prophesize(SnippetDimensionContent::class);
        $dimensionContent1->getLocale()->willReturn('de');
        $dimensionContent1->getGhostLocale()->willReturn('en');

        $dimensionContents = new ArrayCollection([$dimensionContent1->reveal()]);

        $this->snippetRepository->getOneBy($identifier)->willReturn($snippet->reveal());
        $snippet->getDimensionContents()->willReturn($dimensionContents);

        $snippet->removeDimensionContent($dimensionContent1->reveal())->shouldBeCalled();
        $this->snippetRepository->removeDimensionContent($dimensionContent1->reveal())->shouldBeCalled();

        $dimensionContent1->getAvailableLocales()->shouldNotBeCalled();
        $dimensionContent1->setGhostLocale(Argument::any())->shouldNotBeCalled();
        $dimensionContent1->removeAvailableLocale(Argument::any())->shouldNotBeCalled();

        $this->domainEventCollector->collect(
            Argument::type(SnippetTranslationRemovedEvent::class)
        )->shouldBeCalled();

        ($this->handler)($message);
    }

    public function testInvokeHandlesMultipleDimensionContents(): void
    {
        $identifier = ['uuid' => 'snippet-123'];
        $locale = 'en';
        $message = new RemoveSnippetTranslationMessage($identifier, $locale);

        $snippet = $this->prophesize(SnippetInterface::class);

        // Dimension content with matching locale
        $dimensionContent1 = $this->prophesize(SnippetDimensionContent::class);
        $dimensionContent1->getLocale()->willReturn('en');
        $dimensionContent1->getGhostLocale()->willReturn(null);

        // Dimension content with matching ghost locale
        $dimensionContent2 = $this->prophesize(SnippetDimensionContent::class);
        $dimensionContent2->getLocale()->willReturn('de');
        $dimensionContent2->getGhostLocale()->willReturn('en');

        // Dimension content that should not be affected
        $dimensionContent3 = $this->prophesize(SnippetDimensionContent::class);
        $dimensionContent3->getLocale()->willReturn('de');
        $dimensionContent3->getGhostLocale()->willReturn(null);

        $dimensionContents = new ArrayCollection([
            $dimensionContent1->reveal(),
            $dimensionContent2->reveal(),
            $dimensionContent3->reveal(),
        ]);

        $this->snippetRepository->getOneBy($identifier)->willReturn($snippet->reveal());
        $snippet->getDimensionContents()->willReturn($dimensionContents);

        // First dimension content should be removed
        $snippet->removeDimensionContent($dimensionContent1->reveal())->shouldBeCalled();
        $this->snippetRepository->removeDimensionContent($dimensionContent1->reveal())->shouldBeCalled();

        // Second dimension content should be removed
        $snippet->removeDimensionContent($dimensionContent2->reveal())->shouldBeCalled();
        $this->snippetRepository->removeDimensionContent($dimensionContent2->reveal())->shouldBeCalled();

        // Third dimension content should not be affected
        $snippet->removeDimensionContent($dimensionContent3->reveal())->shouldNotBeCalled();
        $this->snippetRepository->removeDimensionContent($dimensionContent3->reveal())->shouldNotBeCalled();

        $this->domainEventCollector->collect(
            Argument::type(SnippetTranslationRemovedEvent::class)
        )->shouldBeCalled();

        ($this->handler)($message);
    }

    public function testInvokeCollectsEvent(): void
    {
        $identifier = ['uuid' => 'snippet-123'];
        $locale = 'en';
        $message = new RemoveSnippetTranslationMessage($identifier, $locale);

        $snippet = $this->prophesize(SnippetInterface::class);
        $this->snippetRepository->getOneBy($identifier)->willReturn($snippet->reveal());
        $snippet->getDimensionContents()->willReturn(new ArrayCollection([]));

        $this->domainEventCollector->collect(Argument::that(function ($event) use ($snippet, $locale) {
            return $event instanceof SnippetTranslationRemovedEvent
                && $event->getSnippet() === $snippet->reveal()
                && $event->getResourceLocale() === $locale;
        }))->shouldBeCalled();

        ($this->handler)($message);
    }
}
