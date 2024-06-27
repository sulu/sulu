<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\RouteBundle\Domain\Event;

use Sulu\Bundle\ActivityBundle\Domain\Event\DomainEvent;

class RouteRemovedEvent extends DomainEvent
{
    public function __construct(
        private int $routeId,
        private string $path,
        private string $locale,
        private string $entityId,
        private string $entityClass,
        private string $resourceKey
    ) {
        parent::__construct();
    }

    public function getEventType(): string
    {
        return 'route_removed';
    }

    public function getEventContext(): array
    {
        return [
            'id' => $this->routeId,
            'entityId' => $this->entityId,
            'entityClass' => $this->entityClass,
            'path' => $this->path,
        ];
    }

    public function getResourceKey(): string
    {
        return $this->resourceKey;
    }

    public function getResourceId(): string
    {
        return $this->entityId;
    }

    public function getResourceLocale(): ?string
    {
        return $this->locale;
    }

    public function getResourceTitle(): ?string
    {
        return $this->path;
    }
}
