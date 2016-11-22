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
     * @return int
     */
    public function getId();

    /**
     * Returns task.
     *
     * @return string
     */
    public function getTaskName();

    /**
     * Returns schedule.
     *
     * @return \DateTime
     */
    public function getSchedule();

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
