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
use Sulu\Page\Infrastructure\Sulu\Admin\PageAdmin;

class CacheClearedEvent extends DomainEvent
{
    /**
     * @var string
     */
    private $webspaceKey;

    /**
     * @var mixed[]
     */
    private $tags;

    /**
     * @param mixed[]|null $tags
     */
    public function __construct(string $webspaceKey, ?array $tags)
    {
        parent::__construct();

        $this->webspaceKey = $webspaceKey;
        $this->tags = $tags ?? [];
    }

    public function getEventType(): string
    {
        return 'cache_cleared';
    }

    public function getEventContext(): array
    {
        return [
            'tags' => $this->tags,
        ];
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
