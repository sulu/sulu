<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Page\Application\MessageHandler;

use Doctrine\ORM\EntityManagerInterface;
use Sulu\Bundle\ActivityBundle\Application\Collector\DomainEventCollectorInterface;
use Sulu\Content\Application\ContentWorkflow\ContentWorkflowInterface;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Content\Domain\Model\ShadowInterface;
use Sulu\Content\Infrastructure\Doctrine\DimensionContentQueryEnhancer;
use Sulu\Page\Application\Message\ApplyWorkflowTransitionPageMessage;
use Sulu\Page\Domain\Event\PageWorkflowTransitionAppliedEvent;
use Sulu\Page\Domain\Model\PageInterface;
use Sulu\Page\Domain\Repository\PageRepositoryInterface;

/**
 * @internal This class should not be instantiated by a project.
 *           Create your own Message and Handler instead.
 */
final class ApplyWorkflowTransitionPageMessageHandler
{
    public function __construct(
        private PageRepositoryInterface $pageRepository,
        private ContentWorkflowInterface $contentWorkflow,
        private EntityManagerInterface $entityManager,
        private DomainEventCollectorInterface $domainEventCollector,
    ) {
    }

    public function __invoke(ApplyWorkflowTransitionPageMessage $message): PageInterface
    {
        $locale = $message->getLocale();

        $page = $this->loadPage($message, [$locale]);

        // The workflow also touches the published locale's shadow source and the locales shadowing
        // it; those are only known once this locale is loaded.
        $relatedLocales = $this->resolveRelatedLocales($page, $locale);
        if ([] !== $relatedLocales) {
            // reset the single-locale collection (a preceding ModifyPageMessage initialized it) so
            // the wider query re-hydrates it instead of reusing the identity-map one
            $this->entityManager->refresh($page);

            $page = $this->loadPage($message, [$locale, ...$relatedLocales]);
        }

        $this->contentWorkflow->apply(
            $page,
            ['locale' => $locale],
            $message->getTransitionName()
        );

        $this->domainEventCollector->collect(new PageWorkflowTransitionAppliedEvent($page, $message->getTransitionName(), $message->getLocale()));

        return $page;
    }

    /**
     * @param string[] $locales
     */
    private function loadPage(ApplyWorkflowTransitionPageMessage $message, array $locales): PageInterface
    {
        return $this->pageRepository->getOneBy(
            $message->getIdentifier(),
            [
                PageRepositoryInterface::SELECT_PAGE_CONTENT => [
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
    private function resolveRelatedLocales(PageInterface $page, string $locale): array
    {
        foreach ($page->getDimensionContents() as $dimensionContent) {
            if (DimensionContentInterface::STAGE_DRAFT !== $dimensionContent->getStage()
                || null !== $dimensionContent->getLocale()
                || !$dimensionContent instanceof ShadowInterface
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
