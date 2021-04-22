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
use Sulu\Bundle\MediaBundle\Entity\FileVersionMeta;
use Sulu\Bundle\MediaBundle\Entity\MediaInterface;

class MediaCropRemovedEvent extends DomainEvent
{
    /**
     * @var MediaInterface
     */
    private $media;

    /**
     * @var string
     */
    private $formatKey;

    public function __construct(
        MediaInterface $media,
        string $formatKey
    ) {
        parent::__construct();

        $this->media = $media;
        $this->formatKey = $formatKey;
    }

    public function getMedia(): MediaInterface
    {
        return $this->media;
    }

    public function getEventType(): string
    {
        return 'crop_removed';
    }

    public function getEventContext(): array
    {
        return [
            'formatKey' => $this->formatKey,
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
}
