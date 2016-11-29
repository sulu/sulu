<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AutomationBundle\TaskHandler;

/**
 * Contains configuration for task-handler.
 */
class TaskHandlerConfiguration
{
    /**
     * Create a new configuration.
     *
     * @param string $title
     *
     * @return static
     */
    public static function create($title)
    {
        return new self($title);
    }

    /**
     * @var string
     */
    private $title;

    /**
     * @param string $title
     */
    private function __construct($title)
    {
        $this->title = $title;
    }

    /**
     * Returns title.
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }
}
