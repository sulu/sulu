<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Admin\Routing;

use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\Expose;
use JMS\Serializer\Annotation\Groups;

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

    public function getView(): string
    {
        return $this->view;
    }

    public function setOption(string $key, $value): self
    {
        $this->options[$key] = $value;

        return $this;
    }

    public function getOption(string $key)
    {
        if (!array_key_exists($key, $this->options)) {
            return null;
        }

        return $this->options[$key];
    }

    public function mergeRoute(self $route): self
    {
        $this->options = array_merge($route->options, $this->options);

        return $this;
    }

    public function setAttributeDefault(string $key, string $value): self
    {
        $this->attributeDefaults[$key] = $value;

        return $this;
    }

    public function getAttributeDefault(string $key)
    {
        if (!array_key_exists($key, $this->attributeDefaults)) {
            return null;
        }

        return $this->attributeDefaults[$key];
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
