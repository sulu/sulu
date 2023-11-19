<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Webspace\Manager;

class WebspaceCollectionBuilder
{
    public function __construct(private array $configuration = [])
    {
    }

    public function build(): WebsiteCollection
    {
        // transform symfony config object to collection object
    }
}
