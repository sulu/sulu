<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Snippet\Application\MessageHandler;

use Doctrine\ORM\EntityManagerInterface;
use Sulu\Bundle\ActivityBundle\Application\Collector\DomainEventCollectorInterface;
use Sulu\Content\Application\ContentWorkflow\ContentWorkflowInterface;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Content\Infrastructure\Doctrine\DimensionContentQueryEnhancer;
use Sulu\Snippet\Application\Message\ApplyWorkflowTransitionSnippetMessage;
use Sulu\Snippet\Domain\Event\SnippetWorkflowTransitionAppliedEvent;
use Sulu\Snippet\Domain\Model\SnippetInterface;
use Sulu\Snippet\Domain\Repository\SnippetRepositoryInterface;

/**
 * @internal This class should not be instantiated by a project.
 *           Create your own Message and Handler instead.
 */
final class ApplyWorkflowTransitionSnippetMessageHandler
{
    public function __construct(
        private SnippetRepositoryInterface $snippetRepository,
        private ContentWorkflowInterface $contentWorkflow,
        private EntityManagerInterface $entityManager,
        private DomainEventCollectorInterface $domainEventCollector
    ) {
    }

    public function __invoke(ApplyWorkflowTransitionSnippetMessage $message): SnippetInterface
    {
        $locale = $message->getLocale();

        $snippet = $this->loadSnippet($message, [$locale]);

        // The shadow source and dependent locales are only known once this locale is loaded.
        $relatedLocales = $this->resolveRelatedLocales($snippet, $locale);
        if ([] !== $relatedLocales) {
            // Drop the identity-map collection (filled by a preceding ModifySnippetMessage) so the
            // wider query re-hydrates it with all locales.
            $this->entityManager->refresh($snippet);

            $snippet = $this->loadSnippet($message, [$locale, ...$relatedLocales]);
        }

        $this->contentWorkflow->apply(
            $snippet,
            ['locale' => $locale],
            $message->getTransitionName()
        );

        $this->domainEventCollector->collect(new SnippetWorkflowTransitionAppliedEvent($snippet, $message->getTransitionName(), $locale));

        return $snippet;
    }

    /**
     * @param string[] $locales
     */
    private function loadSnippet(ApplyWorkflowTransitionSnippetMessage $message, array $locales): SnippetInterface
    {
        return $this->snippetRepository->getOneBy(
            $message->getIdentifier(),
            [
                SnippetRepositoryInterface::SELECT_SNIPPET_CONTENT => [
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
     * @return string[]
     */
    private function resolveRelatedLocales(SnippetInterface $snippet, string $locale): array
    {
        foreach ($snippet->getDimensionContents() as $dimensionContent) {
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
