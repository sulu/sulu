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
 * This Exception is thrown when the start or the end of the validity of a media is not a valid date.
 */
class InvalidValidityDateException extends MediaException implements TranslationErrorMessageExceptionInterface
{
    public function __construct(?\Throwable $previous = null)
    {
        parent::__construct(
            'The validity start and end of a media must be a date in the format Y-m-d',
            self::EXCEPTION_INVALID_VALIDITY_DATE,
            $previous
        );
    }

    public function getMessageTranslationKey(): string
    {
        return 'sulu_media.validity_date_invalid';
    }

    public function getMessageTranslationParameters(): array
    {
        return [];
    }
}
