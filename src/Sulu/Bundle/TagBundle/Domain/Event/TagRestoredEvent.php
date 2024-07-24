<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\TagBundle\Domain\Event;

use Sulu\Bundle\ActivityBundle\Domain\Event\DomainEvent;
use Sulu\Bundle\TagBundle\Admin\TagAdmin;
use Sulu\Bundle\TagBundle\Tag\TagInterface;

class TagRestoredEvent extends DomainEvent
{
    /**
     * @param mixed[] $payload
     */
    public function __construct(
        private TagInterface $tag,
        private array $payload
    ) {
        parent::__construct();
    }

    public function getTag(): TagInterface
    {
        return $this->tag;
    }

    public function getEventType(): string
    {
        return 'restored';
    }

    public function getEventPayload(): ?array
    {
        return $this->payload;
    }

    public function getResourceKey(): string
    {
        return TagInterface::RESOURCE_KEY;
    }

    public function getResourceId(): string
    {
        return (string) $this->tag->getId();
    }

    public function getResourceTitle(): ?string
    {
        return $this->tag->getName();
    }

    public function getResourceSecurityContext(): ?string
    {
        return TagAdmin::SECURITY_CONTEXT;
    }
}
