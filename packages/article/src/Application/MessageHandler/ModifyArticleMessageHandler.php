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

use Sulu\Article\Application\Mapper\ArticleMapperInterface;
use Sulu\Article\Application\Message\ModifyArticleMessage;
use Sulu\Article\Domain\Event\ArticleModifiedEvent;
use Sulu\Article\Domain\Model\ArticleInterface;
use Sulu\Article\Domain\Repository\ArticleRepositoryInterface;
use Sulu\Bundle\ActivityBundle\Application\Collector\DomainEventCollectorInterface;
use Sulu\Content\Application\ContentHash\ContentHashChecker;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Content\Infrastructure\Doctrine\DimensionContentQueryEnhancer;

/**
 * @internal This class should not be instantiated by a project.
 *           Create a ArticleMapper to extend this Handler.
 */
final class ModifyArticleMessageHandler
{
    public function __construct(
        private ArticleRepositoryInterface $articleRepository,
        /** @var iterable<ArticleMapperInterface> */
        private iterable $articleMappers,
        private DomainEventCollectorInterface $domainEventCollector,
        private ContentHashChecker $contentHashChecker,
    ) {
    }

    public function __invoke(ModifyArticleMessage $message): ArticleInterface
    {
        $identifier = $message->getIdentifier();
        $data = $message->getData();
        /** @var string $locale */
        $locale = $data['locale'];

        $article = $this->articleRepository->getOneBy(
            [
                ...$identifier,
                'locale' => $locale,
            ],
            [
                ArticleRepositoryInterface::SELECT_ARTICLE_CONTENT => [
                    'selects' => [DimensionContentQueryEnhancer::GROUP_SELECT_CONTENT_ADMIN => true],
                    'dimensionAttributes' => [
                        'locale' => $locale,
                        'stage' => [DimensionContentInterface::STAGE_DRAFT, DimensionContentInterface::STAGE_LIVE],
                    ],
                ],
            ]
        );

        $this->contentHashChecker->checkHash(
            $data,
            $article,
            ['locale' => $locale, 'stage' => DimensionContentInterface::STAGE_DRAFT],
            $article->getId(),
        );

        foreach ($this->articleMappers as $articleMapper) {
            $articleMapper->mapArticleData($article, $data);
        }

        $this->domainEventCollector->collect(new ArticleModifiedEvent($article, $locale, $data));

        return $article;
    }
}
