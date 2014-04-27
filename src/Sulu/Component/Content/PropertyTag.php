<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content;

/**
 * Tag for property
 */
class PropertyTag
{
    /**
     * name of tag
     * @var string
     */
    private $name;

    /**
     * priority of tag
     * @var int
     */
    private $priority;

    /**
     * @param string $name
     * @param int $priority
     */
    function __construct($name, $priority)
    {
        $this->name = $name;
        $this->priority = $priority;
    }

    /**
     * returns name of tag
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * returns priority of tag
     * @return int
     */
    public function getPriority()
    {
        return $this->priority;
    }
}
