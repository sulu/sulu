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

class MediaModifiedEvent extends DomainEvent
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
        return 'modified';
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
        $media = new Media($this->media, $this->locale);

        return $media->getTitle();
    }

    public function getResourceSecurityContext(): ?string
    {
        return MediaAdmin::SECURITY_CONTEXT;
    }
}
