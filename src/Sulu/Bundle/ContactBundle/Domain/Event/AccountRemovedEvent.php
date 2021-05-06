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

class AccountRemovedEvent extends DomainEvent
{
    /**
     * @var int
     */
    private $accountId;

    /**
     * @var string
     */
    private $accountName;

    public function __construct(
        int $accountId,
        string $accountName
    ) {
        parent::__construct();

        $this->accountId = $accountId;
        $this->accountName = $accountName;
    }

    public function getEventType(): string
    {
        return 'removed';
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
        return $this->accountName;
    }

    public function getResourceSecurityContext(): ?string
    {
        return ContactAdmin::ACCOUNT_SECURITY_CONTEXT;
    }
}
