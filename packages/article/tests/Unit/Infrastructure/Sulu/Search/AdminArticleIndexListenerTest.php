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

namespace Sulu\Article\Tests\Unit\Infrastructure\Sulu\Search;

use CmsIg\Seal\Reindex\ReindexConfig;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Article\Domain\Event\ArticleCreatedEvent;
use Sulu\Article\Domain\Event\ArticleModifiedEvent;
use Sulu\Article\Domain\Event\ArticleRemovedEvent;
use Sulu\Article\Domain\Event\ArticleRestoredEvent;
use Sulu\Article\Domain\Event\ArticleTranslationAddedEvent;
use Sulu\Article\Domain\Event\ArticleTranslationRemovedEvent;
use Sulu\Article\Domain\Model\Article;
use Sulu\Article\Domain\Model\ArticleInterface;
use Sulu\Article\Infrastructure\Sulu\Search\AdminArticleIndexListener;
use Sulu\Bundle\TestBundle\Testing\SetGetPrivatePropertyTrait;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

#[CoversClass(AdminArticleIndexListener::class)]
class AdminArticleIndexListenerTest extends TestCase
{
    use ProphecyTrait;
    use SetGetPrivatePropertyTrait;

    /**
     * @var ObjectProphecy<MessageBusInterface>
     */
    private ObjectProphecy $messageBus;
    private AdminArticleIndexListener $listener;

    protected function setUp(): void
    {
        $this->messageBus = $this->prophesize(MessageBusInterface::class);
        $this->listener = new AdminArticleIndexListener($this->messageBus->reveal());
    }

    public function testOnArticleChangedWithArticleCreatedEvent(): void
    {
        $article = new Article('123');
        $event = new ArticleCreatedEvent($article, 'en', []);

        $expectedConfig = ReindexConfig::create()
            ->withIndex('admin')
            ->withIdentifiers([ArticleInterface::RESOURCE_KEY . '::123::en']);

        $this->messageBus->dispatch($expectedConfig)
            ->willReturn(new Envelope($expectedConfig))
            ->shouldBeCalledOnce();

        $this->listener->onArticleChanged($event);
    }

    public function testOnArticleChangedWithArticleModifiedEvent(): void
    {
        $article = new Article('456');
        $event = new ArticleModifiedEvent($article, 'en', []);

        $expectedConfig = ReindexConfig::create()
            ->withIndex('admin')
            ->withIdentifiers([ArticleInterface::RESOURCE_KEY . '::456::en']);

        $this->messageBus->dispatch($expectedConfig)
            ->willReturn(new Envelope($expectedConfig))
            ->shouldBeCalledOnce();

        $this->listener->onArticleChanged($event);
    }

    public function testOnArticleChangedWithArticleRemovedEvent(): void
    {
        $article = new Article('789');
        $event = new ArticleRemovedEvent($article->getId(), 'Uncool article', ['locales' => ['en', 'de']]);

        $expectedConfig = ReindexConfig::create()
            ->withIndex('admin')
            ->withIdentifiers([ArticleInterface::RESOURCE_KEY . '::789::en', ArticleInterface::RESOURCE_KEY . '::789::de']);

        $this->messageBus->dispatch($expectedConfig)
            ->willReturn(new Envelope($expectedConfig))
            ->shouldBeCalledOnce();

        $this->listener->onArticleChanged($event);
    }

    public function testOnArticleChangedWithArticleRestored(): void
    {
        $article = new Article('222');
        $event = new ArticleRestoredEvent($article, 'test article', ['locales' => ['de']], []);
        $dimensionContent = $article->createDimensionContent();
        $dimensionContent->setLocale(null);
        $dimensionContent->addAvailableLocale('de');
        $article->addDimensionContent($dimensionContent);

        $expectedConfig = ReindexConfig::create()
            ->withIndex('admin')
            ->withIdentifiers([ArticleInterface::RESOURCE_KEY . '::222::de']);

        $this->messageBus->dispatch($expectedConfig)
            ->willReturn(new Envelope($expectedConfig))
            ->shouldBeCalledOnce();

        $this->listener->onArticleChanged($event);
    }

    public function testOnArticleChangedWithArticleTranslationAddedEvent(): void
    {
        $article = new Article('333');
        $event = new ArticleTranslationAddedEvent($article, 'de', []);

        $expectedConfig = ReindexConfig::create()
            ->withIndex('admin')
            ->withIdentifiers([ArticleInterface::RESOURCE_KEY . '::333::de']);

        $this->messageBus->dispatch($expectedConfig)
            ->willReturn(new Envelope($expectedConfig))
            ->shouldBeCalledOnce();

        $this->listener->onArticleChanged($event);
    }

    public function testOnArticleChangedWithArticleTranslationRemovedEvent(): void
    {
        $article = new Article('444');
        $event = new ArticleTranslationRemovedEvent($article, 'de');

        $expectedConfig = ReindexConfig::create()
            ->withIndex('admin')
            ->withIdentifiers([ArticleInterface::RESOURCE_KEY . '::444::de']);

        $this->messageBus->dispatch($expectedConfig)
            ->willReturn(new Envelope($expectedConfig))
            ->shouldBeCalledOnce();

        $this->listener->onArticleChanged($event);
    }
}
