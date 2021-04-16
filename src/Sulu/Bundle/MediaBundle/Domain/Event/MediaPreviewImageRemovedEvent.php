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
use Sulu\Bundle\MediaBundle\Api\Media;
use Sulu\Bundle\MediaBundle\Entity\MediaInterface;

class MediaPreviewImageRemovedEvent extends DomainEvent
{
    /**
     * @var MediaInterface
     */
    private $media;

    /**
     * @var int
     */
    private $previewImageId;

    public function __construct(
        MediaInterface $media,
        int $previewImageId
    ) {
        parent::__construct();

        $this->media = $media;
        $this->previewImageId = $previewImageId;
    }

    public function getMedia(): MediaInterface
    {
        return $this->media;
    }

    public function getEventType(): string
    {
        return 'preview_image_removed';
    }

    public function getEventContext(): array
    {
        return [
            'previewImageId' => $this->previewImageId,
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
        $media = new Media($this->media, null);

        return $media->getTitle();
    }

    public function getResourceSecurityContext(): ?string
    {
        return MediaAdmin::SECURITY_CONTEXT;
    }
}
