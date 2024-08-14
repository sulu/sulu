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
use Sulu\Bundle\CustomUrlBundle\Entity\CustomUrl;

class CustomUrlRemovedEvent extends DomainEvent
{
    public function __construct(
        private CustomUrl $customUrl,
    ) {
        parent::__construct();
    }

    public function getEventType(): string
    {
        return 'removed';
    }

    public function getResourceKey(): string
    {
        return CustomUrl::RESOURCE_KEY;
    }

    public function getResourceId(): string
    {
        return $this->customUrl->getId();
    }

    public function getResourceWebspaceKey(): ?string
    {
        return $this->customUrl->getWebspace();
    }

    public function getResourceTitle(): ?string
    {
        return $this->customUrl->getTitle();
    }

    public function getResourceSecurityContext(): ?string
    {
        return CustomUrlAdmin::getCustomUrlSecurityContext($this->webspaceKey);
    }
}
