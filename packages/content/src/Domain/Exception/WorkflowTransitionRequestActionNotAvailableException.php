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

class WorkflowTransitionRequestActionNotAvailableException extends \RuntimeException implements TranslationErrorMessageExceptionInterface
{
    private readonly string $workflowTransitionRequestId;

    private readonly string $actionKey;

    public function __construct(WorkflowTransitionRequest $workflowTransitionRequest, string $actionKey)
    {
        $this->workflowTransitionRequestId = $workflowTransitionRequest->getId();
        $this->actionKey = $actionKey;

        parent::__construct(\sprintf(
            'The action "%s" is not available for the workflow transition request "%s".',
            $actionKey,
            $this->workflowTransitionRequestId,
        ));
    }

    public function getMessageTranslationKey(): string
    {
        return 'sulu_content.workflow_transition_request.action_not_available';
    }

    public function getMessageTranslationParameters(): array
    {
        return [
            '{workflowTransitionRequestId}' => $this->workflowTransitionRequestId,
            '{actionKey}' => $this->actionKey,
        ];
    }
}
