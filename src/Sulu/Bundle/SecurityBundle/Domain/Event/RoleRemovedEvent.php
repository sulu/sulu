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

use Sulu\Bundle\ActivityBundle\Domain\Event\DomainEvent;
use Sulu\Bundle\SecurityBundle\Admin\SecurityAdmin;
use Sulu\Component\Security\Authentication\RoleInterface;

class RoleRemovedEvent extends DomainEvent
{
    public function __construct(private int $roleId, private string $roleName)
    {
        parent::__construct();
    }

    public function getEventType(): string
    {
        return 'removed';
    }

    public function getResourceKey(): string
    {
        return RoleInterface::RESOURCE_KEY;
    }

    public function getResourceId(): string
    {
        return (string) $this->roleId;
    }

    public function getResourceSecurityContext(): ?string
    {
        return SecurityAdmin::ROLE_SECURITY_CONTEXT;
    }

    public function getResourceTitle(): ?string
    {
        return $this->roleName;
    }
}
