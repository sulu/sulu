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
use Sulu\Page\Application\Message\OrderPageMessage;
use Sulu\Page\Domain\Event\PageOrderedEvent;
use Sulu\Page\Domain\Repository\PageRepositoryInterface;

/**
 * @experimental
 *
 * @internal This class should not be instantiated by a project.
 *           Create your own Message and Handler instead.
 */
final class OrderPageMessageHandler
{
    public function __construct(
        private PageRepositoryInterface $pageRepository,
        private DomainEventCollectorInterface $domainEventCollector,
    ) {
    }

    public function __invoke(OrderPageMessage $message): void
    {
        $this->pageRepository->reorderOneBy($message->getIdentifier(), $message->getPosition());

        $page = $this->pageRepository->getOneBy($message->getIdentifier());

        $this->domainEventCollector->collect(new PageOrderedEvent($page, $message->getLocale(), $message->getPosition()));
    }
}
