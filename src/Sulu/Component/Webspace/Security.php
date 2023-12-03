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
    /**
     * @param ?string $system The key of the segment
     * @param bool $permissionCheck
     */
    public function __construct(
        private $system = null,
        private $permissionCheck = false
    ) {
    }

    /**
     * Sets the key of the segment.
     *
     * @deprecated since version 2.6 and will be removed in 3.0
     *
     * @param string $system
     */
    public function setSystem($system)
    {
        $this->system = $system;
    }

    /**
     * Returns the key of the segment.
     *
     * @return string
     */
    public function getSystem()
    {
        return $this->system;
    }

    /**
     * @deprecated since version 2.6 and will be removed in 3.0
     */
    public function setPermissionCheck(bool $permissionCheck)
    {
        $this->permissionCheck = $permissionCheck;
    }

    public function getPermissionCheck(): bool
    {
        return $this->permissionCheck;
    }
}
