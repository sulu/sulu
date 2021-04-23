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
use Sulu\Bundle\MediaBundle\Entity\Collection;
use Sulu\Bundle\MediaBundle\Entity\FileVersionMeta;
use Sulu\Bundle\MediaBundle\Entity\MediaInterface;

class MediaPreviewImageModifiedEvent extends DomainEvent
{
    /**
     * @var MediaInterface
     */
    private $media;

    /**
     * @var MediaInterface
     */
    private $newPreviewImage;

    /**
     * @var int
     */
    private $oldPreviewImageId;

    public function __construct(
        MediaInterface $media,
        MediaInterface $newPreviewImage,
        int $oldPreviewImageId
    ) {
        parent::__construct();

        $this->media = $media;
        $this->newPreviewImage = $newPreviewImage;
        $this->oldPreviewImageId = $oldPreviewImageId;
    }

    public function getMedia(): MediaInterface
    {
        return $this->media;
    }

    public function getNewPreviewImage(): MediaInterface
    {
        return $this->newPreviewImage;
    }

    public function getEventType(): string
    {
        return 'preview_image_modified';
    }

    public function getEventContext(): array
    {
        return [
            'oldPreviewImageId' => $this->oldPreviewImageId,
            'newPreviewImageId' => $this->newPreviewImage->getId(),
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

    public function getResourceSecurityType(): ?string
    {
        return Collection::class;
    }

    public function getResourceSecurityObjectId(): ?string
    {
        return (string) $this->getMedia()->getCollection()->getId();
    }
}
