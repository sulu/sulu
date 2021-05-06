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

class AnalyticsModifiedEvent extends DomainEvent
{
    /**
     * @var AnalyticsInterface
     */
    private $analytics;

    /**
     * @var mixed[]
     */
    private $payload;

    /**
     * @param mixed[] $payload
     */
    public function __construct(
        AnalyticsInterface $analytics,
        array $payload
    ) {
        parent::__construct();

        $this->analytics = $analytics;
        $this->payload = $payload;
    }

    public function getAnalytics(): AnalyticsInterface
    {
        return $this->analytics;
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
        return AnalyticsInterface::RESOURCE_KEY;
    }

    public function getResourceId(): string
    {
        return (string) $this->analytics->getId();
    }

    public function getResourceWebspaceKey(): string
    {
        return $this->analytics->getWebspaceKey();
    }

    public function getResourceTitle(): ?string
    {
        return $this->analytics->getTitle();
    }

    public function getResourceSecurityContext(): ?string
    {
        return WebsiteAdmin::getAnalyticsSecurityContext($this->analytics->getWebspaceKey());
    }
}
