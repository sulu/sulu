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

use Sulu\Bundle\CustomUrlBundle\Admin\CustomUrlAdmin;
use Sulu\Bundle\EventLogBundle\Event\DomainEvent;
use Sulu\Component\CustomUrl\Document\CustomUrlDocument;

class CustomUrlCreatedEvent extends DomainEvent
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
     * @var array
     */
    private $payload;

    public function __construct(
        CustomUrlDocument $customUrlDocument,
        string $webspaceKey,
        array $payload
    ) {
        parent::__construct();

        $this->customUrlDocument = $customUrlDocument;
        $this->webspaceKey = $webspaceKey;
        $this->payload = $payload;
    }

    public function getCustomUrlDocument(): CustomUrlDocument
    {
        return $this->customUrlDocument;
    }

    public function getEventType(): string
    {
        return 'created';
    }

    public function getEventPayload(): array
    {
        return $this->payload;
    }

    public function getResourceKey(): string
    {
        return 'custom_urls';
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
