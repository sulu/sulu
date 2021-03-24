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

use Sulu\Bundle\EventLogBundle\Domain\Event\DomainEvent;

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

    /**
     * @var bool
     */
    private $tagWasMerged;

    public function __construct(
        int $tagId,
        string $tagName,
        bool $tagWasMerged = false
    ) {
        parent::__construct();

        $this->tagId = $tagId;
        $this->tagName = $tagName;
        $this->tagWasMerged = $tagWasMerged;
    }

    public function getEventType(): string
    {
        return 'removed';
    }

    public function getEventContext(): array
    {
        return [
            'tagWasMerged' => $this->tagWasMerged,
        ];
    }

    public function getResourceKey(): string
    {
        return 'tags';
    }

    public function getResourceId(): string
    {
        return (string) $this->tagId;
    }

    public function getResourceTitle(): ?string
    {
        return $this->tagName;
    }

    public function getResourceSecurityContext(): ?string
    {
        return 'sulu.settings.tags';
    }
}
