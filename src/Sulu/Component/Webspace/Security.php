<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
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
     * The key of the segment.
     *
     * @var string
     */
    private $system;

    /**
     * Sets the key of the segment.
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
}
