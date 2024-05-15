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

class InvalidRootTagException extends \Exception
{
    public function __construct(private string $resource, private string $rootTag)
    {
        parent::__construct(
            \sprintf('The resource "%s" does not have a root tag named "%s"', $resource, $rootTag)
        );
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
