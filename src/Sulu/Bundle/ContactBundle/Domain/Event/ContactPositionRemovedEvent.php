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
use Sulu\Bundle\EventLogBundle\Domain\Event\DomainEvent;

class ContactPositionRemovedEvent extends DomainEvent
{
    /**
     * @var int
     */
    private $positionId;

    /**
     * @var string
     */
    private $positionName;

    public function __construct(
        int $positionId,
        string $positionName
    ) {
        parent::__construct();

        $this->positionId = $positionId;
        $this->positionName = $positionName;
    }

    public function getEventType(): string
    {
        return 'removed';
    }

    public function getResourceKey(): string
    {
        return Position::RESOURCE_KEY;
    }

    public function getResourceId(): string
    {
        return (string) $this->positionId;
    }

    public function getResourceTitle(): ?string
    {
        return $this->positionName;
    }

    public function getResourceSecurityContext(): ?string
    {
        return ContactAdmin::CONTACT_SECURITY_CONTEXT;
    }
}
