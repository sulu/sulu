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

use Sulu\Bundle\ActivityBundle\Application\Collector\DomainEventCollectorInterface;
use Sulu\Component\Localization\Manager\LocalizationManagerInterface;
use Sulu\Content\Application\ContentCopier\ContentCopierInterface;
use Sulu\Content\Domain\Model\DimensionContentCollection;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Content\Infrastructure\Doctrine\DimensionContentQueryEnhancer;
use Sulu\Page\Application\Message\CopyPageMessage;
use Sulu\Page\Domain\Event\PageCopiedEvent;
use Sulu\Page\Domain\Model\PageDimensionContent;
use Sulu\Page\Domain\Model\PageInterface;
use Sulu\Page\Domain\Repository\PageRepositoryInterface;

/**
 * @internal This class should not be instantiated by a project.
 *           Create your own Message and Handler instead.
 */
final class CopyPageMessageHandler
{
    public function __construct(
        private PageRepositoryInterface $pageRepository,
        private ContentCopierInterface $contentCopier,
        private LocalizationManagerInterface $localizationManager,
        private DomainEventCollectorInterface $domainEventCollector,
    ) {
    }

    public function __invoke(CopyPageMessage $message): PageInterface
    {
        $allLocales = [];
        foreach ($this->localizationManager->getLocalizations() as $localization) {
            $allLocales[] = $localization->getLocale();
        }

        $sourcePage = $this->pageRepository->getOneBy(
            $message->getSourceIdentifier(),
            [
                PageRepositoryInterface::SELECT_PAGE_CONTENT => [
                    'selects' => [DimensionContentQueryEnhancer::GROUP_SELECT_CONTENT_WEBSITE => true],
                    'dimensionAttributes' => [
                        'locale' => $allLocales,
                        'stage' => DimensionContentInterface::STAGE_DRAFT,
                    ],
                ],
            ]
        );
        $targetParentPage = $this->pageRepository->getOneBy(
            $message->getTargetParentIdentifier(),
            [
                PageRepositoryInterface::SELECT_PAGE_CONTENT => [
                    'selects' => [], // No excerpt/tags needed - only used for webspace/parent info
                    'dimensionAttributes' => [
                        'locale' => $message->getLocale(),
                        'stage' => DimensionContentInterface::STAGE_DRAFT,
                    ],
                ],
            ]
        );

        $targetPage = $this->pageRepository->createNew($message->getTargetUuid());
        $targetPage->setWebspaceKey($targetParentPage->getWebspaceKey());
        $targetPage->setParent($targetParentPage);
        $this->pageRepository->add($targetPage);

        $dimensionContentCollection = new DimensionContentCollection(
            $sourcePage->getDimensionContents(),
            [],
            PageDimensionContent::class
        );

        foreach ($allLocales as $locale) {
            $sourceDimensionContent = $dimensionContentCollection->getDimensionContent([
                'locale' => $locale,
                'stage' => DimensionContentInterface::STAGE_DRAFT,
            ]);

            if ($sourceDimensionContent?->getLocale() !== $locale) {
                // If the page does not exist in the target locale, we cannot copy content for that locale.
                continue;
            }

            $this->contentCopier->copy(
                $sourcePage,
                [
                    'stage' => DimensionContentInterface::STAGE_DRAFT,
                    'locale' => $locale,
                ],
                $targetPage,
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

        /** @var PageDimensionContent $localizedDimensionContent */
        $localizedDimensionContent = $dimensionContentCollection->getDimensionContent([
            'locale' => $message->getLocale(),
            'stage' => DimensionContentInterface::STAGE_DRAFT,
        ]);

        $this->domainEventCollector->collect(new PageCopiedEvent($sourcePage, $sourcePage->getId(), $sourcePage->getWebspaceKey(), $localizedDimensionContent->getTitle(), $message->getLocale()));

        return $targetPage;
    }
}
