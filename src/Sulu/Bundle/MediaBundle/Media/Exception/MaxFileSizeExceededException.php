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

class MaxFileSizeExceededException extends UploadFileException implements TranslationErrorMessageExceptionInterface
{
    /**
     * @param string $message
     */
    public function __construct($message)
    {
        parent::__construct($message, self::EXCEPTION_CODE_MAX_FILE_SIZE);
    }

    public function getMessageTranslationKey(): string
    {
        return 'sulu_media.upload_exceeds_max_filesize';
    }

    public function getMessageTranslationParameters(): array
    {
        return [];
    }
}
