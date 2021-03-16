<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\TagBundle\Event;

use Sulu\Bundle\EventLogBundle\Event\DomainEvent;
use Sulu\Bundle\TagBundle\Tag\TagInterface;

class TagCreatedEvent extends DomainEvent
{
    /**
     * @var TagInterface
     */
    private $tag;

    /**
     * @var array
     */
    private $payload;

    public function __construct(
        TagInterface $tag,
        array $payload
    ) {
        parent::__construct();

        $this->tag = $tag;
        $this->payload = $payload;
    }

    public function getTag(): TagInterface
    {
        return $this->tag;
    }

    public function getEventType(): string
    {
        return 'created';
    }

    public function getEventPayload(): array
    {
        return $this->payload;
    }

    public function getResourceKey(): string
    {
        return 'tags';
    }

    public function getResourceId(): string
    {
        return (string) $this->tag->getId();
    }

    public function getResourceLocale(): ?string
    {
        return null;
    }

    public function getResourceWebspaceKey(): ?string
    {
        return null;
    }

    public function getResourceTitle(): ?string
    {
        return $this->tag->getName();
    }

    public function getResourceSecurityContext(): ?string
    {
        return 'sulu.settings.tags';
    }

    public function getResourceSecurityType(): ?string
    {
        return null;
    }
}
