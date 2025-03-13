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

use Sulu\Content\Application\ContentWorkflow\ContentWorkflowInterface;
use Sulu\Page\Application\Message\ApplyWorkflowTransitionPageMessage;
use Sulu\Page\Domain\Repository\PageRepositoryInterface;

/**
 * @experimental
 *
 * @internal This class should not be instantiated by a project.
 *           Create your own Message and Handler instead.
 */
final class ApplyWorkflowTransitionPageMessageHandler
{
    /**
     * @var PageRepositoryInterface
     */
    private $pageRepository;

    /**
     * @var ContentWorkflowInterface
     */
    private $contentWorkflow;

    public function __construct(
        PageRepositoryInterface $pageRepository,
        ContentWorkflowInterface $contentWorkflow
    ) {
        $this->pageRepository = $pageRepository;
        $this->contentWorkflow = $contentWorkflow;
    }

    public function __invoke(ApplyWorkflowTransitionPageMessage $message): void
    {
        $page = $this->pageRepository->getOneBy($message->getIdentifier());

        $this->contentWorkflow->apply(
            $page,
            ['locale' => $message->getLocale()],
            $message->getTransitionName()
        );
    }
}
