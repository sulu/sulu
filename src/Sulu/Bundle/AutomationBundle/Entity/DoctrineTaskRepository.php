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

use Doctrine\ORM\EntityRepository;
use Sulu\Bundle\AutomationBundle\Tasks\Model\TaskInterface;
use Sulu\Bundle\AutomationBundle\Tasks\Model\TaskRepositoryInterface;

/**
 * Task-Repository implementation for doctrine.
 */
class DoctrineTaskRepository extends EntityRepository implements TaskRepositoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function create()
    {
        $class = $this->_entityName;

        return new $class();
    }

    /**
     * {@inheritdoc}
     */
    public function save(TaskInterface $task)
    {
        $this->_em->persist($task);

        return $task;
    }

    /**
     * {@inheritdoc}
     */
    public function remove(TaskInterface $task)
    {
        $this->_em->remove($task);
    }

    /**
     * {@inheritdoc}
     */
    public function findById($id)
    {
        return $this->find($id);
    }

    /**
     * {@inheritdoc}
     */
    public function revert(TaskInterface $task)
    {
        $this->_em->refresh($task);

        return $task;
    }
}
