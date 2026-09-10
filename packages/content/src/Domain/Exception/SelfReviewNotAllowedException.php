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

class SelfReviewNotAllowedException extends \RuntimeException implements TranslationErrorMessageExceptionInterface
{
    private readonly string $workflowTransitionRequestId;

    public function __construct(WorkflowTransitionRequest $workflowTransitionRequest)
    {
        $this->workflowTransitionRequestId = $workflowTransitionRequest->getId();

        parent::__construct(\sprintf(
            'The creator of workflow transition request "%s" cannot review their own request.',
            $this->workflowTransitionRequestId,
        ));
    }

    public function getMessageTranslationKey(): string
    {
        return 'sulu_content.workflow_transition_request.self_review_not_allowed';
    }

    public function getMessageTranslationParameters(): array
    {
        return [
            '{workflowTransitionRequestId}' => $this->workflowTransitionRequestId,
        ];
    }
}
