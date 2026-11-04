<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Media\Exception;

use Sulu\Component\Rest\Exception\TranslationErrorMessageExceptionInterface;

/**
 * This Exception is thrown when the end of the validity of a media is before its start.
 */
class InvalidValidityRangeException extends MediaException implements TranslationErrorMessageExceptionInterface
{
    public function __construct(?\Throwable $previous = null)
    {
        parent::__construct(
            'The validity end of a media cannot be before its validity start',
            self::EXCEPTION_INVALID_VALIDITY_RANGE,
            $previous
        );
    }

    public function getMessageTranslationKey(): string
    {
        return 'sulu_media.validity_range_invalid';
    }

    public function getMessageTranslationParameters(): array
    {
        return [];
    }
}
