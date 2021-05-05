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
use Sulu\Bundle\ContactBundle\Entity\AccountContact;
use Sulu\Bundle\EventLogBundle\Domain\Event\DomainEvent;

class AccountContactRemovedEvent extends DomainEvent
{
    /**
     * @var int
     */
    private $accountContactId;

    public function __construct(int $accountContactId)
    {
        parent::__construct();

        $this->accountContactId = $accountContactId;
    }

    public function getEventType(): string
    {
        return 'created';
    }

    public function getResourceKey(): string
    {
        return AccountContact::RESOURCE_KEY;
    }

    public function getResourceId(): string
    {
        return (string) $this->accountContactId;
    }

    public function getResourceSecurityContext(): ?string
    {
        return ContactAdmin::ACCOUNT_SECURITY_CONTEXT;
    }
}
