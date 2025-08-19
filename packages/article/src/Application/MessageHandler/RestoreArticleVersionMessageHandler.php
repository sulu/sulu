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

use Sulu\Article\Application\Message\RestoreArticleVersionMessage;
use Sulu\Article\Domain\Event\ArticleVersionRestoredEvent;
use Sulu\Article\Domain\Model\ArticleInterface;
use Sulu\Article\Domain\Repository\ArticleRepositoryInterface;
use Sulu\Bundle\ActivityBundle\Application\Collector\DomainEventCollectorInterface;
use Sulu\Content\Application\ContentCopier\ContentCopierInterface;
use Sulu\Content\Domain\Model\DimensionContentInterface;

/**
 * @experimental
 *
 * @internal This class should not be instantiated by a project.
 *           Create your own Message and Handler instead.
 */
class RestoreArticleVersionMessageHandler
{
    public function __construct(
        private ArticleRepositoryInterface $articleRepository,
        private ContentCopierInterface $contentCopier,
        private DomainEventCollectorInterface $domainEventCollector,
    ) {
    }

    public function __invoke(RestoreArticleVersionMessage $message): ArticleInterface
    {
        $article = $this->articleRepository->getOneBy($message->getArticleIdentifier());
        $options = $message->getOptions();
        $locale = $message->getLocale();

        $dimensionContent = $this->contentCopier->copy(
            $article,
            [
                'stage' => $options['stage'] ?? DimensionContentInterface::STAGE_DRAFT,
                'locale' => $locale,
                'version' => $message->getVersion(),
            ],
            $article,
            [
                'stage' => $options['stage'] ?? DimensionContentInterface::STAGE_DRAFT,
                'locale' => $locale,
                'version' => DimensionContentInterface::CURRENT_VERSION,
            ],
            [
                'ignoredAttributes' => ['url'],
            ]
        );

        $this->domainEventCollector->collect(new ArticleVersionRestoredEvent($article, $locale, $message->getVersion()));

        return $dimensionContent->getResource();
    }
}
