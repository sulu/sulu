<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Exception;

use Exception;

/**
 * Exception indicates not valid resourcelocator.
 */
class ResourceLocatorNotValidException extends Exception
{
    /**
     * @var string
     */
    private $resourceLocator;

    public function __construct($resourceLocator)
    {
        parent::__construct(sprintf("ResourceLocator '%s' is not valid", $resourceLocator));

        $this->resourceLocator = $resourceLocator;
    }

    /**
     * @return string
     */
    public function getResourceLocator()
    {
        return $this->resourceLocator;
    }
}
