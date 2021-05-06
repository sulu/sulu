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
use Sulu\Bundle\ContactBundle\Entity\ContactTitle;
use Sulu\Bundle\ActivityBundle\Domain\Event\DomainEvent;

class ContactTitleRemovedEvent extends DomainEvent
{
    /**
     * @var int
     */
    private $contactTitleId;

    /**
     * @var string
     */
    private $contactTitleName;

    public function __construct(
        int $contactTitleId,
        string $contactTitleName
    ) {
        parent::__construct();

        $this->contactTitleId = $contactTitleId;
        $this->contactTitleName = $contactTitleName;
    }

    public function getEventType(): string
    {
        return 'removed';
    }

    public function getResourceKey(): string
    {
        return ContactTitle::RESOURCE_KEY;
    }

    public function getResourceId(): string
    {
        return (string) $this->contactTitleId;
    }

    public function getResourceTitle(): ?string
    {
        return $this->contactTitleName;
    }

    public function getResourceSecurityContext(): ?string
    {
        return ContactAdmin::CONTACT_SECURITY_CONTEXT;
    }
}
