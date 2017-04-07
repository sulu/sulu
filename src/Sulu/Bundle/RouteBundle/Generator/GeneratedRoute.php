<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\RouteBundle\Generator;

use Sulu\Bundle\RouteBundle\Model\RoutableInterface;

/**
 * Represents a newly generated route. It contains additional the used entity and class to generate the route.
 */
class GeneratedRoute
{
    /**
     * @var RoutableInterface
     */
    private $entity;

    /**
     * @var string
     */
    private $path;

    /**
     * @var string
     */
    private $entityClass;

    /**
     * @param RoutableInterface $entity
     * @param string $path
     * @param string $entityClass
     */
    public function __construct(RoutableInterface $entity, $path, $entityClass = null)
    {
        $this->entity = $entity;
        $this->path = $path;
        $this->entityClass = $entityClass ?: get_class($this->entity);
    }

    /**
     * Returns entity.
     *
     * @return RoutableInterface
     */
    public function getEntity()
    {
        return $this->entity;
    }

    /**
     * Returns path.
     *
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * Returns entity-class.
     *
     * @return string
     */
    public function getEntityClass()
    {
        return $this->entityClass;
    }
}
