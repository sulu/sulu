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

use Sulu\Bundle\ActivityBundle\Domain\Event\DomainEvent;
use Sulu\Bundle\ContactBundle\Admin\ContactAdmin;
use Sulu\Bundle\ContactBundle\Entity\Contact;
use Sulu\Bundle\ContactBundle\Entity\ContactInterface;

class ContactCreatedEvent extends DomainEvent
{
    /**
     * @param mixed[] $payload
     */
    public function __construct(
        private ContactInterface $contact,
        private array $payload
    ) {
        parent::__construct();
    }

    public function getContact(): ContactInterface
    {
        return $this->contact;
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
        return ContactInterface::RESOURCE_KEY;
    }

    public function getResourceId(): string
    {
        return (string) $this->contact->getId();
    }

    public function getResourceTitle(): ?string
    {
        if ($this->contact instanceof Contact) {
            return $this->contact->getFullName();
        }

        return $this->contact->getFirstName() . ' ' . $this->contact->getLastName();
    }

    public function getResourceSecurityContext(): ?string
    {
        return ContactAdmin::CONTACT_SECURITY_CONTEXT;
    }
}
