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

use Sulu\Bundle\ActivityBundle\Domain\Event\DomainEvent;
use Sulu\Bundle\MediaBundle\Admin\MediaAdmin;
use Sulu\Bundle\MediaBundle\Entity\Collection;
use Sulu\Bundle\MediaBundle\Entity\CollectionInterface;
use Sulu\Bundle\MediaBundle\Entity\CollectionMeta;

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

    /**
     * @var string|null
     */
    private $previousParentTitle;

    /**
     * @var string|null
     */
    private $previousParentTitleLocale;

    public function __construct(
        CollectionInterface $collection,
        ?int $previousParentId,
        ?string $previousParentTitle,
        ?string $previousParentTitleLocale
    ) {
        parent::__construct();

        $this->collection = $collection;
        $this->previousParentId = $previousParentId;
        $this->previousParentTitle = $previousParentTitle;
        $this->previousParentTitleLocale = $previousParentTitleLocale;
    }

    public function getCollection(): CollectionInterface
    {
        return $this->collection;
    }

    public function getPreviousParentId(): ?int
    {
        return $this->previousParentId;
    }

    public function getPreviousParentTitle(): ?string
    {
        return $this->previousParentTitle;
    }

    public function getPreviousParentTitleLocale(): ?string
    {
        return $this->previousParentTitleLocale;
    }

    public function getEventType(): string
    {
        return 'moved';
    }

    public function getEventContext(): array
    {
        $previousParentTitle = null !== $this->previousParentId ? $this->previousParentTitle : 'ROOT';
        $previousParentTitleLocale = null !== $this->previousParentId ? $this->previousParentTitleLocale : null;

        /** @var CollectionInterface|null $newParent */
        $newParent = $this->collection->getParent();
        $newParentId = $newParent ? $newParent->getId() : null;
        $newParentMeta = $newParent ? $this->getCollectionMeta($newParent) : null;
        $newParentTitle = $newParentId ? ($newParentMeta ? $newParentMeta->getTitle() : null) : 'ROOT';
        $newParentTitleLocale = $newParentMeta ? $newParentMeta->getLocale() : null;

        return [
            'previousParentId' => $this->previousParentId,
            'previousParentTitle' => $previousParentTitle,
            'previousParentTitleLocale' => $previousParentTitleLocale,
            'newParentId' => $newParentId,
            'newParentTitle' => $newParentTitle,
            'newParentTitleLocale' => $newParentTitleLocale,
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
        $collectionMeta = $this->getCollectionMeta($this->collection);

        return $collectionMeta ? $collectionMeta->getTitle() : null;
    }

    public function getResourceTitleLocale(): ?string
    {
        $collectionMeta = $this->getCollectionMeta($this->collection);

        return $collectionMeta ? $collectionMeta->getLocale() : null;
    }

    public function getResourceSecurityContext(): ?string
    {
        return MediaAdmin::SECURITY_CONTEXT;
    }

    public function getResourceSecurityObjectType(): ?string
    {
        return Collection::class;
    }

    private function getCollectionMeta(CollectionInterface $collection): ?CollectionMeta
    {
        /** @var CollectionMeta|null $meta */
        $meta = $collection->getDefaultMeta();

        return $meta;
    }
}
