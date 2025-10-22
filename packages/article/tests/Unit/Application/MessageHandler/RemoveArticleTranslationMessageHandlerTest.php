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

use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Sulu\Article\Application\Message\RemoveArticleTranslationMessage;
use Sulu\Article\Application\MessageHandler\RemoveArticleTranslationMessageHandler;
use Sulu\Article\Domain\Event\ArticleTranslationRemovedEvent;
use Sulu\Article\Domain\Model\ArticleDimensionContent;
use Sulu\Article\Domain\Model\ArticleInterface;
use Sulu\Article\Domain\Repository\ArticleRepositoryInterface;
use Sulu\Bundle\ActivityBundle\Application\Collector\DomainEventCollectorInterface;

class RemoveArticleTranslationMessageHandlerTest extends TestCase
{
    use ProphecyTrait;

    private $articleRepository;
    private $domainEventCollector;
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

    public function testInvokeRemovesMatchingLocale(): void
    {
        $identifier = ['uuid' => 'article-123'];
        $locale = 'en';
        $message = new RemoveArticleTranslationMessage($identifier, $locale);

        $article = $this->prophesize(ArticleInterface::class);
        $dimensionContent1 = $this->prophesize(ArticleDimensionContent::class);
        $dimensionContent1->getLocale()->willReturn('en');
        $dimensionContent1->getGhostLocale()->willReturn(null);

        $dimensionContent2 = $this->prophesize(ArticleDimensionContent::class);
        $dimensionContent2->getLocale()->willReturn('de');
        $dimensionContent2->getGhostLocale()->willReturn(null);

        $dimensionContents = new ArrayCollection([
            $dimensionContent1->reveal(),
            $dimensionContent2->reveal(),
        ]);

        $this->articleRepository->getOneBy($identifier)->willReturn($article->reveal());
        $article->getDimensionContents()->willReturn($dimensionContents);

        $article->removeDimensionContent($dimensionContent1->reveal())->shouldBeCalled();
        $this->articleRepository->removeDimensionContent($dimensionContent1->reveal())->shouldBeCalled();

        $article->removeDimensionContent($dimensionContent2->reveal())->shouldNotBeCalled();
        $this->articleRepository->removeDimensionContent($dimensionContent2->reveal())->shouldNotBeCalled();

        $this->domainEventCollector->collect(
            Argument::type(ArticleTranslationRemovedEvent::class)
        )->shouldBeCalled();

        ($this->handler)($message);
    }

    public function testInvokeRemovesGhostLocale(): void
    {
        $identifier = ['uuid' => 'article-123'];
        $locale = 'en';
        $message = new RemoveArticleTranslationMessage($identifier, $locale);

        $article = $this->prophesize(ArticleInterface::class);
        $dimensionContent1 = $this->prophesize(ArticleDimensionContent::class);
        $dimensionContent1->getLocale()->willReturn(null);
        $dimensionContent1->getGhostLocale()->willReturn('en');

        $dimensionContents = new ArrayCollection([$dimensionContent1->reveal()]);

        $this->articleRepository->getOneBy($identifier)->willReturn($article->reveal());
        $article->getDimensionContents()->willReturn($dimensionContents);

        $article->removeDimensionContent($dimensionContent1->reveal())->shouldNotBeCalled();
        $this->articleRepository->removeDimensionContent($dimensionContent1->reveal())->shouldNotBeCalled();

        $dimensionContent1->getAvailableLocales()->willReturn(['en', 'de']);
        $dimensionContent1->setGhostLocale('de')->shouldBeCalled();
        $dimensionContent1->removeAvailableLocale('en')->shouldBeCalled();

        $this->domainEventCollector->collect(
            Argument::type(ArticleTranslationRemovedEvent::class)
        )->shouldBeCalled();

        ($this->handler)($message);
    }

    public function testInvokeRemovesDimensionContentWhenNoAvailableLocalesLeft(): void
    {
        $identifier = ['uuid' => 'article-123'];
        $locale = 'en';
        $message = new RemoveArticleTranslationMessage($identifier, $locale);

        $article = $this->prophesize(ArticleInterface::class);
        $dimensionContent1 = $this->prophesize(ArticleDimensionContent::class);
        $dimensionContent1->getLocale()->willReturn(null);
        $dimensionContent1->getGhostLocale()->willReturn('en');
        $dimensionContent1->getAvailableLocales()->willReturn(['en']);

        $dimensionContents = new ArrayCollection([$dimensionContent1->reveal()]);

        $this->articleRepository->getOneBy($identifier)->willReturn($article->reveal());
        $article->getDimensionContents()->willReturn($dimensionContents);

        $this->articleRepository->removeDimensionContent($dimensionContent1->reveal())->shouldBeCalled();

        $dimensionContent1->setGhostLocale(Argument::any())->shouldNotBeCalled();
        $dimensionContent1->removeAvailableLocale(Argument::any())->shouldNotBeCalled();

        $this->domainEventCollector->collect(
            Argument::type(ArticleTranslationRemovedEvent::class)
        )->shouldBeCalled();

        ($this->handler)($message);
    }

    public function testInvokeHandlesMultipleDimensionContents(): void
    {
        $identifier = ['uuid' => 'article-123'];
        $locale = 'en';
        $message = new RemoveArticleTranslationMessage($identifier, $locale);

        $article = $this->prophesize(ArticleInterface::class);

        // Dimension content with matching locale
        $dimensionContent1 = $this->prophesize(ArticleDimensionContent::class);
        $dimensionContent1->getLocale()->willReturn('en');
        $dimensionContent1->getGhostLocale()->willReturn(null);

        // Dimension content with ghost locale
        $dimensionContent2 = $this->prophesize(ArticleDimensionContent::class);
        $dimensionContent2->getLocale()->willReturn(null);
        $dimensionContent2->getGhostLocale()->willReturn('en');
        $dimensionContent2->getAvailableLocales()->willReturn(['en', 'de', 'fr']);

        // Dimension content that should not be affected
        $dimensionContent3 = $this->prophesize(ArticleDimensionContent::class);
        $dimensionContent3->getLocale()->willReturn('de');
        $dimensionContent3->getGhostLocale()->willReturn(null);

        $dimensionContents = new ArrayCollection([
            $dimensionContent1->reveal(),
            $dimensionContent2->reveal(),
            $dimensionContent3->reveal(),
        ]);

        $this->articleRepository->getOneBy($identifier)->willReturn($article->reveal());
        $article->getDimensionContents()->willReturn($dimensionContents);

        // First dimension content should be removed
        $article->removeDimensionContent($dimensionContent1->reveal())->shouldBeCalled();
        $this->articleRepository->removeDimensionContent($dimensionContent1->reveal())->shouldBeCalled();

        // Second dimension content should have ghost locale updated
        $dimensionContent2->setGhostLocale('de')->shouldBeCalled();
        $dimensionContent2->removeAvailableLocale('en')->shouldBeCalled();

        // Third dimension content should not be affected
        $article->removeDimensionContent($dimensionContent3->reveal())->shouldNotBeCalled();
        $this->articleRepository->removeDimensionContent($dimensionContent3->reveal())->shouldNotBeCalled();

        $this->domainEventCollector->collect(
            Argument::type(ArticleTranslationRemovedEvent::class)
        )->shouldBeCalled();

        ($this->handler)($message);
    }

    public function testInvokeCollectsEvent(): void
    {
        $identifier = ['uuid' => 'article-123'];
        $locale = 'en';
        $message = new RemoveArticleTranslationMessage($identifier, $locale);

        $article = $this->prophesize(ArticleInterface::class);
        $this->articleRepository->getOneBy($identifier)->willReturn($article->reveal());
        $article->getDimensionContents()->willReturn(new ArrayCollection([]));

        $this->domainEventCollector->collect(Argument::that(function ($event) use ($article, $locale) {
            return $event instanceof ArticleTranslationRemovedEvent
                && $event->getArticle() === $article->reveal()
                && $event->getResourceLocale() === $locale;
        }))->shouldBeCalled();

        ($this->handler)($message);
    }
}
