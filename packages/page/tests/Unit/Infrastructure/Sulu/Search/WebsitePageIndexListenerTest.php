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
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Page\Domain\Event\PageRemovedEvent;
use Sulu\Page\Domain\Event\PageTranslationRemovedEvent;
use Sulu\Page\Domain\Event\PageWorkflowTransitionAppliedEvent;
use Sulu\Page\Domain\Model\Page;
use Sulu\Page\Domain\Model\PageInterface;
use Sulu\Page\Infrastructure\Sulu\Search\WebsitePageIndexListener;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

#[CoversClass(WebsitePageIndexListener::class)]
class WebsitePageIndexListenerTest extends TestCase
{
    use ProphecyTrait;
    use SetGetPrivatePropertyTrait;

    /**
     * @var ObjectProphecy<MessageBusInterface>
     */
    private ObjectProphecy $messageBus;
    private WebsitePageIndexListener $listener;

    protected function setUp(): void
    {
        $this->messageBus = $this->prophesize(MessageBusInterface::class);
        $this->listener = new WebsitePageIndexListener($this->messageBus->reveal());
    }

    public function testOnPageChangedWithPageWorkflowTransitionAppliedEvent(): void
    {
        $page = new Page('123');
        $event = new PageWorkflowTransitionAppliedEvent($page, DimensionContentInterface::STAGE_LIVE, 'en');

        $expectedConfig = ReindexConfig::create()
            ->withIndex('website')
            ->withIdentifiers([PageInterface::RESOURCE_KEY . '__123__en']);

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
            ->withIndex('website')
            ->withIdentifiers([PageInterface::RESOURCE_KEY . '__789__en', PageInterface::RESOURCE_KEY . '__789__de']);

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
            ->withIndex('website')
            ->withIdentifiers([PageInterface::RESOURCE_KEY . '__444__de']);

        $this->messageBus->dispatch($expectedConfig)
            ->willReturn(new Envelope($expectedConfig))
            ->shouldBeCalledOnce();

        $this->listener->onPageChanged($event);
    }
}
