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
 * This Exception is thrown when the classification of a media is not a string of up to 191 characters.
 */
class InvalidClassificationException extends MediaException implements TranslationErrorMessageExceptionInterface
{
    public function __construct(?\Throwable $previous = null)
    {
        parent::__construct(
            'The classification of a media must be a string of up to 191 characters',
            self::EXCEPTION_INVALID_CLASSIFICATION,
            $previous
        );
    }

    public function getMessageTranslationKey(): string
    {
        return 'sulu_media.classification_invalid';
    }

    public function getMessageTranslationParameters(): array
    {
        return [];
    }
}
