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

class DuplicateActiveWorkflowTransitionRequestException extends \RuntimeException implements TranslationErrorMessageExceptionInterface
{
    public function __construct(
        private readonly string $resourceKey,
        private readonly string $resourceId,
        private readonly string $locale,
    ) {
        parent::__construct(\sprintf(
            'An active workflow transition request already exists for "%s" "%s" in locale "%s".',
            $resourceKey,
            $resourceId,
            $locale,
        ));
    }

    public function getMessageTranslationKey(): string
    {
        return 'sulu_content.workflow_transition_request.duplicate_active';
    }

    public function getMessageTranslationParameters(): array
    {
        return [
            '{resourceKey}' => $this->resourceKey,
            '{resourceId}' => $this->resourceId,
            '{locale}' => $this->locale,
        ];
    }
}
