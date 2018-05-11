<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Admin\Routing;

use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\Groups;
use JMS\Serializer\Annotation\Expose;

/**
 * Represents a route for adminstration frontend.
 *
 * @ExclusionPolicy("all")
 */
class Route
{
    /**
     * @var string
     *
     * @Expose()
     * @Groups({"fullRoute"})
     */
    private $name;

    /**
     * @var string
     *
     * @Expose()
     * @Groups({"fullRoute"})
     */
    private $view;

    /**
     * @var string
     *
     * @Expose()
     * @Groups({"fullRoute"})
     */
    private $path;

    /**
     * @var array
     *
     * @Expose()
     * @Groups({"fullRoute"})
     */
    private $options = [];

    /**
     * @var array
     *
     * @Expose()
     * @Groups({"fullRoute"})
     */
    private $attributeDefaults = [];

    /**
     * @var string
     *
     * @Expose()
     * @Groups({"fullRoute"})
     */
    private $parent;

    /**
     * @var array
     *
     * @Expose()
     * @Groups({"fullRoute"})
     */
    private $rerenderAttributes;

    public function __construct(string $name, string $path, string $view)
    {
        $this->name = $name;
        $this->path = $path;
        $this->view = $view;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function prependPath(string $prependPath): void
    {
        $this->path = $prependPath . $this->path;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function addOption(string $key, $value): self
    {
        $this->options[$key] = $value;

        return $this;
    }

    public function mergeRoute(self $route): self
    {
        $this->options = array_merge($route->options, $this->options);

        return $this;
    }

    public function addAttributeDefault(string $key, string $value): self
    {
        $this->attributeDefaults[$key] = $value;

        return $this;
    }

    public function setParent(string $parent): self
    {
        $this->parent = $parent;

        return $this;
    }

    public function getParent(): ?string
    {
        return $this->parent;
    }

    public function addRerenderAttribute(string $attribute): self
    {
        $this->rerenderAttributes[] = $attribute;

        return $this;
    }
}
