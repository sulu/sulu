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

class TagMergedEvent extends DomainEvent
{
    /**
     * @var int
     */
    private $sourceTagId;

    /**
     * @var int
     */
    private $sourceTagName;

    /**
     * @var TagInterface
     */
    private $destinationTag;

    public function __construct(
        int $sourceTagId,
        string $sourceTagName,
        TagInterface $destinationTag
    ) {
        parent::__construct();

        $this->sourceTagId = $sourceTagId;
        $this->sourceTagName = $sourceTagName;
        $this->destinationTag = $destinationTag;
    }

    public function getEventType(): string
    {
        return 'merged';
    }

    public function getEventPayload(): array
    {
        return [
            'destinationTagId' => $this->destinationTag->getId(),
            'destinationTagName' => $this->destinationTag->getName(),
        ];
    }

    public function getResourceKey(): string
    {
        return 'tags';
    }

    public function getResourceId(): string
    {
        return (string) $this->sourceTagId;
    }

    public function getResourceTitle(): ?string
    {
        return $this->sourceTagName;
    }

    public function getResourceSecurityContext(): ?string
    {
        return 'sulu.settings.tags';
    }
}
