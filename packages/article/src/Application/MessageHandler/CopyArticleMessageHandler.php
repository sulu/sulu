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

use Sulu\Article\Application\Message\CopyArticleMessage;
use Sulu\Article\Domain\Event\ArticleCopiedEvent;
use Sulu\Article\Domain\Model\ArticleDimensionContent;
use Sulu\Article\Domain\Model\ArticleInterface;
use Sulu\Article\Domain\Repository\ArticleRepositoryInterface;
use Sulu\Bundle\ActivityBundle\Application\Collector\DomainEventCollectorInterface;
use Sulu\Component\Localization\Manager\LocalizationManagerInterface;
use Sulu\Content\Application\ContentCopier\ContentCopierInterface;
use Sulu\Content\Domain\Model\DimensionContentCollection;
use Sulu\Content\Domain\Model\DimensionContentInterface;

/**
 * @internal This class should not be instantiated by a project.
 *           Create your own Message and Handler instead.
 */
final class CopyArticleMessageHandler
{
    public function __construct(
        private ArticleRepositoryInterface $articleRepository,
        private ContentCopierInterface $contentCopier,
        private LocalizationManagerInterface $localizationManager,
        private DomainEventCollectorInterface $domainEventCollector,
    ) {
    }

    public function __invoke(CopyArticleMessage $message): ArticleInterface
    {
        $allLocales = [];
        foreach ($this->localizationManager->getLocalizations() as $localization) {
            $allLocales[] = $localization->getLocale();
        }

        $sourceArticle = $this->articleRepository->getOneBy(
            $message->getSourceIdentifier(),
            [
                ArticleRepositoryInterface::SELECT_ARTICLE_CONTENT => [
                    'selects' => [],
                    'dimensionAttributes' => [
                        'locale' => $allLocales,
                        'stage' => DimensionContentInterface::STAGE_DRAFT,
                    ],
                ],
            ]
        );

        $targetArticle = $this->articleRepository->createNew($message->getTargetUuid());
        $this->articleRepository->add($targetArticle);

        $dimensionContentCollection = new DimensionContentCollection(
            $sourceArticle->getDimensionContents(),
            [],
            ArticleDimensionContent::class
        );

        foreach ($allLocales as $locale) {
            $sourceDimensionContent = $dimensionContentCollection->getDimensionContent([
                'locale' => $locale,
                'stage' => DimensionContentInterface::STAGE_DRAFT,
            ]);

            if ($sourceDimensionContent?->getLocale() !== $locale) {
                // If the article does not exist in the target locale, we cannot copy content for that locale.
                continue;
            }

            $this->contentCopier->copy(
                $sourceArticle,
                [
                    'stage' => DimensionContentInterface::STAGE_DRAFT,
                    'locale' => $locale,
                ],
                $targetArticle,
                [
                    'stage' => DimensionContentInterface::STAGE_DRAFT,
                    'locale' => $locale,
                ],
                [
                    'ignoredAttributes' => [
                        'url', // TODO remove this once the route resolving is implemented on duplicates
                    ],
                ]
            );
        }

        $sourceDimensionContent = $dimensionContentCollection->getDimensionContent([
            'locale' => $message->getLocale(),
            'stage' => DimensionContentInterface::STAGE_DRAFT,
        ]);

        $this->domainEventCollector->collect(new ArticleCopiedEvent(
            $targetArticle,
            (string) $sourceArticle->getUuid(),
            $sourceDimensionContent instanceof ArticleDimensionContent ? $sourceDimensionContent->getTitle() : null,
            $message->getLocale(),
        ));

        return $targetArticle;
    }
}
