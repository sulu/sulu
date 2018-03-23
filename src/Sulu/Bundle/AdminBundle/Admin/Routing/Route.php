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

/**
 * Represents a route for adminstration frontend.
 */
class Route
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $view;

    /**
     * @var string
     */
    private $path;

    /**
     * @var array
     */
    private $options = [];

    /**
     * @var array
     */
    private $attributeDefaults = [];

    /**
     * @var string
     */
    private $parent;

    /**
     * @var array
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

    public function updatePath(string $newPath): void
    {
        $this->path = $newPath;
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
