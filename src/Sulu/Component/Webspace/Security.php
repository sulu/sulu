<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Webspace;

/**
 * Represents the segments defined in a webspace.
 */
class Security
{
    private string $system;

    private bool $permissionCheck = false;

    /**
     * Sets the key of the segment.
     *
     * @param string $system
     */
    public function setSystem(string $system): void
    {
        $this->system = $system;
    }

    /**
     * Returns the key of the segment.
     *
     * @return string
     */
    public function getSystem(): string
    {
        return $this->system;
    }

    public function setPermissionCheck(bool $permissionCheck): void
    {
        $this->permissionCheck = $permissionCheck;
    }

    public function getPermissionCheck(): bool
    {
        return $this->permissionCheck;
    }
}
