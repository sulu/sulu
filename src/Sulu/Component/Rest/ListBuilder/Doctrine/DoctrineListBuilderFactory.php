<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Rest\ListBuilder\Doctrine;

use Doctrine\ORM\EntityManager;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Factory for DoctrineListBuilders.
 */
class DoctrineListBuilderFactory
{
    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @var \Doctrine\ORM\EntityManager
     */
    private $em;

    /**
     * @var array
     */
    private $permissions;

    /**
     * @param EntityManager $em
     * @param EventDispatcherInterface $eventDispatcher
     * @param array $permissions
     */
    public function __construct(EntityManager $em, EventDispatcherInterface $eventDispatcher, array $permissions)
    {
        $this->em = $em;
        $this->eventDispatcher = $eventDispatcher;
        $this->permissions = $permissions;
    }

    /**
     * Creates a new DoctrineListBuilder for the given entity name and returns it.
     *
     * @param $entityName
     *
     * @return DoctrineListBuilder
     */
    public function create($entityName)
    {
        return new DoctrineListBuilder($this->em, $entityName, $this->eventDispatcher, $this->permissions);
    }
}
