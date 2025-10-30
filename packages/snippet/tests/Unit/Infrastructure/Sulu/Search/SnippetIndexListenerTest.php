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

namespace Sulu\Snippet\Tests\Unit\Infrastructure\Sulu\Search;

use CmsIg\Seal\Reindex\ReindexConfig;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\TestBundle\Testing\SetGetPrivatePropertyTrait;
use Sulu\Snippet\Domain\Event\SnippetCreatedEvent;
use Sulu\Snippet\Domain\Event\SnippetModifiedEvent;
use Sulu\Snippet\Domain\Event\SnippetRemovedEvent;
use Sulu\Snippet\Domain\Event\SnippetRestoredEvent;
use Sulu\Snippet\Domain\Event\SnippetTranslationAddedEvent;
use Sulu\Snippet\Domain\Event\SnippetTranslationRemovedEvent;
use Sulu\Snippet\Domain\Model\Snippet;
use Sulu\Snippet\Domain\Model\SnippetInterface;
use Sulu\Snippet\Infrastructure\Sulu\Search\SnippetIndexListener;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

#[CoversClass(SnippetIndexListener::class)]
class SnippetIndexListenerTest extends TestCase
{
    use ProphecyTrait;
    use SetGetPrivatePropertyTrait;

    /**
     * @var ObjectProphecy<MessageBusInterface>
     */
    private ObjectProphecy $messageBus;
    private SnippetIndexListener $listener;

    protected function setUp(): void
    {
        $this->messageBus = $this->prophesize(MessageBusInterface::class);
        $this->listener = new SnippetIndexListener($this->messageBus->reveal());
    }

    public function testOnSnippetChangedWithSnippetCreatedEvent(): void
    {
        $snippet = new Snippet('123');
        $event = new SnippetCreatedEvent($snippet, 'en', []);

        $expectedConfig = ReindexConfig::create()
            ->withIndex('admin')
            ->withIdentifiers([SnippetInterface::RESOURCE_KEY . '::123::en']);

        $this->messageBus->dispatch($expectedConfig)
            ->willReturn(new Envelope($expectedConfig))
            ->shouldBeCalledOnce();

        $this->listener->onSnippetChanged($event);
    }

    public function testOnSnippetChangedWithSnippetModifiedEvent(): void
    {
        $snippet = new Snippet('456');
        $event = new SnippetModifiedEvent($snippet, 'en', []);

        $expectedConfig = ReindexConfig::create()
            ->withIndex('admin')
            ->withIdentifiers([SnippetInterface::RESOURCE_KEY . '::456::en']);

        $this->messageBus->dispatch($expectedConfig)
            ->willReturn(new Envelope($expectedConfig))
            ->shouldBeCalledOnce();

        $this->listener->onSnippetChanged($event);
    }

    public function testOnSnippetChangedWithSnippetRemovedEvent(): void
    {
        $snippet = new Snippet('789');
        $event = new SnippetRemovedEvent($snippet->getId(), 'Uncool snippet', ['locales' => ['en', 'de']]);

        $expectedConfig = ReindexConfig::create()
            ->withIndex('admin')
            ->withIdentifiers([SnippetInterface::RESOURCE_KEY . '::789::en', SnippetInterface::RESOURCE_KEY . '::789::de']);

        $this->messageBus->dispatch($expectedConfig)
            ->willReturn(new Envelope($expectedConfig))
            ->shouldBeCalledOnce();

        $this->listener->onSnippetChanged($event);
    }

    public function testOnSnippetChangedWithSnippetRestored(): void
    {
        $snippet = new Snippet('222');
        $event = new SnippetRestoredEvent($snippet, 'test Snippet', ['locales' => ['de']], []);
        $dimensionContent = $snippet->createDimensionContent();
        $dimensionContent->setLocale(null);
        $dimensionContent->addAvailableLocale('de');
        $snippet->addDimensionContent($dimensionContent);

        $expectedConfig = ReindexConfig::create()
            ->withIndex('admin')
            ->withIdentifiers([SnippetInterface::RESOURCE_KEY . '::222::de']);

        $this->messageBus->dispatch($expectedConfig)
            ->willReturn(new Envelope($expectedConfig))
            ->shouldBeCalledOnce();

        $this->listener->onSnippetChanged($event);
    }

    public function testOnSnippetChangedWithSnippetTranslationAddedEvent(): void
    {
        $snippet = new Snippet('333');
        $event = new SnippetTranslationAddedEvent($snippet, 'de', []);

        $expectedConfig = ReindexConfig::create()
            ->withIndex('admin')
            ->withIdentifiers([SnippetInterface::RESOURCE_KEY . '::333::de']);

        $this->messageBus->dispatch($expectedConfig)
            ->willReturn(new Envelope($expectedConfig))
            ->shouldBeCalledOnce();

        $this->listener->onSnippetChanged($event);
    }

    public function testOnSnippetChangedWithSnippetTranslationRemovedEvent(): void
    {
        $snippet = new Snippet('444');
        $event = new SnippetTranslationRemovedEvent($snippet, 'de');

        $expectedConfig = ReindexConfig::create()
            ->withIndex('admin')
            ->withIdentifiers([SnippetInterface::RESOURCE_KEY . '::444::de']);

        $this->messageBus->dispatch($expectedConfig)
            ->willReturn(new Envelope($expectedConfig))
            ->shouldBeCalledOnce();

        $this->listener->onSnippetChanged($event);
    }
}
