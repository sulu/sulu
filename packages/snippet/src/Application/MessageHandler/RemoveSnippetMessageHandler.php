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

use Sulu\Bundle\ActivityBundle\Application\Collector\DomainEventCollectorInterface;
use Sulu\Bundle\TrashBundle\Application\TrashManager\TrashManagerInterface;
use Sulu\Content\Domain\Model\DimensionContentCollection;
use Sulu\Snippet\Application\Message\RemoveSnippetMessage;
use Sulu\Snippet\Domain\Event\SnippetRemovedEvent;
use Sulu\Snippet\Domain\Model\SnippetDimensionContent;
use Sulu\Snippet\Domain\Model\SnippetDimensionContentInterface;
use Sulu\Snippet\Domain\Repository\SnippetRepositoryInterface;

/**
 * @internal This class should not be instantiated by a project.
 *           Create your own Message and Handler instead.
 */
final class RemoveSnippetMessageHandler
{
    public function __construct(
        private SnippetRepositoryInterface $snippetRepository,
        private DomainEventCollectorInterface $domainEventCollector,
        private ?TrashManagerInterface $trashManager = null,
    ) {
    }

    public function __invoke(RemoveSnippetMessage $message): void
    {
        $snippet = $this->snippetRepository->getOneBy($message->getIdentifier());

        $this->snippetRepository->remove($snippet);

        /** @var string $resourceKey */
        $resourceKey = $snippet::RESOURCE_KEY;
        $this->trashManager?->store($resourceKey, $snippet);

        $dimensionContentCollection = new DimensionContentCollection($snippet->getDimensionContents(), [], SnippetDimensionContent::class);
        /** @var SnippetDimensionContentInterface|null $localizedDimensionContent */
        $localizedDimensionContent = $dimensionContentCollection->getDimensionContent(['locale' => $message->getLocale()]);
        $unlocalizedDimensionContent = $dimensionContentCollection->getDimensionContent(['locale' => null, 'stage' => 'draft']);
        $context = $unlocalizedDimensionContent?->getAvailableLocales() ? ['locales' => $unlocalizedDimensionContent->getAvailableLocales()] : [];

        // Try to get title from the removed locale first, fallback to any available locale if null
        $title = $localizedDimensionContent?->getTitle();
        if (null === $title && $unlocalizedDimensionContent) {
            $availableLocales = $unlocalizedDimensionContent->getAvailableLocales() ?? [];
            foreach ($availableLocales as $availableLocale) {
                $fallbackDimensionContent = $dimensionContentCollection->getDimensionContent(['locale' => $availableLocale]);
                if ($fallbackDimensionContent instanceof SnippetDimensionContentInterface && null !== $fallbackDimensionContent->getTitle()) {
                    $title = $fallbackDimensionContent->getTitle();
                    break;
                }
            }
        }

        $this->domainEventCollector->collect(new SnippetRemovedEvent($snippet->getId(), $title, $context));
    }
}
