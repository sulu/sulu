<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Metadata;

use Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\SchemaMetadata;

/**
 * Represents metadata for a structure.
 *
 * @deprecated use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FormMetadata instead
 */
class StructureMetadata extends PropertiesMetadata
{
    /**
     * @var array
     */
    protected $cacheLifetime;

    /**
     * @var string
     */
    protected $controller;

    /**
     * @var string
     */
    protected $view;

    /**
     * @var bool
     */
    protected $internal;

    /**
     * @var array
     */
    protected $areas;

    /**
     * @var SchemaMetadata
     */
    protected $schema;

    public function getCacheLifetime(): array
    {
        return $this->cacheLifetime;
    }

    public function setCacheLifetime(array $cacheLifetime): self
    {
        $this->cacheLifetime = $cacheLifetime;

        return $this;
    }

    public function getController(): ?string
    {
        return $this->controller;
    }

    public function setController(?string $controller = null): self
    {
        $this->controller = $controller;

        return $this;
    }

    public function getView(): ?string
    {
        return $this->view;
    }

    public function setView(?string $view = null): self
    {
        $this->view = $view;

        return $this;
    }

    public function isInternal(): bool
    {
        return $this->internal;
    }

    public function setInternal(bool $internal): self
    {
        $this->internal = $internal;

        return $this;
    }

    public function getAreas(): array
    {
        return $this->areas;
    }

    public function setAreas(array $areas): self
    {
        $this->areas = $areas;

        return $this;
    }

    public function getSchema(): ?SchemaMetadata
    {
        return $this->schema;
    }

    public function setSchema(SchemaMetadata $schema)
    {
        $this->schema = $schema;

        return $this;
    }
}
