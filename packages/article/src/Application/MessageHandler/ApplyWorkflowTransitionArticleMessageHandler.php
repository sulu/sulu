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

use Doctrine\ORM\EntityManagerInterface;
use Sulu\Article\Application\Message\ApplyWorkflowTransitionArticleMessage;
use Sulu\Article\Domain\Event\ArticleWorkflowTransitionAppliedEvent;
use Sulu\Article\Domain\Model\ArticleInterface;
use Sulu\Article\Domain\Repository\ArticleRepositoryInterface;
use Sulu\Bundle\ActivityBundle\Application\Collector\DomainEventCollectorInterface;
use Sulu\Content\Application\ContentWorkflow\ContentWorkflowInterface;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Content\Infrastructure\Doctrine\DimensionContentQueryEnhancer;

/**
 * @internal This class should not be instantiated by a project.
 *           Create your own Message and Handler instead.
 */
final class ApplyWorkflowTransitionArticleMessageHandler
{
    public function __construct(
        private ArticleRepositoryInterface $articleRepository,
        private ContentWorkflowInterface $contentWorkflow,
        private EntityManagerInterface $entityManager,
        private DomainEventCollectorInterface $domainEventCollector,
    ) {
    }

    public function __invoke(ApplyWorkflowTransitionArticleMessage $message): ArticleInterface
    {
        $locale = $message->getLocale();

        $article = $this->loadArticle($message, [$locale]);

        // The shadow source and dependent locales are only known once this locale is loaded.
        $relatedLocales = $this->resolveRelatedLocales($article, $locale);
        if ([] !== $relatedLocales) {
            // Drop the identity-map collection (filled by a preceding ModifyArticleMessage) so the
            // wider query re-hydrates it with all locales.
            $this->entityManager->refresh($article);

            $article = $this->loadArticle($message, [$locale, ...$relatedLocales]);
        }

        $this->contentWorkflow->apply(
            $article,
            ['locale' => $locale],
            $message->getTransitionName()
        );

        $this->domainEventCollector->collect(new ArticleWorkflowTransitionAppliedEvent($article, $message->getTransitionName(), $locale));

        return $article;
    }

    /**
     * @param string[] $locales
     */
    private function loadArticle(ApplyWorkflowTransitionArticleMessage $message, array $locales): ArticleInterface
    {
        return $this->articleRepository->getOneBy(
            $message->getIdentifier(),
            [
                ArticleRepositoryInterface::SELECT_ARTICLE_CONTENT => [
                    'selects' => [DimensionContentQueryEnhancer::GROUP_SELECT_CONTENT_ADMIN => true],
                    'dimensionAttributes' => [
                        'locale' => $locales,
                        'stage' => [DimensionContentInterface::STAGE_DRAFT, DimensionContentInterface::STAGE_LIVE],
                    ],
                ],
            ]
        );
    }

    /**
     * Resolve the shadow source and dependent locales so the caller can re-load the article
     * with them hydrated before the publish subscriber aggregates the source locale.
     *
     * @return string[]
     */
    private function resolveRelatedLocales(ArticleInterface $article, string $locale): array
    {
        foreach ($article->getDimensionContents() as $dimensionContent) {
            if (DimensionContentInterface::STAGE_DRAFT !== $dimensionContent->getStage()
                || null !== $dimensionContent->getLocale()
            ) {
                continue;
            }

            $relatedLocales = $dimensionContent->getShadowLocalesForLocale($locale);
            $sourceLocale = ($dimensionContent->getShadowLocales() ?? [])[$locale] ?? null;
            if (null !== $sourceLocale) {
                $relatedLocales[] = $sourceLocale;
            }

            return \array_values(\array_unique($relatedLocales));
        }

        return [];
    }
}
