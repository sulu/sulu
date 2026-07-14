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

class PublishWithoutRouteException extends UnavailableContentTransitionException implements TranslationErrorMessageExceptionInterface
{
    public const TRANSITION_BLOCKER_CODE = 'sulu_content.publish_without_route';

    public function getMessageTranslationKey(): string
    {
        return 'sulu_content.publish_without_route_error';
    }

    public function getMessageTranslationParameters(): array
    {
        return [];
    }
}
