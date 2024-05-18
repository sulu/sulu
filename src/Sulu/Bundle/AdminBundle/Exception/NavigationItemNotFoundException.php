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

class NavigationItemNotFoundException extends \Exception
{
    public function __construct(private string $navigationItem)
    {
        parent::__construct(\sprintf('The NavigationItem with the name "%s" does not exist.', $navigationItem));
    }

    public function getNavigationItem(): string
    {
        return $this->navigationItem;
    }
}
