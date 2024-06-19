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

class CustomUrlCreatedEvent extends DomainEvent
{
    /**
     * @param mixed[] $payload
     */
    public function __construct(
        private CustomUrlDocument $customUrlDocument,
        private string $webspaceKey,
        private array $payload
    ) {
        parent::__construct();
    }

    public function getCustomUrlDocument(): CustomUrlDocument
    {
        return $this->customUrlDocument;
    }

    public function getEventType(): string
    {
        return 'created';
    }

    public function getEventPayload(): ?array
    {
        return $this->payload;
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
