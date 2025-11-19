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

namespace Sulu\Page\Tests\Unit\Infrastructure\Sulu\Search;

use CmsIg\Seal\Reindex\ReindexConfig;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\TestBundle\Testing\SetGetPrivatePropertyTrait;
use Sulu\Page\Domain\Event\PageCreatedEvent;
use Sulu\Page\Domain\Event\PageModifiedEvent;
use Sulu\Page\Domain\Event\PageRemovedEvent;
use Sulu\Page\Domain\Event\PageRestoredEvent;
use Sulu\Page\Domain\Event\PageTranslationAddedEvent;
use Sulu\Page\Domain\Event\PageTranslationRemovedEvent;
use Sulu\Page\Domain\Model\Page;
use Sulu\Page\Domain\Model\PageInterface;
use Sulu\Page\Infrastructure\Sulu\Search\AdminPageIndexListener;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

#[CoversClass(AdminPageIndexListener::class)]
class AdminPageIndexListenerTest extends TestCase
{
    use ProphecyTrait;
    use SetGetPrivatePropertyTrait;

    /**
     * @var ObjectProphecy<MessageBusInterface>
     */
    private ObjectProphecy $messageBus;
    private AdminPageIndexListener $listener;

    protected function setUp(): void
    {
        $this->messageBus = $this->prophesize(MessageBusInterface::class);
        $this->listener = new AdminPageIndexListener($this->messageBus->reveal());
    }

    public function testOnPageChangedWithPageCreatedEvent(): void
    {
        $page = new Page('123');
        $event = new PageCreatedEvent($page, 'en', []);

        $expectedConfig = ReindexConfig::create()
            ->withIndex('admin')
            ->withIdentifiers([PageInterface::RESOURCE_KEY . '__123__en']);

        $this->messageBus->dispatch($expectedConfig)
            ->willReturn(new Envelope($expectedConfig))
            ->shouldBeCalledOnce();

        $this->listener->onPageChanged($event);
    }

    public function testOnPageChangedWithPageModifiedEvent(): void
    {
        $page = new Page('456');
        $event = new PageModifiedEvent($page, 'en', []);

        $expectedConfig = ReindexConfig::create()
            ->withIndex('admin')
            ->withIdentifiers([PageInterface::RESOURCE_KEY . '__456__en']);

        $this->messageBus->dispatch($expectedConfig)
            ->willReturn(new Envelope($expectedConfig))
            ->shouldBeCalledOnce();

        $this->listener->onPageChanged($event);
    }

    public function testOnPageChangedWithPageRemovedEvent(): void
    {
        $page = new Page('789');
        $event = new PageRemovedEvent($page->getId(), 'webspace', 'Title', ['locales' => ['en', 'de']]);

        $expectedConfig = ReindexConfig::create()
            ->withIndex('admin')
            ->withIdentifiers([PageInterface::RESOURCE_KEY . '__789__en', PageInterface::RESOURCE_KEY . '__789__de']);

        $this->messageBus->dispatch($expectedConfig)
            ->willReturn(new Envelope($expectedConfig))
            ->shouldBeCalledOnce();

        $this->listener->onPageChanged($event);
    }

    public function testOnPageChangedWithPageRestored(): void
    {
        $page = new Page('222');
        $event = new PageRestoredEvent($page, 'pageTitle', ['locales' => ['de']], []);
        $dimensionContent = $page->createDimensionContent();
        $dimensionContent->setLocale(null);
        $dimensionContent->addAvailableLocale('de');
        $page->addDimensionContent($dimensionContent);

        $expectedConfig = ReindexConfig::create()
            ->withIndex('admin')
            ->withIdentifiers([PageInterface::RESOURCE_KEY . '__222__de']);

        $this->messageBus->dispatch($expectedConfig)
            ->willReturn(new Envelope($expectedConfig))
            ->shouldBeCalledOnce();

        $this->listener->onPageChanged($event);
    }

    public function testOnPageChangedWithPageTranslationAddedEvent(): void
    {
        $page = new Page('333');
        $event = new PageTranslationAddedEvent($page, 'de', []);

        $expectedConfig = ReindexConfig::create()
            ->withIndex('admin')
            ->withIdentifiers([PageInterface::RESOURCE_KEY . '__333__de']);

        $this->messageBus->dispatch($expectedConfig)
            ->willReturn(new Envelope($expectedConfig))
            ->shouldBeCalledOnce();

        $this->listener->onPageChanged($event);
    }

    public function testOnPageChangedWithPageTranslationRemovedEvent(): void
    {
        $page = new Page('444');
        $event = new PageTranslationRemovedEvent($page, 'de');

        $expectedConfig = ReindexConfig::create()
            ->withIndex('admin')
            ->withIdentifiers([PageInterface::RESOURCE_KEY . '__444__de']);

        $this->messageBus->dispatch($expectedConfig)
            ->willReturn(new Envelope($expectedConfig))
            ->shouldBeCalledOnce();

        $this->listener->onPageChanged($event);
    }
}
