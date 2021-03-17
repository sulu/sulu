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

use Sulu\Bundle\EventLogBundle\Event\DomainEvent;
use Sulu\Bundle\TagBundle\Tag\TagInterface;

class TagModifiedEvent extends DomainEvent
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
        return 'modified';
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

    public function getResourceTitle(): ?string
    {
        return $this->tag->getName();
    }

    public function getResourceSecurityContext(): ?string
    {
        return 'sulu.settings.tags';
    }
}
