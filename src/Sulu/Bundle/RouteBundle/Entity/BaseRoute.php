<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\RouteBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Hateoas\Configuration\Annotation\Relation;
use Hateoas\Configuration\Annotation\Route as HateoasRoute;
use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\Expose;
use JMS\Serializer\Annotation\VirtualProperty;
use Sulu\Bundle\RouteBundle\Model\RouteInterface;
use Sulu\Component\Persistence\Model\AuditableInterface;
use Sulu\Component\Persistence\Model\AuditableTrait;

/**
 * Represents a base route in the route-pool.
 *
 * @Relation(
 *     "delete",
 *     href = @HateoasRoute(
 *         "delete_route",
 *         parameters = { "id" = "expr(object.getId())" }
 *     )
 * )
 *
 * @ExclusionPolicy("all")
 */
abstract class BaseRoute implements RouteInterface, AuditableInterface
{
    use AuditableTrait;

    /**
     * @var int
     *
     * @Expose
     */
    private $id;

    /**
     * @var string
     *
     * @Expose
     */
    private $path;

    /**
     * @var string
     *
     * @Expose
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
     * @var bool
     *
     * @Expose
     */
    private $history = false;

    /**
     * @var RouteInterface
     */
    private $target;

    /**
     * @var RouteInterface[]
     */
    protected $histories;

    /**
     * @param string $path
     * @param string $entityId
     * @param string $entityClass
     * @param string $locale
     */
    public function __construct($path = null, $entityId = null, $entityClass = null, $locale = null)
    {
        $this->path = $path;
        $this->entityId = $entityId;
        $this->entityClass = $entityClass;
        $this->locale = $locale;
        $this->histories = new ArrayCollection();
    }

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
    public function setPath($path)
    {
        $this->path = $path;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * {@inheritdoc}
     */
    public function setLocale($locale)
    {
        $this->locale = $locale;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getLocale()
    {
        return $this->locale;
    }

    /**
     * {@inheritdoc}
     */
    public function getEntityClass()
    {
        return $this->entityClass;
    }

    /**
     * {@inheritdoc}
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
     * {@inheritdoc}
     */
    public function setEntityId($entityId)
    {
        $this->entityId = $entityId;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function isHistory()
    {
        return $this->history;
    }

    /**
     * {@inheritdoc}
     */
    public function setHistory($history)
    {
        $this->history = $history;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getTarget()
    {
        return $this->target;
    }

    /**
     * {@inheritdoc}
     */
    public function setTarget(RouteInterface $target = null)
    {
        $this->target = $target;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function removeTarget()
    {
        $this->target = null;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getHistories()
    {
        return $this->histories;
    }

    /**
     * {@inheritdoc}
     */
    public function addHistory(RouteInterface $history)
    {
        $this->histories[] = $history;

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @VirtualProperty
     */
    public function getCreated()
    {
        return $this->created;
    }
}
