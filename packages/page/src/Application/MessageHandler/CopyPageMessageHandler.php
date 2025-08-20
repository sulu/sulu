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
use Sulu\Page\Application\Message\CopyPageMessage;
use Sulu\Page\Domain\Event\PageCopiedEvent;
use Sulu\Page\Domain\Model\PageDimensionContent;
use Sulu\Page\Domain\Model\PageInterface;
use Sulu\Page\Domain\Repository\PageRepositoryInterface;

/**
 * @experimental
 *
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
        $sourcePage = $this->pageRepository->getOneBy($message->getSourceIdentifier());
        $targetParentPage = $this->pageRepository->getOneBy($message->getTargetParentIdentifier());

        $targetPage = $this->pageRepository->createNew($message->getTargetUuid());
        $targetPage->setWebspaceKey($targetParentPage->getWebspaceKey());
        $targetPage->setParent($targetParentPage);
        $this->pageRepository->add($targetPage);

        // TODO we should not depend on the LocalizationManager here, but rather use the `availableLocales` from the sourceDimensionContent itself.
        foreach ($this->localizationManager->getLocalizations() as $localization) {
            $exists = $this->pageRepository->countBy([...$message->getSourceIdentifier(), 'locale' => $localization->getLocale(), 'stage' => DimensionContentInterface::STAGE_DRAFT]);
            if (!$exists) {
                // If the page does not exist in the target locale, we cannot copy content for that locale.
                continue;
            }

            $this->contentCopier->copy(
                $sourcePage,
                [
                    'stage' => DimensionContentInterface::STAGE_DRAFT,
                    'locale' => $localization->getLocale(),
                ],
                $targetPage,
                [
                    'stage' => DimensionContentInterface::STAGE_DRAFT,
                    'locale' => $localization->getLocale(),
                ],
                [
                    'ignoredAttributes' => [
                        'url', // TODO remove this once the route resolving is implemented on duplicates
                    ],
                ]
            );
        }

        $dimensionContentCollection = new DimensionContentCollection($sourcePage->getDimensionContents()->toArray(), [], PageDimensionContent::class);
        /** @var PageDimensionContent $localizedDimensionContent */
        $localizedDimensionContent = $dimensionContentCollection->getDimensionContent(['locale' => $message->getLocale()]);

        $this->domainEventCollector->collect(new PageCopiedEvent($sourcePage, $sourcePage->getId(), $sourcePage->getWebspaceKey(), $localizedDimensionContent->getTitle(), $message->getLocale()));

        return $targetPage;
    }
}
