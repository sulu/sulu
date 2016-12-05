<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AutomationBundle\Entity;

use Sulu\Bundle\AutomationBundle\Tasks\Model\TaskInterface;
use Sulu\Component\Persistence\Model\AuditableTrait;

/**
 * Represents a task-entity.
 */
class Task implements TaskInterface
{
    use AuditableTrait;

    /**
     * @var string
     */
    private $id;

    /**
     * @var string
     */
    private $handlerClass;

    /**
     * @var \DateTime
     */
    private $schedule;

    /**
     * @var string
     */
    private $locale;

    /**
     * @var string
     */
    private $entityClass;

    /**
     * @var string
     */
    private $entityId;

    /**
     * @var string
     */
    private $taskId;

    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * {@inheritdoc}
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getHandlerClass()
    {
        return $this->handlerClass;
    }

    /**
     * Set task.
     *
     * @param string $handlerClass
     *
     * @return $this
     */
    public function setHandlerClass($handlerClass)
    {
        $this->handlerClass = $handlerClass;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getSchedule()
    {
        return $this->schedule;
    }

    /**
     * Set schedule.
     *
     * @param \DateTime $schedule
     *
     * @return $this
     */
    public function setSchedule($schedule)
    {
        $this->schedule = $schedule;

        return $this;
    }

    /**
     * Returns locale.
     *
     * @return string
     */
    public function getLocale()
    {
        return $this->locale;
    }

    /**
     * Set locale.
     *
     * @param string $locale
     *
     * @return $this
     */
    public function setLocale($locale)
    {
        $this->locale = $locale;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getEntityClass()
    {
        return $this->entityClass;
    }

    /**
     * Set entity-class.
     *
     * @param string $entityClass
     *
     * @return $this
     */
    public function setEntityClass($entityClass)
    {
        $this->entityClass = $entityClass;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getEntityId()
    {
        return $this->entityId;
    }

    /**
     * Set entity-id.
     *
     * @param mixed $entityId
     *
     * @return $this
     */
    public function setEntityId($entityId)
    {
        $this->entityId = $entityId;

        return $this;
    }

    /**
     * Returns taskId.
     *
     * @return string
     */
    public function getTaskId()
    {
        return $this->taskId;
    }

    /**
     * Set taskId.
     *
     * @param string $taskId
     *
     * @return $this
     */
    public function setTaskId($taskId)
    {
        $this->taskId = $taskId;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getCreatorFullName()
    {
        return $this->getCreator()->getFullName();
    }

    /**
     * {@inheritdoc}
     */
    public function getChangerFullName()
    {
        return $this->getChanger()->getFullName();
    }
}
