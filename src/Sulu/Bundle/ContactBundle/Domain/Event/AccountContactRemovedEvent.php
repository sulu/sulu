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
use Sulu\Bundle\ContactBundle\Entity\AccountInterface;
use Sulu\Bundle\EventLogBundle\Domain\Event\DomainEvent;

class AccountContactRemovedEvent extends DomainEvent
{
    /**
     * @var int
     */
    private $accountId;

    public function __construct(int $accountId)
    {
        parent::__construct();

        $this->accountId = $accountId;
    }

    public function getEventType(): string
    {
        return 'contact_removed';
    }

    public function getResourceKey(): string
    {
        return AccountInterface::RESOURCE_KEY;
    }

    public function getResourceId(): string
    {
        return (string) $this->accountId;
    }

    public function getResourceSecurityContext(): ?string
    {
        return ContactAdmin::ACCOUNT_SECURITY_CONTEXT;
    }
}
