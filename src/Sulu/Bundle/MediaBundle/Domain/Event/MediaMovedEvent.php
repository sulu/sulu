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
use Sulu\Bundle\MediaBundle\Entity\FileVersionMeta;
use Sulu\Bundle\MediaBundle\Entity\MediaInterface;

class MediaMovedEvent extends DomainEvent
{
    /**
     * @var MediaInterface
     */
    private $media;

    /**
     * @var int
     */
    private $previousCollectionId;

    /**
     * @var string|null
     */
    private $previousCollectionTitle;

    /**
     * @var string|null
     */
    private $previousCollectionTitleLocale;

    public function __construct(
        MediaInterface $media,
        int $previousCollectionId,
        ?string $previousCollectionTitle,
        ?string $previousCollectionTitleLocale
    ) {
        parent::__construct();

        $this->media = $media;
        $this->previousCollectionId = $previousCollectionId;
        $this->previousCollectionTitle = $previousCollectionTitle;
        $this->previousCollectionTitleLocale = $previousCollectionTitleLocale;
    }

    public function getMedia(): MediaInterface
    {
        return $this->media;
    }

    public function getPreviousCollectionId(): int
    {
        return $this->previousCollectionId;
    }

    public function getPreviousCollectionTitle(): ?string
    {
        return $this->previousCollectionTitle;
    }

    public function getPreviousCollectionTitleLocale(): ?string
    {
        return $this->previousCollectionTitleLocale;
    }

    public function getEventType(): string
    {
        return 'moved';
    }

    public function getEventContext(): array
    {
        $fileVersionMeta = $this->getFileVersionMeta();
        $locale = $fileVersionMeta ? $fileVersionMeta->getLocale() : null;

        $newCollection = $this->media->getCollection();
        $newCollectionId = $newCollection->getId();
        $newCollectionMeta = $this->getCollectionMeta($newCollection, $locale);
        $newCollectionTitle = $newCollectionMeta ? $newCollectionMeta->getTitle() : null;
        $newCollectionTitleLocale = $newCollectionMeta ? $newCollectionMeta->getLocale() : null;

        return [
            'previousCollectionId' => $this->previousCollectionId,
            'previousCollectionTitle' => $this->previousCollectionTitle,
            'previousCollectionTitleLocale' => $this->previousCollectionTitleLocale,
            'newCollectionId' => $newCollectionId,
            'newCollectionTitle' => $newCollectionTitle,
            'newCollectionTitleLocale' => $newCollectionTitleLocale,
        ];
    }

    public function getResourceKey(): string
    {
        return MediaInterface::RESOURCE_KEY;
    }

    public function getResourceId(): string
    {
        return (string) $this->media->getId();
    }

    public function getResourceTitle(): ?string
    {
        $fileVersionMeta = $this->getFileVersionMeta();

        return $fileVersionMeta ? $fileVersionMeta->getTitle() : null;
    }

    public function getResourceTitleLocale(): ?string
    {
        $fileVersionMeta = $this->getFileVersionMeta();

        return $fileVersionMeta ? $fileVersionMeta->getLocale() : null;
    }

    private function getFileVersionMeta(): ?FileVersionMeta
    {
        $file = $this->media->getFiles()[0] ?? null;
        $fileVersion = $file ? $file->getLatestFileVersion() : null;

        return $fileVersion ? $fileVersion->getDefaultMeta() : null;
    }

    public function getResourceSecurityContext(): ?string
    {
        return MediaAdmin::SECURITY_CONTEXT;
    }

    public function getResourceSecurityObjectType(): ?string
    {
        return Collection::class;
    }

    public function getResourceSecurityObjectId(): ?string
    {
        return (string) $this->getMedia()->getCollection()->getId();
    }

    private function getCollectionMeta(CollectionInterface $collection, ?string $locale): ?CollectionMeta
    {
        /** @var CollectionMeta|null $meta */
        $meta = $collection->getDefaultMeta();
        foreach ($collection->getMeta() as $collectionMeta) {
            if ($collectionMeta->getLocale() === $locale) {
                return $collectionMeta;
            }
        }

        return $meta;
    }
}
