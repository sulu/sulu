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
 */
class StorageNotFoundException extends MediaException
{
    /**
     * @param string $type
     */
    public function __construct($type)
    {
        parent::__construct('The storage "' . $type .'" was not found or configured.', self::EXCEPTION_CODE_STORAGE_NOT_FOUND);
    }
}
