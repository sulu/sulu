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
use Sulu\Article\Domain\Event\ArticleRemovedEvent;
use Sulu\Article\Domain\Event\ArticleTranslationRemovedEvent;
use Sulu\Article\Domain\Event\ArticleWorkflowTransitionAppliedEvent;
use Sulu\Article\Domain\Model\Article;
use Sulu\Article\Domain\Model\ArticleInterface;
use Sulu\Article\Infrastructure\Sulu\Search\WebsiteArticleIndexListener;
use Sulu\Bundle\TestBundle\Testing\SetGetPrivatePropertyTrait;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

#[CoversClass(WebsiteArticleIndexListener::class)]
class WebsiteArticleIndexListenerTest extends TestCase
{
    use ProphecyTrait;
    use SetGetPrivatePropertyTrait;

    /**
     * @var ObjectProphecy<MessageBusInterface>
     */
    private ObjectProphecy $messageBus;
    private WebsiteArticleIndexListener $listener;

    protected function setUp(): void
    {
        $this->messageBus = $this->prophesize(MessageBusInterface::class);
        $this->listener = new WebsiteArticleIndexListener($this->messageBus->reveal());
    }

    public function testOnArticleChangedWithArticleArticleWorkflowTransitionAppliedEvent(): void
    {
        $article = new Article('123');
        $event = new ArticleWorkflowTransitionAppliedEvent($article, DimensionContentInterface::STAGE_LIVE, 'en');

        $expectedConfig = ReindexConfig::create()
            ->withIndex('website')
            ->withIdentifiers([ArticleInterface::RESOURCE_KEY . '::123::en']);

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
            ->withIndex('website')
            ->withIdentifiers([ArticleInterface::RESOURCE_KEY . '::789::en', ArticleInterface::RESOURCE_KEY . '::789::de']);

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
            ->withIndex('website')
            ->withIdentifiers([ArticleInterface::RESOURCE_KEY . '::444::de']);

        $this->messageBus->dispatch($expectedConfig)
            ->willReturn(new Envelope($expectedConfig))
            ->shouldBeCalledOnce();

        $this->listener->onArticleChanged($event);
    }
}
