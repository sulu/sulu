<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SecurityBundle\Domain\Event;

use Sulu\Bundle\EventLogBundle\Domain\Event\DomainEvent;
use Sulu\Bundle\SecurityBundle\Admin\SecurityAdmin;
use Sulu\Component\Security\Authentication\UserInterface;

class UserCreatedEvent extends DomainEvent
{
    /**
     * @var UserInterface
     */
    private $resourceUser;

    /**
     * @var mixed[]|null
     */
    private $payload;

    /**
     * @param mixed[] $payload
     */
    public function __construct(UserInterface $resourceUser, array $payload)
    {
        parent::__construct();

        $this->resourceUser = $resourceUser;
        $this->payload = $payload;
    }

    public function getEventType(): string
    {
        return 'created';
    }

    /**
     * @return mixed[]|null
     */
    public function getEventPayload(): ?array
    {
        return $this->payload;
    }

    public function getResourceKey(): string
    {
        return UserInterface::RESOURCE_KEY;
    }

    public function getResourceId(): string
    {
        return (string) $this->resourceUser->getId();
    }

    public function getResourceSecurityContext(): ?string
    {
        return SecurityAdmin::USER_SECURITY_CONTEXT;
    }

    public function getResourceUser(): UserInterface
    {
        return $this->resourceUser;
    }

    public function getResourceTitle(): ?string
    {
        return $this->resourceUser->getUsername();
    }
}
