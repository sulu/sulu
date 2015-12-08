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
 * Exception which is thrown when a Storage adapter was not found or configured.
 */
class StorageAdapterNotFoundException extends MediaException
{
    /**
     * @param string $message
     */
    public function __construct($message)
    {
        parent::__construct($message, self::EXCEPTION_CODE_STORAGE_ADAPTER_NOT_FOUND);
    }
}
