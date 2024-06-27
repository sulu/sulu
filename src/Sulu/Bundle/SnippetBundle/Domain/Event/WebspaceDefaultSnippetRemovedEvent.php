<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SnippetBundle\Domain\Event;

use Sulu\Bundle\ActivityBundle\Domain\Event\DomainEvent;
use Sulu\Bundle\SnippetBundle\Admin\SnippetAdmin;

class WebspaceDefaultSnippetRemovedEvent extends DomainEvent
{
    public function __construct(
        private string $webspaceKey,
        private string $snippetAreaKey
    ) {
        parent::__construct();
    }

    public function getSnippetAreaKey(): string
    {
        return $this->snippetAreaKey;
    }

    public function getEventType(): string
    {
        return 'default_snippet_removed';
    }

    public function getEventContext(): array
    {
        return [
            'snippetAreaKey' => $this->snippetAreaKey,
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
        return SnippetAdmin::getDefaultSnippetsSecurityContext($this->webspaceKey);
    }
}
