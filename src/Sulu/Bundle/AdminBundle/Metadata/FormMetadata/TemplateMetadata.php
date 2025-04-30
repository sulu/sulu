<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Metadata\FormMetadata;

class TemplateMetadata
{
    protected ?string $controller = null;

    protected ?string $view = null;

    protected ?CacheLifetimeMetadata $cacheLifetime = null;

    public function __construct()
    {
    }

    public function getController(): ?string
    {
        return $this->controller;
    }

    public function setController(?string $controller): void
    {
        $this->controller = $controller;
    }

    public function getView(): ?string
    {
        return $this->view;
    }

    public function setView(?string $view): void
    {
        $this->view = $view;
    }

    public function getCacheLifetime(): ?CacheLifetimeMetadata
    {
        return $this->cacheLifetime;
    }

    public function setCacheLifetime(?CacheLifetimeMetadata $cacheLifetime): void
    {
        $this->cacheLifetime = $cacheLifetime;
    }
}
