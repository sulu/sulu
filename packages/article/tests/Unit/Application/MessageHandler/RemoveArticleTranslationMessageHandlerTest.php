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

namespace Sulu\Article\Tests\Unit\Application\MessageHandler;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Article\Application\Message\RemoveArticleTranslationMessage;
use Sulu\Article\Application\MessageHandler\RemoveArticleTranslationMessageHandler;
use Sulu\Article\Domain\Event\ArticleTranslationRemovedEvent;
use Sulu\Article\Domain\Model\Article;
use Sulu\Article\Domain\Model\ArticleDimensionContent;
use Sulu\Article\Domain\Repository\ArticleRepositoryInterface;
use Sulu\Bundle\ActivityBundle\Application\Collector\DomainEventCollectorInterface;
use Sulu\Content\Domain\Model\DimensionContentInterface;

class RemoveArticleTranslationMessageHandlerTest extends TestCase
{
    use ProphecyTrait;

    /** @var ObjectProphecy<ArticleRepositoryInterface> */
    private ObjectProphecy $articleRepository;
    /** @var ObjectProphecy<DomainEventCollectorInterface> */
    private ObjectProphecy $domainEventCollector;
    private RemoveArticleTranslationMessageHandler $handler;

    protected function setUp(): void
    {
        $this->articleRepository = $this->prophesize(ArticleRepositoryInterface::class);
        $this->domainEventCollector = $this->prophesize(DomainEventCollectorInterface::class);

        $this->handler = new RemoveArticleTranslationMessageHandler(
            $this->articleRepository->reveal(),
            $this->domainEventCollector->reveal()
        );
    }

    public function testRemoveDimensionContentWhenDirectLocaleMatches(): void
    {
        $identifier = ['uuid' => 'article-123'];
        $locale = 'en';
        $message = new RemoveArticleTranslationMessage($identifier, $locale);

        $article = new Article('article-123');
        $dimensionContent = new ArticleDimensionContent($article);
        $dimensionContent->setLocale($locale);
        $dimensionContent->setStage(DimensionContentInterface::STAGE_DRAFT);

        $article->addDimensionContent($dimensionContent);

        $this->articleRepository->getOneBy($identifier)->willReturn($article);
        $this->articleRepository->removeDimensionContent($dimensionContent)->shouldBeCalled();
        $this->domainEventCollector->collect(Argument::type(ArticleTranslationRemovedEvent::class))->shouldBeCalled();

        $this->handler->__invoke($message);

        $this->assertCount(0, $article->getDimensionContents());
    }

    public function testRemoveDimensionContentWhenGhostLocaleMatchesAndNoRemainingLocales(): void
    {
        $identifier = ['uuid' => 'article-123'];
        $locale = 'en';
        $message = new RemoveArticleTranslationMessage($identifier, $locale);

        $article = new Article('article-123');
        $dimensionContent = new ArticleDimensionContent($article);
        $dimensionContent->setLocale(null);
        $dimensionContent->setGhostLocale($locale);
        $dimensionContent->addAvailableLocale('en');
        $dimensionContent->setStage(DimensionContentInterface::STAGE_DRAFT);

        $article->addDimensionContent($dimensionContent);

        $this->articleRepository->getOneBy($identifier)->willReturn($article);
        $this->articleRepository->removeDimensionContent($dimensionContent)->shouldBeCalled();
        $this->domainEventCollector->collect(Argument::type(ArticleTranslationRemovedEvent::class))->shouldBeCalled();

        $this->handler->__invoke($message);

        $this->assertCount(0, $article->getDimensionContents());
    }

    public function testUpdateGhostLocaleWhenGhostLocaleMatchesWithRemainingLocales(): void
    {
        $identifier = ['uuid' => 'article-123'];
        $locale = 'en';
        $message = new RemoveArticleTranslationMessage($identifier, $locale);

        $article = new Article('article-123');
        $dimensionContent = new ArticleDimensionContent($article);
        $dimensionContent->setLocale(null);
        $dimensionContent->setGhostLocale($locale);
        $dimensionContent->addAvailableLocale('en');
        $dimensionContent->addAvailableLocale('de');
        $dimensionContent->addAvailableLocale('fr');
        $dimensionContent->setStage(DimensionContentInterface::STAGE_DRAFT);

        $article->addDimensionContent($dimensionContent);

        $this->articleRepository->getOneBy($identifier)->willReturn($article);
        $this->articleRepository->removeDimensionContent(Argument::any())->shouldNotBeCalled();
        $this->domainEventCollector->collect(Argument::type(ArticleTranslationRemovedEvent::class))->shouldBeCalled();

        $this->handler->__invoke($message);

        $this->assertCount(1, $article->getDimensionContents());
        $this->assertSame('de', $dimensionContent->getGhostLocale());
        $this->assertSame(['de', 'fr'], $dimensionContent->getAvailableLocales());
    }

    public function testHandleNullAvailableLocales(): void
    {
        $identifier = ['uuid' => 'article-123'];
        $locale = 'en';
        $message = new RemoveArticleTranslationMessage($identifier, $locale);

        $article = new Article('article-123');
        $dimensionContent = new ArticleDimensionContent($article);
        $dimensionContent->setLocale(null);
        $dimensionContent->setGhostLocale($locale);
        $dimensionContent->setStage(DimensionContentInterface::STAGE_DRAFT);

        $article->addDimensionContent($dimensionContent);

        $this->articleRepository->getOneBy($identifier)->willReturn($article);
        $this->articleRepository->removeDimensionContent($dimensionContent)->shouldBeCalled();
        $this->domainEventCollector->collect(Argument::type(ArticleTranslationRemovedEvent::class))->shouldBeCalled();

        $this->handler->__invoke($message);

        $this->assertCount(0, $article->getDimensionContents());
    }

    public function testProcessMultipleDimensionContents(): void
    {
        $identifier = ['uuid' => 'article-123'];
        $locale = 'en';
        $message = new RemoveArticleTranslationMessage($identifier, $locale);

        $article = new Article('article-123');

        $dimensionContent1 = new ArticleDimensionContent($article);
        $dimensionContent1->setLocale($locale);
        $dimensionContent1->setStage(DimensionContentInterface::STAGE_DRAFT);
        $article->addDimensionContent($dimensionContent1);

        $dimensionContent2 = new ArticleDimensionContent($article);
        $dimensionContent2->setLocale('de');
        $dimensionContent2->setStage(DimensionContentInterface::STAGE_DRAFT);
        $article->addDimensionContent($dimensionContent2);

        $dimensionContent3 = new ArticleDimensionContent($article);
        $dimensionContent3->setLocale(null);
        $dimensionContent3->setGhostLocale($locale);
        $dimensionContent3->addAvailableLocale('en');
        $dimensionContent3->addAvailableLocale('de');
        $dimensionContent3->setStage(DimensionContentInterface::STAGE_LIVE);
        $article->addDimensionContent($dimensionContent3);

        $this->articleRepository->getOneBy($identifier)->willReturn($article);
        $this->articleRepository->removeDimensionContent($dimensionContent1)->shouldBeCalled();
        $this->articleRepository->removeDimensionContent($dimensionContent2)->shouldNotBeCalled();
        $this->articleRepository->removeDimensionContent($dimensionContent3)->shouldNotBeCalled();
        $this->domainEventCollector->collect(Argument::type(ArticleTranslationRemovedEvent::class))->shouldBeCalled();

        $this->handler->__invoke($message);

        $this->assertCount(2, $article->getDimensionContents());
        $this->assertSame('de', $dimensionContent3->getGhostLocale());
        $this->assertSame(['de'], $dimensionContent3->getAvailableLocales());
    }

    public function testCollectsDomainEvent(): void
    {
        $identifier = ['uuid' => 'article-123'];
        $locale = 'en';
        $message = new RemoveArticleTranslationMessage($identifier, $locale);

        $article = new Article('article-123');
        $dimensionContent = new ArticleDimensionContent($article);
        $dimensionContent->setLocale($locale);
        $dimensionContent->setStage(DimensionContentInterface::STAGE_DRAFT);
        $article->addDimensionContent($dimensionContent);

        $this->articleRepository->getOneBy($identifier)->willReturn($article);
        $this->articleRepository->removeDimensionContent($dimensionContent)->shouldBeCalled();

        $this->domainEventCollector->collect(Argument::that(function(ArticleTranslationRemovedEvent $event) use ($article, $locale) {
            return $event->getArticle() === $article && $event->getResourceLocale() === $locale;
        }))->shouldBeCalled();

        $this->handler->__invoke($message);
    }
}
