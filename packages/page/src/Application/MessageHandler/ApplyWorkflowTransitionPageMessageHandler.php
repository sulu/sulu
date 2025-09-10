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
use Sulu\Content\Application\ContentWorkflow\ContentWorkflowInterface;
use Sulu\Page\Application\Message\ApplyWorkflowTransitionPageMessage;
use Sulu\Page\Domain\Event\PageWorkflowTransitionAppliedEvent;
use Sulu\Page\Domain\Repository\PageRepositoryInterface;

/**
 * @experimental
 *
 * @internal This class should not be instantiated by a project.
 *           Create your own Message and Handler instead.
 */
final class ApplyWorkflowTransitionPageMessageHandler
{
    public function __construct(
        private PageRepositoryInterface $pageRepository,
        private ContentWorkflowInterface $contentWorkflow,
        private DomainEventCollectorInterface $domainEventCollector,
    ) {
    }

    public function __invoke(ApplyWorkflowTransitionPageMessage $message): void
    {
        $page = $this->pageRepository->getOneBy($message->getIdentifier());

        $this->contentWorkflow->apply(
            $page,
            ['locale' => $message->getLocale()],
            $message->getTransitionName()
        );

        $this->domainEventCollector->collect(new PageWorkflowTransitionAppliedEvent($page, $message->getTransitionName(), $message->getLocale()));
    }
}
