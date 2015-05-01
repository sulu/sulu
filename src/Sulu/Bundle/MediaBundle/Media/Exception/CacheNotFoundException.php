<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Media\Exception;

/**
 * This Exception is thrown when the wanted format cache was not found.
 */
class CacheNotFoundException extends MediaException
{
    private $alias;

    /**
     * @param string $alias
     */
    public function __construct($alias)
    {
        $this->alias = $alias;

        parent::__construct('Format cache with the alias ' . $alias . ' was not found', self::EXCEPTION_CACHE_NOT_FOUND);
    }

    /**
     * The format cache alias which is not found.
     *
     * @return string
     */
    public function getAlias()
    {
        return $this->alias;
    }

    public function toArray()
    {
        return [
            'code' => $this->code,
            'message' => $this->message,
            'alias' => $this->alias,
        ];
    }
}
