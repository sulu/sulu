<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Article\Application\MessageHandler;

use Sulu\Article\Application\Message\RemoveArticleMessage;
use Sulu\Article\Domain\Event\ArticleRemovedEvent;
use Sulu\Article\Domain\Model\ArticleDimensionContent;
use Sulu\Article\Domain\Repository\ArticleRepositoryInterface;
use Sulu\Bundle\ActivityBundle\Application\Collector\DomainEventCollectorInterface;
use Sulu\Content\Domain\Model\DimensionContentCollection;

/**
 * @experimental
 *
 * @internal This class should not be instantiated by a project.
 *           Create your own Message and Handler instead.
 */
final class RemoveArticleMessageHandler
{
    public function __construct(
        private ArticleRepositoryInterface $articleRepository,
        private DomainEventCollectorInterface $domainEventCollector,
    ) {
    }

    public function __invoke(RemoveArticleMessage $message): void
    {
        $article = $this->articleRepository->getOneBy($message->getIdentifier());

        $this->articleRepository->remove($article);

        $dimensionContentCollection = new DimensionContentCollection($article->getDimensionContents()->toArray(), [], ArticleDimensionContent::class);
        $localizedDimensionContent = $dimensionContentCollection->getDimensionContent(['locale' => $message->getLocale()]);

        $this->domainEventCollector->collect(new ArticleRemovedEvent($article->getId(), $localizedDimensionContent?->getTitle(), []));
    }
}
