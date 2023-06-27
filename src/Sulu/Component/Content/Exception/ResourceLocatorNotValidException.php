<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Exception;

/**
 * Exception indicates not valid resourcelocator.
 */
class ResourceLocatorNotValidException extends \Exception
{
    /**
     * @param string $resourceLocator
     */
    public function __construct(private $resourceLocator)
    {
        parent::__construct(\sprintf("ResourceLocator '%s' is not valid", $resourceLocator));
    }

    /**
     * @return string
     */
    public function getResourceLocator()
    {
        return $this->resourceLocator;
    }
}
