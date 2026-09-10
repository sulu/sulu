<?php

declare(strict_types=1);

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Content\Domain\Exception;

use Sulu\Component\Rest\Exception\TranslationErrorMessageExceptionInterface;
use Sulu\Content\Domain\Model\WorkflowTransitionRequest\WorkflowTransitionRequest;

class WorkflowTransitionRequestClosedException extends \RuntimeException implements TranslationErrorMessageExceptionInterface
{
    private readonly string $workflowTransitionRequestId;

    private readonly string $status;

    public function __construct(WorkflowTransitionRequest $workflowTransitionRequest)
    {
        $this->workflowTransitionRequestId = $workflowTransitionRequest->getId();
        $this->status = $workflowTransitionRequest->getStatus()->value;

        parent::__construct(\sprintf(
            'Workflow transition request "%s" cannot accept decisions in status "%s".',
            $this->workflowTransitionRequestId,
            $this->status,
        ));
    }

    public function getMessageTranslationKey(): string
    {
        return 'sulu_content.workflow_transition_request.closed';
    }

    public function getMessageTranslationParameters(): array
    {
        return [
            '{workflowTransitionRequestId}' => $this->workflowTransitionRequestId,
            '{status}' => $this->status,
        ];
    }
}
