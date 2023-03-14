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

class InvalidMimeTypeForPreviewException extends MediaException
{
    /**
     * @param string $mimeType
     */
    public function __construct($mimeType, ?\Throwable $e = null)
    {
        parent::__construct('The mimeType "' . $mimeType . '" is not supported for preview.', self::EXCEPTION_INVALID_MIMETYPE_FOR_PREVIEW, $e);
    }
}
