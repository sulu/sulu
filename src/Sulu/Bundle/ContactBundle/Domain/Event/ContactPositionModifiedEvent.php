<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContactBundle\Domain\Event;

use Sulu\Bundle\ContactBundle\Admin\ContactAdmin;
use Sulu\Bundle\ContactBundle\Entity\Position;
use Sulu\Bundle\ActivityBundle\Domain\Event\DomainEvent;

class ContactPositionModifiedEvent extends DomainEvent
{
    /**
     * @var Position
     */
    private $position;

    /**
     * @var mixed[]
     */
    private $payload;

    /**
     * @param mixed[] $payload
     */
    public function __construct(
        Position $position,
        array $payload
    ) {
        parent::__construct();

        $this->position = $position;
        $this->payload = $payload;
    }

    public function getPosition(): Position
    {
        return $this->position;
    }

    public function getEventType(): string
    {
        return 'modified';
    }

    public function getEventPayload(): ?array
    {
        return $this->payload;
    }

    public function getResourceKey(): string
    {
        return Position::RESOURCE_KEY;
    }

    public function getResourceId(): string
    {
        return (string) $this->position->getId();
    }

    public function getResourceTitle(): ?string
    {
        return $this->position->getPosition();
    }

    public function getResourceSecurityContext(): ?string
    {
        return ContactAdmin::CONTACT_SECURITY_CONTEXT;
    }
}
