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
use Sulu\Content\Domain\Model\DimensionContentCollection;
use Sulu\Page\Application\Message\MovePageMessage;
use Sulu\Page\Domain\Event\PageMovedEvent;
use Sulu\Page\Domain\Model\PageDimensionContent;
use Sulu\Page\Domain\Repository\PageRepositoryInterface;

/**
 * @experimental
 *
 * @internal This class should not be instantiated by a project.
 *           Create a PageMapper to extend this Handler.
 */
class MovePageMessageHandler
{
    public function __construct(
        private PageRepositoryInterface $pageRepository,
        private DomainEventCollectorInterface $domainEventCollector,
    ) {
    }

    public function __invoke(MovePageMessage $message): void
    {
        $page = $this->pageRepository->getOneBy($message->getIdentifier());
        $previousParent = $page->getParent();

        $this->pageRepository->moveOneBy($message->getIdentifier(), $message->getTargetParentIdentifier());

        if (null === $previousParent) {
            $this->domainEventCollector->collect(new PageMovedEvent(
                $page,
                $message->getLocale(),
                null,
                null,
                null,
            ));

            return;
        }

        $previousParentDimensionContentCollection = new DimensionContentCollection($previousParent->getDimensionContents()->toArray(), [], PageDimensionContent::class);
        /** @var PageDimensionContent $previousParentLocalizedDimensionContent */
        $previousParentLocalizedDimensionContent = $previousParentDimensionContentCollection->getDimensionContent(['locale' => $message->getLocale()]);

        $this->domainEventCollector->collect(new PageMovedEvent(
            $page,
            $message->getLocale(),
            $previousParent->getUuid(),
            $previousParent->getWebspaceKey(),
            $previousParentLocalizedDimensionContent->getTitle(),
        ));
    }
}
