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
use Sulu\Bundle\PageBundle\Admin\PageAdmin;

class CacheClearedEvent extends DomainEvent
{
    /**
     * @var string
     */
    private $webspaceKey;

    /**
     * @var mixed[]
     */
    private $payload;

    /**
     * @param mixed[]|null $payload
     */
    public function __construct(string $webspaceKey, ?array $payload)
    {
        parent::__construct();

        $this->webspaceKey = $webspaceKey;
        $this->payload = $payload ?? [];
    }

    public function getEventType(): string
    {
        return 'cache_cleared';
    }

    public function getEventPayload(): ?array
    {
        return $this->payload;
    }

    public function getResourceKey(): string
    {
        return 'webspaces';
    }

    public function getResourceWebspaceKey(): ?string
    {
        return $this->webspaceKey;
    }

    public function getResourceId(): string
    {
        return $this->webspaceKey;
    }

    public function getResourceTitle(): ?string
    {
        return $this->webspaceKey;
    }

    public function getResourceSecurityContext(): ?string
    {
        return PageAdmin::SECURITY_CONTEXT_PREFIX . $this->webspaceKey;
    }
}
