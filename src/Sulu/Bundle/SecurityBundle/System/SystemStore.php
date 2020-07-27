<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SecurityBundle\System;

class SystemStore implements SystemStoreInterface
{
    /**
     * @var string
     */
    private $system;

    public function getSystem(): ?string
    {
        return $this->system;
    }

    public function setSystem(string $system): void
    {
        $this->system = $system;
    }
}
