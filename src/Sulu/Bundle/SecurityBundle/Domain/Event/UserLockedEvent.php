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

class UserLockedEvent extends DomainEvent
{
    /**
     * @var UserInterface
     */
    private $user;

    public function __construct(UserInterface $user)
    {
        parent::__construct();

        $this->user = $user;
    }

    public function getEventType(): string
    {
        return 'locked';
    }

    public function getResourceKey(): string
    {
        return UserInterface::RESOURCE_KEY;
    }

    public function getResourceId(): string
    {
        return (string) $this->user->getId();
    }

    public function getResourceSecurityContext(): ?string
    {
        return SecurityAdmin::USER_SECURITY_CONTEXT;
    }
}
