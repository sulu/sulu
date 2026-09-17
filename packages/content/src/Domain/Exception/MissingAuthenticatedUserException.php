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

/**
 * Thrown when a workflow transition request operation requires an authenticated Sulu user but the
 * security token storage does not provide one.
 */
class MissingAuthenticatedUserException extends \RuntimeException implements TranslationErrorMessageExceptionInterface
{
    public function __construct(string $operation)
    {
        parent::__construct(\sprintf(
            'A valid Sulu user is required to %s.',
            $operation,
        ));
    }

    public function getMessageTranslationKey(): string
    {
        return 'sulu_content.workflow_transition_request.missing_authenticated_user';
    }

    public function getMessageTranslationParameters(): array
    {
        return [];
    }
}
