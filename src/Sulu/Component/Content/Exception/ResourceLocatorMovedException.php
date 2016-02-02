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

class ResourceLocatorMovedException extends Exception
{
    /**
     * new resource locator after move.
     *
     * @var string
     */
    private $newResourceLocator;

    /**
     * uuid of new path node.
     *
     * @var string
     */
    private $newResourceLocatorUuid;

    public function __construct($newResourceLocator, $newResourceLocatorUuid)
    {
        $this->newResourceLocator = $newResourceLocator;
        $this->newResourceLocatorUuid = $newResourceLocatorUuid;
    }

    /**
     * @return string
     */
    public function getNewResourceLocator()
    {
        return $this->newResourceLocator;
    }

    /**
     * @return string
     */
    public function getNewResourceLocatorUuid()
    {
        return $this->newResourceLocatorUuid;
    }
}
