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
use Sulu\Bundle\MediaBundle\Entity\MediaInterface;

class MediaRemovedEvent extends DomainEvent
{
    /**
     * @var int
     */
    private $mediaId;

    /**
     * @var string|null
     */
    private $mediaTitle;

    /**
     * @var string|null
     */
    private $mediaTitleLocale;

    public function __construct(
        int $mediaId,
        ?string $mediaTitle,
        ?string $mediaTitleLocale
    ) {
        parent::__construct();

        $this->mediaId = $mediaId;
        $this->mediaTitle = $mediaTitle;
        $this->mediaTitleLocale = $mediaTitleLocale;
    }

    public function getEventType(): string
    {
        return 'removed';
    }

    public function getResourceKey(): string
    {
        return MediaInterface::RESOURCE_KEY;
    }

    public function getResourceId(): string
    {
        return (string) $this->mediaId;
    }

    public function getResourceTitle(): ?string
    {
        return $this->mediaTitle;
    }

    public function getResourceTitleLocale(): ?string
    {
        return $this->mediaTitleLocale;
    }

    public function getResourceSecurityContext(): ?string
    {
        return MediaAdmin::SECURITY_CONTEXT;
    }
}
