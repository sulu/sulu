<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\Domain\Event;

use Sulu\Bundle\ActivityBundle\Domain\Event\DomainEvent;
use Sulu\Bundle\WebsiteBundle\Admin\WebsiteAdmin;
use Sulu\Bundle\WebsiteBundle\Entity\AnalyticsInterface;

class AnalyticsRemovedEvent extends DomainEvent
{
    /**
     * @var int
     */
    private $id;

    /**
     * @var string
     */
    private $webspaceKey;

    /**
     * @var string|null
     */
    private $analyticsTitle;

    public function __construct(
        int $id,
        string $webspaceKey,
        ?string $analyticsTitle
    ) {
        parent::__construct();

        $this->id = $id;
        $this->webspaceKey = $webspaceKey;
        $this->analyticsTitle = $analyticsTitle;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getEventType(): string
    {
        return 'removed';
    }

    public function getResourceKey(): string
    {
        return AnalyticsInterface::RESOURCE_KEY;
    }

    public function getResourceId(): string
    {
        return (string) $this->id;
    }

    public function getResourceWebspaceKey(): string
    {
        return $this->webspaceKey;
    }

    public function getResourceTitle(): ?string
    {
        return $this->analyticsTitle;
    }

    public function getResourceSecurityContext(): ?string
    {
        return WebsiteAdmin::getAnalyticsSecurityContext($this->webspaceKey);
    }
}
