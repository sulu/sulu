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

class MediaCreatedEvent extends DomainEvent
{
    /**
     * @var MediaInterface
     */
    private $media;

    /**
     * @var string
     */
    private $locale;

    /**
     * @var mixed[]
     */
    private $payload;

    /**
     * @param mixed[] $payload
     */
    public function __construct(
        MediaInterface $media,
        string $locale,
        array $payload
    ) {
        parent::__construct();

        $this->media = $media;
        $this->locale = $locale;
        $this->payload = $payload;
    }

    public function getMedia(): MediaInterface
    {
        return $this->media;
    }

    public function getEventType(): string
    {
        return 'created';
    }

    public function getEventPayload(): ?array
    {
        return $this->payload;
    }

    public function getResourceKey(): string
    {
        return MediaInterface::RESOURCE_KEY;
    }

    public function getResourceId(): string
    {
        return (string) $this->media->getId();
    }

    public function getResourceLocale(): ?string
    {
        return $this->locale;
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
        $meta = $fileVersion ? $fileVersion->getDefaultMeta() : null;

        if (null !== $fileVersion) {
            foreach ($fileVersion->getMeta() as $fileVersionMeta) {
                if ($fileVersionMeta->getLocale() === $this->locale) {
                    $meta = $fileVersionMeta;

                    break;
                }
            }
        }

        return $meta;
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
}
