<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Domain\Event;

use Sulu\Bundle\EventLogBundle\Domain\Event\DomainEvent;
use Sulu\Bundle\MediaBundle\Admin\MediaAdmin;
use Sulu\Bundle\MediaBundle\Api\Collection;
use Sulu\Bundle\MediaBundle\Entity\CollectionInterface;

class CollectionMovedEvent extends DomainEvent
{
    /**
     * @var CollectionInterface
     */
    private $collection;

    /**
     * @var int|null
     */
    private $previousParentId;

    public function __construct(
        CollectionInterface $collection,
        ?int $previousParentId
    ) {
        parent::__construct();

        $this->collection = $collection;
        $this->previousParentId = $previousParentId;
    }

    public function getCollection(): CollectionInterface
    {
        return $this->collection;
    }

    public function getEventType(): string
    {
        return 'moved';
    }

    public function getEventContext(): array
    {
        $newParent = $this->collection->getParent();

        return [
            'previousParentId' => $this->previousParentId,
            'newParentId' => $newParent ? $newParent->getId() : null,
        ];
    }

    public function getResourceKey(): string
    {
        return CollectionInterface::RESOURCE_KEY;
    }

    public function getResourceId(): string
    {
        return (string) $this->collection->getId();
    }

    public function getResourceTitle(): ?string
    {
        $collection = new Collection($this->collection, null);

        return $collection->getTitle();
    }

    public function getResourceSecurityContext(): ?string
    {
        return MediaAdmin::SECURITY_CONTEXT;
    }
}
