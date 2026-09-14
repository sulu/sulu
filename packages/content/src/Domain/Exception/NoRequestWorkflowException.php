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

class NoRequestWorkflowException extends \RuntimeException implements TranslationErrorMessageExceptionInterface
{
    public function __construct(string $resourceKey, string $resourceId, string $locale)
    {
        parent::__construct(\sprintf(
            'No request workflow applies to the content "%s" "%s" in locale "%s", so it cannot be sent to review.',
            $resourceKey,
            $resourceId,
            $locale,
        ));
    }

    public function getMessageTranslationKey(): string
    {
        return 'sulu_content.workflow_transition_request.no_workflow';
    }

    public function getMessageTranslationParameters(): array
    {
        return [];
    }
}
