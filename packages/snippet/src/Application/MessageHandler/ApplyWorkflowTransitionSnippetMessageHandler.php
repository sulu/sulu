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
use Sulu\Content\Application\ContentWorkflow\ContentWorkflowInterface;
use Sulu\Snippet\Application\Message\ApplyWorkflowTransitionSnippetMessage;
use Sulu\Snippet\Domain\Event\SnippetWorkflowTransitionAppliedEvent;
use Sulu\Snippet\Domain\Repository\SnippetRepositoryInterface;

/**
 * @experimental
 *
 * @internal This class should not be instantiated by a project.
 *           Create your own Message and Handler instead.
 */
final class ApplyWorkflowTransitionSnippetMessageHandler
{
    public function __construct(
        private SnippetRepositoryInterface $snippetRepository,
        private ContentWorkflowInterface $contentWorkflow,
        private DomainEventCollectorInterface $domainEventCollector
    ) {
    }

    public function __invoke(ApplyWorkflowTransitionSnippetMessage $message): void
    {
        $snippet = $this->snippetRepository->getOneBy($message->getIdentifier());

        $this->contentWorkflow->apply(
            $snippet,
            ['locale' => $message->getLocale()],
            $message->getTransitionName()
        );

        $this->domainEventCollector->collect(new SnippetWorkflowTransitionAppliedEvent($snippet, $message->getTransitionName(), $message->getLocale()));
    }
}
