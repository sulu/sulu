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
use Sulu\Content\Domain\Model\DimensionContentCollection;
use Sulu\Snippet\Application\Message\RemoveSnippetMessage;
use Sulu\Snippet\Domain\Event\SnippetRemovedEvent;
use Sulu\Snippet\Domain\Model\SnippetDimensionContent;
use Sulu\Snippet\Domain\Repository\SnippetRepositoryInterface;

/**
 * @experimental
 *
 * @internal This class should not be instantiated by a project.
 *           Create your own Message and Handler instead.
 */
final class RemoveSnippetMessageHandler
{
    public function __construct(
        private SnippetRepositoryInterface $snippetRepository,
        private DomainEventCollectorInterface $domainEventCollector,
    ) {
    }

    public function __invoke(RemoveSnippetMessage $message): void
    {
        $snippet = $this->snippetRepository->getOneBy($message->getIdentifier());

        $this->snippetRepository->remove($snippet);

        $dimensionContentCollection = new DimensionContentCollection($snippet->getDimensionContents()->toArray(), [], SnippetDimensionContent::class);
        /** @var SnippetDimensionContent $localizedDimensionContent */
        $localizedDimensionContent = $dimensionContentCollection->getDimensionContent(['locale' => $message->getLocale()]);

        $this->domainEventCollector->collect(new SnippetRemovedEvent($snippet->getId(), $localizedDimensionContent->getTitle(), []));
    }
}
