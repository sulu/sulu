<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Exception;

use Exception;

class InvalidRootTagException extends Exception
{
    /**
     * @var string
     */
    private $resource;

    /**
     * @var string
     */
    private $rootTag;

    public function __construct(string $resource, string $rootTag)
    {
        parent::__construct(
            \sprintf('The resource "%s" does not have a root tag named "%s"', $resource, $rootTag)
        );

        $this->resource = $resource;
        $this->rootTag = $rootTag;
    }

    public function getResource()
    {
        return $this->resource;
    }

    public function getRootTag()
    {
        return $this->rootTag;
    }
}
