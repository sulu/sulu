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

final class WorkflowTransitionRequestCancelNotAllowedException extends \RuntimeException implements TranslationErrorMessageExceptionInterface
{
    private readonly string $workflowTransitionRequestId;

    public function __construct(WorkflowTransitionRequest $workflowTransitionRequest)
    {
        $this->workflowTransitionRequestId = $workflowTransitionRequest->getId();

        parent::__construct(\sprintf(
            'Workflow transition request "%s" can only be cancelled with the edit permission.',
            $this->workflowTransitionRequestId,
        ));
    }

    public function getMessageTranslationKey(): string
    {
        return 'sulu_content.workflow_transition_request.cancel_not_allowed';
    }

    public function getMessageTranslationParameters(): array
    {
        return [
            '{workflowTransitionRequestId}' => $this->workflowTransitionRequestId,
        ];
    }
}
