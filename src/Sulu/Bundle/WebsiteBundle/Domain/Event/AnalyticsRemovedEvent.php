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

use Sulu\Bundle\EventLogBundle\Domain\Event\DomainEvent;
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
    private $title;

    public function __construct(
        int $id,
        string $webspaceKey,
        ?string $title
    ) {
        parent::__construct();

        $this->id = $id;
        $this->webspaceKey = $webspaceKey;
        $this->title = $title;
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
        return $this->title;
    }

    public function getResourceSecurityContext(): ?string
    {
        return WebsiteAdmin::getAnalyticsSecurityContext($this->webspaceKey);
    }
}
