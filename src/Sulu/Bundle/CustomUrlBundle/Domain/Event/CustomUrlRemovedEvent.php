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

use Sulu\Bundle\CustomUrlBundle\Admin\CustomUrlAdmin;
use Sulu\Bundle\EventLogBundle\Domain\Event\DomainEvent;
use Sulu\Component\CustomUrl\Document\CustomUrlDocument;

class CustomUrlRemovedEvent extends DomainEvent
{
    /**
     * @var string
     */
    private $customUrlUuid;

    /**
     * @var string
     */
    private $customUrlTitle;

    /**
     * @var string
     */
    private $webspaceKey;

    public function __construct(
        string $customUrlUuid,
        string $customUrlTitle,
        string $webspaceKey
    ) {
        parent::__construct();

        $this->customUrlUuid = $customUrlUuid;
        $this->customUrlTitle = $customUrlTitle;
        $this->webspaceKey = $webspaceKey;
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
