<?php

/*
 * This file is part of the Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Media\Exception;

/**
 */
class CacheNotFoundException extends MediaException
{
    /**
     * @param string $alias
     */
    public function __construct($alias)
    {
        parent::__construct('Format cache with the alias ' . $alias . ' was not found', self::EXCEPTION_CACHE_NOT_FOUND);
    }
}
