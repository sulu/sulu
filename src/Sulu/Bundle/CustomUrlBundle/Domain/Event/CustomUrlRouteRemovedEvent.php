<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\CustomUrlBundle\Domain\Event;

use Sulu\Bundle\ActivityBundle\Domain\Event\DomainEvent;
use Sulu\Bundle\CustomUrlBundle\Admin\CustomUrlAdmin;
use Sulu\Component\CustomUrl\Document\CustomUrlDocument;

class CustomUrlRouteRemovedEvent extends DomainEvent
{
    /**
     * @var CustomUrlDocument
     */
    private $customUrlDocument;

    /**
     * @var string
     */
    private $webspaceKey;

    /**
     * @var string
     */
    private $routeUuid;

    public function __construct(
        CustomUrlDocument $customUrlDocument,
        string $webspaceKey,
        string $routeUuid
    ) {
        parent::__construct();

        $this->customUrlDocument = $customUrlDocument;
        $this->webspaceKey = $webspaceKey;
        $this->routeUuid = $routeUuid;
    }

    public function getCustomUrlDocument(): CustomUrlDocument
    {
        return $this->customUrlDocument;
    }

    public function getEventType(): string
    {
        return 'route_removed';
    }

    public function getEventContext(): array
    {
        return [
            'routeUuid' => $this->routeUuid,
        ];
    }

    public function getResourceKey(): string
    {
        return CustomUrlDocument::RESOURCE_KEY;
    }

    public function getResourceId(): string
    {
        return (string) $this->customUrlDocument->getUuid();
    }

    public function getResourceWebspaceKey(): ?string
    {
        return $this->webspaceKey;
    }

    public function getResourceTitle(): ?string
    {
        return $this->customUrlDocument->getTitle();
    }

    public function getResourceSecurityContext(): ?string
    {
        return CustomUrlAdmin::getCustomUrlSecurityContext($this->webspaceKey);
    }
}
