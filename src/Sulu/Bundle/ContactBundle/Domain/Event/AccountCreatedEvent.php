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
use Sulu\Bundle\ActivityBundle\Domain\Event\DomainEvent;

class AccountCreatedEvent extends DomainEvent
{
    /**
     * @var AccountInterface
     */
    private $account;

    /**
     * @var mixed[]
     */
    private $payload;

    /**
     * @param mixed[] $payload
     */
    public function __construct(AccountInterface $account, array $payload)
    {
        parent::__construct();

        $this->account = $account;
        $this->payload = $payload;
    }

    public function getAccount(): AccountInterface
    {
        return $this->account;
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
        return AccountInterface::RESOURCE_KEY;
    }

    public function getResourceId(): string
    {
        return (string) $this->account->getId();
    }

    public function getResourceTitle(): ?string
    {
        return $this->account->getName();
    }

    public function getResourceSecurityContext(): ?string
    {
        return ContactAdmin::ACCOUNT_SECURITY_CONTEXT;
    }
}
