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
    public function __construct(
        protected ?string $controller = null,
        protected ?string $view = null,
        protected ?CacheLifetimeMetadata $cacheLifetime = null,
    ) {
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

    public function merge(self $other): self
    {
        $mergedCacheLifetime = match (true) {
            null !== $other->cacheLifetime => clone ($other->cacheLifetime),
            null !== $this->cacheLifetime => clone ($this->cacheLifetime),
            default => null,
        };

        return new self(
            $other->getController() ?? $this->getController(),
            $other->getView() ?? $this->getView(),
            $mergedCacheLifetime,
        );
    }
}
