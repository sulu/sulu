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
use Sulu\Bundle\MediaBundle\Entity\MediaInterface;

class MediaRemovedNoTrashEvent extends DomainEvent
{
    public function __construct(
        private int $mediaId,
        private int $collectionId,
        private ?string $mediaTitle,
        private ?string $mediaTitleLocale
    ) {
        parent::__construct();
    }

    public function getEventType(): string
    {
        return 'removed_no_trash';
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

    public function getResourceSecurityObjectType(): ?string
    {
        return Collection::class;
    }

    public function getResourceSecurityObjectId(): ?string
    {
        return (string) $this->collectionId;
    }
}
