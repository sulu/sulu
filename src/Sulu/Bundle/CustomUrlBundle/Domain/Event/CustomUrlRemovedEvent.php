<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\CustomUrlBundle\Domain\Event;

use Sulu\Bundle\ActivityBundle\Domain\Event\DomainEvent;
use Sulu\Bundle\CustomUrlBundle\Admin\CustomUrlAdmin;
use Sulu\Component\CustomUrl\Document\CustomUrlDocument;

class CustomUrlRemovedEvent extends DomainEvent
{
    public function __construct(
        private string $customUrlUuid,
        private string $customUrlTitle,
        private string $webspaceKey
    ) {
        parent::__construct();
    }

    public function getEventType(): string
    {
        return 'removed';
    }

    public function getResourceKey(): string
    {
        return CustomUrlDocument::RESOURCE_KEY;
    }

    public function getResourceId(): string
    {
        return $this->customUrlUuid;
    }

    public function getResourceWebspaceKey(): ?string
    {
        return $this->webspaceKey;
    }

    public function getResourceTitle(): ?string
    {
        return $this->customUrlTitle;
    }

    public function getResourceSecurityContext(): ?string
    {
        return CustomUrlAdmin::getCustomUrlSecurityContext($this->webspaceKey);
    }
}
