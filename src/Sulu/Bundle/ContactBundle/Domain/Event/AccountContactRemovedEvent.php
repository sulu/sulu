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
    /**
     * @var int
     */
    private $contactId;
    /**
     * @var string
     */
    private $accountname;
    /**
     * @var string
     */
    private $contactName;

    public function __construct(int $accountId, int $contactId, string $accountName, string $contactName)
    {
        parent::__construct();

        $this->accountId = $accountId;
        $this->contactId = $contactId;
        $this->accountname = $accountName;
        $this->contactName = $contactName;
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

    public function getResourceTitle(): ?string
    {
        return $this->accountname;
    }

    public function getEventContext(): array
    {
        return [
            'contactId' => $this->contactId,
            'name' => $this->contactName,
        ];
    }

    public function getResourceSecurityContext(): ?string
    {
        return ContactAdmin::ACCOUNT_SECURITY_CONTEXT;
    }

    public function getContactId(): int
    {
        return $this->contactId;
    }

    public function getAccountname(): string
    {
        return $this->accountname;
    }

    public function getContactName(): string
    {
        return $this->contactName;
    }
}
