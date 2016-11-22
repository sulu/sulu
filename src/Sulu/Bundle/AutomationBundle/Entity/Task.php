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
     * @var int
     */
    private $id;

    /**
     * @var string
     */
    private $taskName;

    /**
     * @var \DateTime
     */
    private $schedule;

    /**
     * @var string
     */
    private $entityClass;

    /**
     * @var string
     */
    private $entityId;

    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set id.
     *
     * @param int $id
     *
     * @return $this
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getTaskName()
    {
        return $this->taskName;
    }

    /**
     * Set task.
     *
     * @param string $taskName
     *
     * @return $this
     */
    public function setTaskName($taskName)
    {
        $this->taskName = $taskName;

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
