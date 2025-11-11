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
use Sulu\Bundle\TrashBundle\Application\TrashManager\TrashManagerInterface;
use Sulu\Content\Domain\Model\DimensionContentCollection;
use Sulu\Page\Application\Message\RemovePageMessage;
use Sulu\Page\Domain\Event\PageRemovedEvent;
use Sulu\Page\Domain\Model\PageDimensionContent;
use Sulu\Page\Domain\Model\PageDimensionContentInterface;
use Sulu\Page\Domain\Repository\PageRepositoryInterface;

/**
 * @experimental
 *
 * @internal This class should not be instantiated by a project.
 *           Create your own Message and Handler instead.
 */
final class RemovePageMessageHandler
{
    public function __construct(
        private PageRepositoryInterface $pageRepository,
        private DomainEventCollectorInterface $domainEventCollector,
        private ?TrashManagerInterface $trashManager = null,
    ) {
    }

    public function __invoke(RemovePageMessage $message): void
    {
        $page = $this->pageRepository->getOneBy($message->getIdentifier());

        $this->pageRepository->remove($page);

        /** @var string $resourceKey */
        $resourceKey = $page::RESOURCE_KEY;
        $this->trashManager?->store($resourceKey, $page);

        $dimensionContentCollection = new DimensionContentCollection($page->getDimensionContents()->toArray(), [], PageDimensionContent::class);
        /** @var PageDimensionContentInterface|null $localizedDimensionContent */
        $localizedDimensionContent = $dimensionContentCollection->getDimensionContent(['locale' => $message->getLocale()]);
        $unlocalizedDimensionContent = $dimensionContentCollection->getDimensionContent(['locale' => null, 'stage' => 'draft']);
        $context = $unlocalizedDimensionContent?->getAvailableLocales() ? ['locales' => $unlocalizedDimensionContent->getAvailableLocales()] : [];

        // Try to get title from the removed locale first, fallback to any available locale if null
        $title = $localizedDimensionContent?->getTitle();
        if (null === $title && $unlocalizedDimensionContent) {
            $availableLocales = $unlocalizedDimensionContent->getAvailableLocales();
            foreach ($availableLocales as $availableLocale) {
                $fallbackDimensionContent = $dimensionContentCollection->getDimensionContent(['locale' => $availableLocale]);
                if ($fallbackDimensionContent instanceof PageDimensionContentInterface && null !== $fallbackDimensionContent->getTitle()) {
                    $title = $fallbackDimensionContent->getTitle();
                    break;
                }
            }
        }

        $this->domainEventCollector->collect(new PageRemovedEvent(
            $page->getUuid(),
            $page->getWebspaceKey(),
            $title,
            $context
        ));
    }
}
