<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AutomationBundle\Tasks\Model;

use Sulu\Component\Persistence\Model\AuditableInterface;

/**
 * Interface for tasks it contains functions which are necessary for managing tasks.
 */
interface TaskInterface extends AuditableInterface
{
    /**
     * Returns id.
     *
     * @return string
     */
    public function getId();

    /**
     * Set id.
     *
     * @param string $id
     *
     * @return $this
     */
    public function setId($id);

    /**
     * Returns task.
     *
     * @return string
     */
    public function getHandlerClass();

    /**
     * Returns schedule.
     *
     * @return \DateTime
     */
    public function getSchedule();

    /**
     * Returns locale.
     *
     * @return string
     */
    public function getLocale();

    /**
     * Returns entity-class.
     *
     * @return string
     */
    public function getEntityClass();

    /**
     * Returns entity-id.
     *
     * @return string
     */
    public function getEntityId();

    /**
     * Returns taskId.
     *
     * @return string
     */
    public function getTaskId();

    /**
     * Set taskId.
     *
     * @param string $taskId
     *
     * @return $this
     */
    public function setTaskId($taskId);

    /**
     * Returns creator full-name.
     *
     * @return string
     */
    public function getCreatorFullName();

    /**
     * Returns creator full-name.
     *
     * @return string
     */
    public function getChangerFullName();
}
