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

class TagRemovedEvent extends DomainEvent
{
    /**
     * @var int
     */
    private $tagId;

    /**
     * @var int
     */
    private $tagName;

    public function __construct(
        int $tagId,
        string $tagName
    ) {
        parent::__construct();

        $this->tagId = $tagId;
        $this->tagName = $tagName;
    }

    public function getEventType(): string
    {
        return 'removed';
    }

    public function getEventPayload(): array
    {
        return [];
    }

    public function getResourceKey(): string
    {
        return 'tags';
    }

    public function getResourceId(): string
    {
        return (string) $this->tagId;
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
        return $this->tagName;
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
