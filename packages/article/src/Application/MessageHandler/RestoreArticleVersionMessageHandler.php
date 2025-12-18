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
use Sulu\Content\Infrastructure\Doctrine\DimensionContentQueryEnhancer;

/**
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
        $options = $message->getOptions();
        $stage = $options['stage'] ?? DimensionContentInterface::STAGE_DRAFT;

        $article = $this->articleRepository->getOneBy(
            $message->getArticleIdentifier(),
            [
                ArticleRepositoryInterface::SELECT_ARTICLE_CONTENT => [
                    'selects' => [DimensionContentQueryEnhancer::GROUP_SELECT_CONTENT_WEBSITE => true],
                    'dimensionAttributes' => [
                        'locale' => $message->getLocale(),
                        'stage' => $stage,
                        'version' => [$message->getVersion(), DimensionContentInterface::CURRENT_VERSION],
                    ],
                ],
            ]
        );

        $dimensionContent = $this->contentCopier->copy(
            $article,
            [
                'stage' => $stage,
                'locale' => $message->getLocale(),
                'version' => $message->getVersion(),
            ],
            $article,
            [
                'stage' => $stage,
                'locale' => $message->getLocale(),
                'version' => DimensionContentInterface::CURRENT_VERSION,
            ],
            [
                'ignoredAttributes' => ['url'],
            ]
        );

        $this->domainEventCollector->collect(new ArticleVersionRestoredEvent($article, $message->getLocale(), $message->getVersion()));

        return $dimensionContent->getResource();
    }
}
