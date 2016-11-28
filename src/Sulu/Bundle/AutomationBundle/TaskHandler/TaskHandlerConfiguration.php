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
class TaskHandlerConfiguration implements \JsonSerializable
{
    /**
     * Create a new configuration.
     *
     * @param string $entityClass
     * @param string $title
     *
     * @return static
     */
    public static function create($entityClass, $title)
    {
        return new self($entityClass, $title);
    }

    /**
     * @var string
     */
    private $handlerClass;

    /**
     * @var string
     */
    private $title;

    /**
     * @param string $handlerClass
     * @param string $title
     */
    private function __construct($handlerClass, $title)
    {
        $this->handlerClass = $handlerClass;
        $this->title = $title;
    }

    /**
     * Returns entityClass.
     *
     * @return string
     */
    public function getHandlerClass()
    {
        return $this->handlerClass;
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

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize()
    {
        return ['id' => $this->handlerClass, 'title' => $this->getTitle()];
    }
}
