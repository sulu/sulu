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

use Sulu\Component\Localization\Manager\LocalizationManagerInterface;
use Sulu\Content\Application\ContentCopier\ContentCopierInterface;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Page\Application\Message\CopyPageMessage;
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
    ) {
    }

    public function __invoke(CopyPageMessage $message): void
    {
        $page = $this->pageRepository->getOneBy($message->getIdentifier());
        $targetParentPage = $this->pageRepository->getOneBy($message->getDestinationIdentifier());

        $targetPage = $this->pageRepository->createNew();
        $targetPage->setWebspaceKey($targetParentPage->getWebspaceKey());
        $targetPage->setParent($targetParentPage);
        $this->pageRepository->add($targetPage);

        foreach ($this->localizationManager->getLocalizations() as $localization) {
            $exists = $this->pageRepository->countBy([...$message->getIdentifier(), 'locale' => $localization->getLocale(), 'stage' => DimensionContentInterface::STAGE_DRAFT]);
            if (!$exists) {
                // If the page does not exist in the target locale, we cannot copy content for that locale.
                continue;
            }

            $this->contentCopier->copy(
                $page,
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
    }
}
