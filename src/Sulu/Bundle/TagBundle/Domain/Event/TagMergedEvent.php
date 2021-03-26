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
use Sulu\Bundle\TagBundle\Tag\TagInterface;

class TagMergedEvent extends DomainEvent
{
    /**
     * @var int
     */
    private $sourceTagId;

    /**
     * @var string
     */
    private $sourceTagName;

    /**
     * @var TagInterface
     */
    private $destinationTag;

    public function __construct(
        TagInterface $destinationTag,
        int $sourceTagId,
        string $sourceTagName
    ) {
        parent::__construct();

        $this->sourceTagId = $sourceTagId;
        $this->sourceTagName = $sourceTagName;
        $this->destinationTag = $destinationTag;
    }

    public function getDestinationTag(): TagInterface
    {
        return $this->destinationTag;
    }

    public function getEventType(): string
    {
        return 'merged';
    }

    public function getEventContext(): array
    {
        return [
            'sourceTagId' => $this->sourceTagId,
            'sourceTagName' => $this->sourceTagName,
        ];
    }

    public function getResourceKey(): string
    {
        return 'tags';
    }

    public function getResourceId(): string
    {
        return (string) $this->destinationTag->getId();
    }

    public function getResourceTitle(): ?string
    {
        return $this->destinationTag->getName();
    }

    public function getResourceSecurityContext(): ?string
    {
        return 'sulu.settings.tags';
    }
}
