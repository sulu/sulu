<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Media\Exception;

/**
 * @package Sulu\Bundle\MediaBundle\Media\Exception
 */
class InvalidExtensionForPreviewException extends MediaException
{
    /**
     * @param string $extension
     */
    public function __construct($extension)
    {
        parent::__construct('The extension "' . $extension . '" is not supported for preview.', self::EXCEPTION_INVALID_EXTENSION_FOR_PREVIEW);
    }
}
